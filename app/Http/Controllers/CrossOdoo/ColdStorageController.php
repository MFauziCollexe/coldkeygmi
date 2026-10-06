<?php

namespace App\Http\Controllers\CrossOdoo;

use App\Http\Controllers\Controller;
use App\Services\OdooXmlRpcService;
use Inertia\Inertia;
use Inertia\Response;

class ColdStorageController extends Controller
{
    public function index(): Response
    {
        $odoo = new OdooXmlRpcService;
        $coldStorageRoots = $odoo->searchRead(
            'stock.location',
            ['id', 'name', 'complete_name'],
            null,
            [['name', '=', 'CS'], ['usage', '=', 'view']],
        );
        $coldStorageRoot = $coldStorageRoots[0] ?? null;
        if ($coldStorageRoot === null) {
            return Inertia::render('GMISL/CrossOdoo/ColdStorage/Index', [
                'storages' => [],
                'customerOccupancy' => [],
                'updatedAt' => now()->format('d/m/Y H:i'),
            ]);
        }

        $storageRoots = $odoo->searchRead(
            'stock.location',
            ['id', 'name', 'complete_name'],
            null,
            [['location_id', '=', (int) $coldStorageRoot['id']], ['usage', '=', 'view']],
        );
        $storageRoots = array_values(array_filter(
            $storageRoots,
            fn (array $location): bool => preg_match('/^CS\s+\d+$/i', trim((string) ($location['name'] ?? ''))) === 1,
        ));

        $storages = [];
        $storageIdsByName = [];
        foreach ($storageRoots as $storageRoot) {
            $storageId = (int) $storageRoot['id'];
            $storageName = (string) ($storageRoot['name'] ?? $storageRoot['complete_name'] ?? 'Cold Storage');
            $storageIdsByName[strtoupper($storageName)] = $storageId;
            $storages[$storageId] = [
                'id' => $storageId,
                'name' => $storageName,
                'completeName' => (string) ($storageRoot['complete_name'] ?? $storageRoot['name'] ?? ''),
                'capacity' => 0,
                'used' => 0,
                'slots' => [],
            ];
        }

        $internalSlots = $odoo->searchRead(
            'stock.location',
            ['id', 'name', 'complete_name'],
            null,
            [['id', 'child_of', (int) $coldStorageRoot['id']], ['usage', '=', 'internal'], ['active', '=', true]],
            ['active_test' => false],
        );
        $slotToStorage = [];
        foreach ($internalSlots as $slot) {
            $completeName = (string) ($slot['complete_name'] ?? $slot['name'] ?? '');
            if (preg_match('/^(CS\s+\d+)\//i', $completeName, $matches) !== 1) {
                continue;
            }

            $storageId = $storageIdsByName[strtoupper(trim($matches[1]))] ?? null;
            if ($storageId === null) {
                continue;
            }

            $slotId = (int) $slot['id'];
            $slotToStorage[$slotId] = $storageId;
            $storages[$storageId]['capacity']++;
            $storages[$storageId]['slots'][$slotId] = [
                'id' => $slotId,
                'name' => (string) ($slot['name'] ?? $completeName),
                'completeName' => $completeName,
                'quantity' => 0.0,
                'reserved' => 0.0,
            ];
        }

        $quantGroups = $odoo->executeKw(
                'stock.quant',
                'read_group',
                [[['location_id', 'child_of', (int) $coldStorageRoot['id']], ['location_id.usage', '=', 'internal'], ['quantity', '>', 0]], ['location_id', 'quantity:sum', 'reserved_quantity:sum'], ['location_id']],
                ['lazy' => false],
            );

        foreach (is_array($quantGroups) ? $quantGroups : [] as $quantGroup) {
            $location = $quantGroup['location_id'] ?? false;
            if (! is_array($location) || ! isset($location[0])) {
                continue;
            }

            $slotId = (int) $location[0];
            $storageId = $slotToStorage[$slotId] ?? null;
            if ($storageId === null || ! isset($storages[$storageId]['slots'][$slotId])) {
                continue;
            }

            $storages[$storageId]['slots'][$slotId]['quantity'] = (float) ($quantGroup['quantity'] ?? 0);
            $storages[$storageId]['slots'][$slotId]['reserved'] = (float) ($quantGroup['reserved_quantity'] ?? 0);
            $storages[$storageId]['used']++;
        }

        $storages = array_values(array_map(function (array $storage): array {
            $storage['available'] = max(0, $storage['capacity'] - $storage['used']);
            $storage['occupancy'] = $storage['capacity'] > 0
                ? round($storage['used'] / $storage['capacity'] * 100, 1)
                : 0;
            $storage['status'] = $storage['occupancy'] >= 85
                ? 'high'
                : ($storage['occupancy'] >= 50 ? 'normal' : 'low');
            $storage['slots'] = array_values($storage['slots']);
            usort($storage['slots'], fn (array $left, array $right): int => strnatcasecmp($left['name'], $right['name']));

            return $storage;
        }, $storages));
        usort($storages, fn (array $left, array $right): int => strnatcasecmp($left['name'], $right['name']));

        $totalCapacity = array_sum(array_column($storages, 'capacity'));
        $ownerSlotGroups = $odoo->executeKw(
            'stock.quant',
            'read_group',
            [[
                ['location_id', 'child_of', (int) $coldStorageRoot['id']],
                ['location_id.usage', '=', 'internal'],
                ['quantity', '>', 0],
                ['owner_id', '!=', false],
            ], ['owner_id', 'location_id'], ['owner_id', 'location_id']],
            ['lazy' => false],
        );

        $customerSlots = [];
        $customerSlotsByStorage = [];
        foreach (is_array($ownerSlotGroups) ? $ownerSlotGroups : [] as $ownerSlotGroup) {
            $owner = $ownerSlotGroup['owner_id'] ?? false;
            $location = $ownerSlotGroup['location_id'] ?? false;
            if (! is_array($owner) || ! isset($owner[0], $owner[1]) || ! is_array($location) || ! isset($location[0])) {
                continue;
            }

            $ownerId = (int) $owner[0];
            $slotId = (int) $location[0];
            $storageId = $slotToStorage[$slotId] ?? null;
            if ($storageId === null) {
                continue;
            }

            if (! isset($customerSlots[$ownerId])) {
                $customerSlots[$ownerId] = [
                    'customer' => (string) $owner[1],
                    'occupiedSlots' => 0,
                ];
            }
            $customerSlots[$ownerId]['occupiedSlots']++;

            if (! isset($customerSlotsByStorage[$storageId][$ownerId])) {
                $customerSlotsByStorage[$storageId][$ownerId] = [
                    'customer' => (string) $owner[1],
                    'occupiedSlots' => 0,
                ];
            }
            $customerSlotsByStorage[$storageId][$ownerId]['occupiedSlots']++;
        }

        $customerOccupancy = array_values(array_map(function (array $customer) use ($totalCapacity): array {
            $customer['percentage'] = $totalCapacity > 0
                ? round($customer['occupiedSlots'] / $totalCapacity * 100, 1)
                : 0;

            return $customer;
        }, $customerSlots));
        usort($customerOccupancy, fn (array $left, array $right): int =>
            $right['occupiedSlots'] <=> $left['occupiedSlots'] ?: strnatcasecmp($left['customer'], $right['customer'])
        );

        foreach ($storages as &$storage) {
            $storageCustomerSlots = $customerSlotsByStorage[$storage['id']] ?? [];
            $storage['customerOccupancy'] = array_values(array_map(function (array $customer) use ($storage): array {
                $customer['percentage'] = $storage['capacity'] > 0
                    ? round($customer['occupiedSlots'] / $storage['capacity'] * 100, 1)
                    : 0;

                return $customer;
            }, $storageCustomerSlots));
            usort($storage['customerOccupancy'], fn (array $left, array $right): int =>
                $right['occupiedSlots'] <=> $left['occupiedSlots'] ?: strnatcasecmp($left['customer'], $right['customer'])
            );
        }
        unset($storage);

        return Inertia::render('GMISL/CrossOdoo/ColdStorage/Index', [
            'storages' => $storages,
            'customerOccupancy' => $customerOccupancy,
            'updatedAt' => now()->format('d/m/Y H:i'),
        ]);
    }
}