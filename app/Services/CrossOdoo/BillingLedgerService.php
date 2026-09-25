<?php

namespace App\Services\CrossOdoo;

use App\Services\OdooXmlRpcService;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

class BillingLedgerService
{
    /**
     * @return array{customers: array<int, array<string, mixed>>, products: array<int, array<string, mixed>>}
     */
    public function catalog(OdooXmlRpcService $odoo): array
    {
        $templates = $odoo->searchRead(
            'product.template',
            ['id', 'name', 'default_code', 'x_studio_customer'],
            null,
            [['x_studio_customer', '!=', false]],
            ['lang' => 'en_US'],
        );

        $customers = [];
        $products = [];

        foreach ($templates as $template) {
            $customer = $template['x_studio_customer'] ?? null;
            if (! is_array($customer) || count($customer) < 2) {
                continue;
            }

            $customerId = (int) $customer[0];
            $customerName = (string) $customer[1];
            $customers[$customerId] = [
                'customer_id' => $customerId,
                'customer_name' => $customerName,
            ];
            $products[] = [
                'product_id' => (int) $template['id'],
                'default_code' => ($template['default_code'] ?? false) !== false
                    ? (string) $template['default_code']
                    : null,
                'product_name' => (string) $template['name'],
                'customer_id' => $customerId,
                'customer_name' => $customerName,
            ];
        }

        $customers = array_values($customers);
        usort($customers, fn ($a, $b) => strcmp($a['customer_name'], $b['customer_name']));
        usort($products, fn ($a, $b) => strcmp(
            $a['customer_name'].'|'.$a['product_name'],
            $b['customer_name'].'|'.$b['product_name'],
        ));

        return compact('customers', 'products');
    }

    /**
     * @param  array<int, array<string, mixed>>  $products
     * @return array{selectedCustomerId: int|null, selectedProductId: int|null, customerName: string|null, productName: string|null}
     */
    public function resolveSelection(?int $customerId, ?int $productId, array $customers, array $products): array
    {
        $selectedCustomerId = $customerId ?: ($customers[0]['customer_id'] ?? null);
        $customerProducts = array_values(array_filter(
            $products,
            fn ($product) => (int) $product['customer_id'] === (int) $selectedCustomerId,
        ));
        $selectedProduct = null;
        foreach ($customerProducts as $product) {
            if ($productId !== null && (int) $product['product_id'] === $productId) {
                $selectedProduct = $product;
                break;
            }
        }
        $selectedProduct ??= $customerProducts[0] ?? null;

        $customerName = null;
        foreach ($customers as $customer) {
            if ((int) $customer['customer_id'] === (int) $selectedCustomerId) {
                $customerName = (string) $customer['customer_name'];
                break;
            }
        }

        return [
            'selectedCustomerId' => $selectedCustomerId,
            'selectedProductId' => $selectedProduct['product_id'] ?? null,
            'customerName' => $customerName ?? ($customers[0]['customer_name'] ?? null),
            'productName' => $selectedProduct['product_name'] ?? null,
        ];
    }

    /**
     * @return array{rows: array<int, array<string, mixed>>, totalRows: int}
     */
    public function ledger(
        OdooXmlRpcService $odoo,
        int $templateId,
        int $customerId,
        string $startDate,
        string $endDate,
    ): array {
        if ($startDate > $endDate) {
            throw new InvalidArgumentException('Tanggal mulai tidak boleh lebih besar dari tanggal akhir.');
        }

        $variantRows = $odoo->searchRead('product.product', ['id'], null, [['product_tmpl_id', '=', $templateId]]);
        $variantIds = array_map(fn ($row) => (int) $row['id'], $variantRows);
        if ($variantIds === []) {
            return ['rows' => [], 'totalRows' => 0];
        }

        $start = CarbonImmutable::parse($startDate)->startOfDay();
        $endExclusive = CarbonImmutable::parse($endDate)->addDay()->startOfDay();
        $domain = [
            ['state', '=', 'done'],
            ['product_id', 'in', $variantIds],
            ['date', '<', $endExclusive->format('Y-m-d H:i:s')],
            '|',
            ['owner_id', '=', false],
            ['owner_id', '=', $customerId],
        ];
        $lines = $odoo->searchRead(
            'stock.move.line',
            ['id', 'date', 'quantity', 'product_id', 'owner_id', 'location_id', 'location_dest_id', 'picking_type_id', 'result_package_id', 'package_id', 'lot_id', 'reference', 'picking_id'],
            null,
            $domain,
        );

        $locationMap = $this->fetchLocationMap($odoo, $lines);
        $pickingTypes = $this->fetchPickingTypes($odoo, $lines);
        $lineDetails = $this->fetchLineDetails($odoo, $lines, $templateId, $customerId, $pickingTypes, $locationMap);
        $opening = [];
        $openingDetails = [];
        $latestDetailsByLocation = [];
        $daily = [];

        foreach ($lines as $line) {
            $transactionDate = CarbonImmutable::parse((string) ($line['date'] ?? ''));
            $date = $transactionDate->format('Y-m-d');
            $time = $transactionDate->format('Y-m-d H:i:s');
            $source = $this->locationGroup($line['location_id'] ?? false, $locationMap);
            $destination = $this->locationGroup($line['location_dest_id'] ?? false, $locationMap);
            $details = $lineDetails[(int) ($line['id'] ?? 0)] ?? [];
            if ($details !== []) {
                if ($source !== null) {
                    $latestDetailsByLocation[$source] = [$details];
                }
                if ($destination !== null) {
                    $latestDetailsByLocation[$destination] = [$details];
                }
            }
            $movement = $this->classify($line, $pickingTypes);
            if ($movement === null) {
                continue;
            }

            $quantity = (float) ($line['quantity'] ?? 0);
            if ($quantity == 0.0) {
                continue;
            }

            $isBeforeRange = $date < $startDate;
            if ($isBeforeRange) {
                $this->applyBalance(
                    $opening,
                    $movement,
                    $source,
                    $destination,
                    $quantity,
                    $details,
                    $openingDetails,
                );
                continue;
            }
            if ($date > $endDate) {
                continue;
            }

            if ($movement === 'internal') {
                $this->applySilentInternalMovement(
                    $daily,
                    $date,
                    $source,
                    $destination,
                    $quantity,
                    $details,
                );
                continue;
            }

            $this->applyVisibleMovement(
                $daily,
                $date,
                $movement,
                $source,
                $destination,
                $quantity,
                $time,
                $details,
            );
        }

        $rows = [];
        $dailySummaries = [];
        $running = $opening;
        $runningDetails = array_replace($latestDetailsByLocation, $openingDetails);
        $historicalDetailsByLocation = [];
        foreach ($lines as $line) {
            $details = $lineDetails[(int) ($line['id'] ?? 0)] ?? [];
            if ($details === []) {
                continue;
            }
            foreach (['location_id', 'location_dest_id'] as $field) {
                $reference = $line[$field] ?? false;
                if (! is_array($reference) || ! isset($reference[0])) {
                    continue;
                }
                $normalized = $this->locationGroup($reference, $locationMap);
                if ($normalized !== null) {
                    $historicalDetailsByLocation[$normalized] = [$details];
                }
                if (isset($reference[1]) && $reference[1] !== '') {
                    $historicalDetailsByLocation[(string) $reference[1]] = [$details];
                }
            }
        }
        ksort($daily);

        for ($cursor = $start; $cursor < $endExclusive; $cursor = $cursor->addDay()) {
            $dateKey = $cursor->format('Y-m-d');
            $dateMovements = $daily[$dateKey] ?? [];
            $summary = [
                'opening' => array_sum($running),
                'in' => array_sum(array_map(fn ($movement) => (float) ($movement['in'] ?? 0.0), $dateMovements)),
                'out' => array_sum(array_map(fn ($movement) => (float) ($movement['out'] ?? 0.0), $dateMovements)),
                'closing' => 0.0,
            ];
            $dateRows = [];
            $hasVisibleDateMovement = array_reduce(
                $dateMovements,
                fn (bool $visible, array $movement): bool => $visible
                    || (float) ($movement['in'] ?? 0.0) !== 0.0
                    || (float) ($movement['out'] ?? 0.0) !== 0.0,
                false,
            );
            $locations = array_values(array_unique(array_merge(
                array_keys($running),
                array_keys($dateMovements),
            )));
            foreach ($locations as $location) {
                $openingBalance = (float) ($running[$location] ?? 0.0);
                $movement = array_merge(
                    ['in' => 0.0, 'out' => 0.0, 'internal' => 0.0, 'balance' => 0.0, 'details' => [], 'transactions' => []],
                    $dateMovements[$location] ?? [],
                );
                $closingBalance = $openingBalance + $movement['balance'];
                $hasMovement = $movement['in'] != 0.0 || $movement['out'] != 0.0;
                $hasCarryForward = ! $hasVisibleDateMovement && $closingBalance != 0.0;
                $rowDetails = $movement['details'] !== []
                    ? $movement['details']
                    : ($runningDetails[$location] ?? ($historicalDetailsByLocation[$location] ?? []));
                if (! $this->isBufferLocation($location) && $hasMovement) {
                    $transactionBalance = $openingBalance;
                    usort($movement['transactions'], fn ($left, $right) => strcmp($left['time'], $right['time']));
                    foreach ($movement['transactions'] as $transaction) {
                        $transactionOpening = $transactionBalance;
                        $transactionClosing = $transactionOpening + $transaction['in'] - $transaction['out'];
                        $transactionDetails = ($transaction['details'] ?? []) !== []
                            ? [$transaction['details']]
                            : $rowDetails;
                        $dateRows[] = $this->makeLedgerRow(
                            $dateKey,
                            [$transaction['time']],
                            $transactionOpening,
                            $location,
                            $transaction['in'],
                            $transaction['out'],
                            $transactionClosing,
                            $transactionDetails,
                        );
                        $transactionBalance = $transactionClosing;
                    }
                } elseif (! $this->isBufferLocation($location) && $hasCarryForward) {
                    $dateRows[] = $this->makeLedgerRow(
                        $dateKey,
                        [],
                        $openingBalance,
                        $location,
                        0.0,
                        0.0,
                        $closingBalance,
                        $rowDetails,
                    );
                }
                if ($movement['details'] !== []) {
                    $runningDetails[$location] = $movement['details'];
                }
                $running[$location] = $closingBalance;
            }
            $summary['closing'] = array_sum($running);
            $dailySummaries[$dateKey] = $summary;
            $rows = array_merge($rows, $dateRows);
        }

        return ['rows' => $rows, 'totalRows' => count($rows), 'dailySummaries' => $dailySummaries];
    }

    /** @param array<int, array<string, mixed>> $lines */
    private function fetchLocationMap(OdooXmlRpcService $odoo, array $lines): array
    {
        $ids = [];
        foreach ($lines as $line) {
            foreach (['location_id', 'location_dest_id'] as $field) {
                $ref = $line[$field] ?? false;
                if (is_array($ref) && isset($ref[0])) {
                    $ids[(int) $ref[0]] = true;
                }
            }
        }
        if ($ids === []) {
            return [];
        }

        $locations = $odoo->searchRead(
            'stock.location',
            ['id', 'name', 'complete_name', 'location_id', 'usage'],
            null,
            [],
            ['active_test' => false],
        );
        $map = [];
        foreach ($locations as $location) {
            $map[(int) $location['id']] = [
                'name' => (string) ($location['name'] ?? ''),
                'complete_name' => (string) ($location['complete_name'] ?? $location['name'] ?? ''),
                'parent_id' => is_array($location['location_id'] ?? false) ? (int) $location['location_id'][0] : null,
                'usage' => (string) ($location['usage'] ?? ''),
            ];
        }
        return $map;
    }

    /** @param array<int, array<string, mixed>> $lines */
    private function fetchPickingTypes(OdooXmlRpcService $odoo, array $lines): array
    {
        $ids = [];
        foreach ($lines as $line) {
            $ref = $line['picking_type_id'] ?? false;
            if (is_array($ref) && isset($ref[0])) {
                $ids[(int) $ref[0]] = true;
            }
        }
        if ($ids === []) {
            return [];
        }
        $types = $odoo->searchRead('stock.picking.type', ['id', 'code', 'sequence_code', 'name'], null, [['id', 'in', array_keys($ids)]]);
        $map = [];
        foreach ($types as $type) {
            $map[(int) $type['id']] = [
                'code' => strtolower((string) ($type['code'] ?? '')),
                'sequence_code' => strtoupper((string) ($type['sequence_code'] ?? '')),
                'name' => strtoupper((string) ($type['name'] ?? '')),
            ];
        }
        return $map;
    }

    /** @param array<int, array<string, mixed>> $lines */
    private function fetchLineDetails(OdooXmlRpcService $odoo, array $lines, int $templateId, int $customerId, array $pickingTypes, array $locationMap): array
    {
        $pickingIds = [];
        $lotIds = [];
        foreach ($lines as $line) {
            $picking = $line['picking_id'] ?? false;
            if (is_array($picking) && isset($picking[0])) {
                $pickingIds[(int) $picking[0]] = true;
            }
            $lot = $line['lot_id'] ?? false;
            if (is_array($lot) && isset($lot[0])) {
                $lotIds[(int) $lot[0]] = true;
            }
        }

        $pickings = $pickingIds === [] ? [] : $odoo->searchRead(
            'stock.picking',
            ['id', 'origin'],
            null,
            [['id', 'in', array_keys($pickingIds)]],
        );
        $pickingMap = [];
        foreach ($pickings as $picking) {
            $pickingMap[(int) $picking['id']] = ($picking['origin'] ?? false) ?: null;
        }

        $lots = $lotIds === [] ? [] : $odoo->searchRead(
            'stock.lot',
            ['id', 'expiration_date'],
            null,
            [['id', 'in', array_keys($lotIds)]],
        );
        $lotMap = [];
        foreach ($lots as $lot) {
            $lotMap[(int) $lot['id']] = ($lot['expiration_date'] ?? false) ?: null;
        }

        $templates = $odoo->searchRead(
            'product.template',
            ['id', 'default_code', 'name'],
            1,
            [['id', '=', $templateId]],
        );
        $template = $templates[0] ?? [];
        $defaultCode = ($template['default_code'] ?? false) ?: null;
        $productName = ($template['name'] ?? false) ?: null;
        $owners = $odoo->searchRead('res.partner', ['id', 'name'], 1, [['id', '=', $customerId]]);
        $defaultOwner = $owners[0]['name'] ?? (string) $customerId;
        $details = [];

        foreach ($lines as $line) {
            $owner = $line['owner_id'] ?? false;
            $picking = $line['picking_id'] ?? false;
            $lot = $line['lot_id'] ?? false;
            $pickingType = $line['picking_type_id'] ?? false;
            $destinationPackage = $line['result_package_id'] ?? ($line['package_id'] ?? false);
            $destination = $line['location_dest_id'] ?? false;
            $lotId = is_array($lot) && isset($lot[0]) ? (int) $lot[0] : null;
            $pickingId = is_array($picking) && isset($picking[0]) ? (int) $picking[0] : null;
            $pickingTypeId = is_array($pickingType) && isset($pickingType[0]) ? (int) $pickingType[0] : null;
            $details[(int) ($line['id'] ?? 0)] = [
                'Owner' => is_array($owner) ? ($owner[1] ?? null) : $defaultOwner,
                'Transaksi' => $pickingTypeId !== null ? ($pickingTypes[$pickingTypeId]['name'] ?? null) : null,
                'Destination package' => is_array($destinationPackage) ? ($destinationPackage[1] ?? null) : null,
                'Kode barang' => $defaultCode,
                'Nama barang' => $productName,
                'Source Document' => $pickingId !== null ? ($pickingMap[$pickingId] ?? null) : null,
                'Expired' => $lotId !== null ? ($lotMap[$lotId] ?? null) : null,
                'To' => is_array($destination) && isset($destination[0])
                    ? ($locationMap[(int) $destination[0]]['complete_name'] ?? ($destination[1] ?? null))
                    : null,
            ];
        }

        return $details;
    }

    private function classify(array $line, array $pickingTypes): ?string
    {
        $ref = $line['picking_type_id'] ?? false;
        $type = is_array($ref) ? ($pickingTypes[(int) ($ref[0] ?? 0)] ?? []) : [];
        $code = $type['code'] ?? '';
        $sequence = $type['sequence_code'] ?? '';
        $name = $type['name'] ?? '';
        if ($code === 'incoming'
            || $sequence === 'GR'
            || in_array($sequence, ['IN', 'INCOMING'], true)
            || str_contains($name, 'RECEIPT')
            || str_contains($name, 'INBOUND')) {
            return 'in';
        }
        if ($sequence === 'PICK' || str_contains($name, 'PICKING')) {
            return 'internal';
        }
        if ($code === 'outgoing'
            || $sequence === 'DO'
            || in_array($sequence, ['OUT', 'OUTGOING'], true)
            || str_contains($name, 'DELIVERY')
            || str_contains($name, 'OUTBOUND')) {
            return 'out';
        }
        if ($code === 'internal'
            || in_array($sequence, ['INT', 'JOIN'], true)
            || str_contains($name, 'INTERNAL')
            || str_contains($name, 'JOIN PALLET')) {
            return 'internal';
        }
        return null;
    }

    private function locationGroup($reference, array $locationMap): ?string
    {
        if (! is_array($reference) || ! isset($reference[0])) {
            return null;
        }
        $id = (int) $reference[0];
        return $locationMap[$id]['complete_name'] ?? (string) ($reference[1] ?? $id);
    }

    private function applyBalance(array &$balances, string $movement, ?string $source, ?string $destination, float $quantity, array $details = [], array &$balanceDetails = []): void
    {
        if ($movement === 'in' && $destination !== null) {
            $balances[$destination] = ($balances[$destination] ?? 0.0) + $quantity;
            if ($details !== []) {
                $balanceDetails[$destination] = [$details];
            }
        } elseif ($movement === 'out' && $source !== null) {
            $balances[$source] = ($balances[$source] ?? 0.0) - $quantity;
            if ($details !== []) {
                $balanceDetails[$source] = [$details];
            }
        } elseif ($movement === 'internal') {
            if ($source !== null) {
                $balances[$source] = ($balances[$source] ?? 0.0) - $quantity;
                if ($details !== []) {
                    $balanceDetails[$source] = [$details];
                }
            }
            if ($destination !== null) {
                $balances[$destination] = ($balances[$destination] ?? 0.0) + $quantity;
                if ($details !== []) {
                    $balanceDetails[$destination] = [$details];
                }
            }
        }
    }

    private function applyVisibleMovement(array &$daily, string $date, string $movement, ?string $source, ?string $destination, float $quantity, string $time, array $details = []): void
    {
        if ($movement === 'in' && $destination !== null) {
            $daily[$date][$destination]['balance'] = ($daily[$date][$destination]['balance'] ?? 0.0) + $quantity;
            $daily[$date][$destination]['details'][] = $details;
            if (! $this->isBufferLocation($destination)) {
                $daily[$date][$destination]['in'] = ($daily[$date][$destination]['in'] ?? 0.0) + $quantity;
                $daily[$date][$destination]['times'][] = $time;
            }
            $daily[$date][$destination]['transactions'][] = ['time' => $time, 'in' => $quantity, 'out' => 0.0, 'details' => $details];
        } elseif ($movement === 'out' && $source !== null) {
            $daily[$date][$source]['balance'] = ($daily[$date][$source]['balance'] ?? 0.0) - $quantity;
            $daily[$date][$source]['details'][] = $details;
            $daily[$date][$source]['out'] = ($daily[$date][$source]['out'] ?? 0.0) + $quantity;
            $daily[$date][$source]['times'][] = $time;
            $daily[$date][$source]['transactions'][] = ['time' => $time, 'in' => 0.0, 'out' => $quantity, 'details' => $details];
        } elseif ($movement === 'internal') {
            if ($source !== null) {
                $daily[$date][$source]['balance'] = ($daily[$date][$source]['balance'] ?? 0.0) - $quantity;
                $daily[$date][$source]['internal'] = ($daily[$date][$source]['internal'] ?? 0.0) - $quantity;
                $daily[$date][$source]['details'][] = $details;
                if (! $this->isBufferLocation($source)) {
                    $daily[$date][$source]['out'] = ($daily[$date][$source]['out'] ?? 0.0) + $quantity;
                    $daily[$date][$source]['times'][] = $time;
                }
                $daily[$date][$source]['transactions'][] = ['time' => $time, 'in' => 0.0, 'out' => $quantity, 'details' => $details];
            }
            if ($destination !== null) {
                $daily[$date][$destination]['balance'] = ($daily[$date][$destination]['balance'] ?? 0.0) + $quantity;
                $daily[$date][$destination]['internal'] = ($daily[$date][$destination]['internal'] ?? 0.0) + $quantity;
                $daily[$date][$destination]['details'][] = $details;
                if (! $this->isBufferLocation($destination)) {
                    $daily[$date][$destination]['in'] = ($daily[$date][$destination]['in'] ?? 0.0) + $quantity;
                    $daily[$date][$destination]['times'][] = $time;
                }
                $daily[$date][$destination]['transactions'][] = ['time' => $time, 'in' => $quantity, 'out' => 0.0, 'details' => $details];
            }
        }
    }

    private function applySilentInternalMovement(
        array &$daily,
        string $date,
        ?string $source,
        ?string $destination,
        float $quantity,
        array $details,
    ): void {
        if ($source !== null) {
            $daily[$date][$source]['balance'] = ($daily[$date][$source]['balance'] ?? 0.0) - $quantity;
            if ($details !== []) {
                $daily[$date][$source]['details'][] = $details;
            }
        }
        if ($destination !== null) {
            $daily[$date][$destination]['balance'] = ($daily[$date][$destination]['balance'] ?? 0.0) + $quantity;
            if ($details !== []) {
                $daily[$date][$destination]['details'][] = $details;
            }
        }
    }

    private function aggregateDetailValues(array $details, string $key): ?string
    {
        $values = [];
        foreach ($details as $detail) {
            $value = $detail[$key] ?? null;
            if ($value !== null && $value !== '' && ! in_array((string) $value, $values, true)) {
                $values[] = (string) $value;
            }
        }

        return $values === [] ? null : implode(', ', $values);
    }

    private function makeLedgerRow(
        string $date,
        array $times,
        float $opening,
        string $location,
        float $in,
        float $out,
        float $closing,
        array $details,
    ): array {
        return [
            'Date' => $date,
            'Times' => $times,
            'Saldo Awal' => $opening,
            'Location' => $location,
            'In' => $in,
            'Out' => $out,
            'Saldo Akhir' => $closing,
            'Owner' => $this->aggregateDetailValues($details, 'Owner'),
            'Transaksi' => $this->aggregateDetailValues($details, 'Transaksi'),
            'Destination package' => $this->aggregateDetailValues($details, 'Destination package'),
            'Kode barang' => $this->aggregateDetailValues($details, 'Kode barang'),
            'Nama barang' => $this->aggregateDetailValues($details, 'Nama barang'),
            'Source Document' => $this->aggregateDetailValues($details, 'Source Document'),
            'Expired' => $this->aggregateDetailValues($details, 'Expired'),
            'To' => $this->aggregateDetailValues($details, 'To'),
        ];
    }

    private function isBufferLocation(string $location): bool
    {
        $parts = preg_split('/\s*\/\s*/', strtolower(trim($location)));
        $leaf = (string) end($parts);

        return $leaf === 'input';
    }
}
