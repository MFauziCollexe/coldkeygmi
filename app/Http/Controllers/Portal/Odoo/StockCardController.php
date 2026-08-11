<?php

namespace App\Http\Controllers\Portal\Odoo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\IOFactory;

class StockCardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $userOwnerId = $user && !empty($user->from_owner_id) ? (string) $user->from_owner_id : null;

        $baseQuery = function () use ($userOwnerId) {
            $query = DB::table('t_move_history');

            if ($userOwnerId !== null) {
                $query->where('from_owner_id', $userOwnerId);
            }

            return $query;
        };

        $owners = $baseQuery()
            ->select('from_owner_id')
            ->selectRaw('MAX(from_owner) AS from_owner')
            ->whereNotNull('from_owner_id')
            ->where('from_owner_id', '!=', '')
            ->groupBy('from_owner_id')
            ->orderBy('from_owner_id')
            ->get()
            ->map(fn ($owner) => [
                'owner_id' => $owner->from_owner_id,
                'owner_name' => $owner->from_owner ?: $owner->from_owner_id,
            ])
            ->values()
            ->all();

        $products = $baseQuery()
            ->select('product_internal_reference', 'product_name')
            ->whereNotNull('product_internal_reference')
            ->whereNotNull('product_name')
            ->distinct()
            ->orderBy('product_internal_reference')
            ->get()
            ->map(fn ($product) => [
                'product_id' => $product->product_internal_reference,
                'default_code' => $product->product_internal_reference,
                'product_name' => $product->product_name,
            ])
            ->values()
            ->all();

        $selectedOwnerId = $request->input('owner_id');
        if ($userOwnerId !== null) {
            $selectedOwnerId = $userOwnerId;
        }

        $targetProductId = $request->input('product_id');
        if ($targetProductId !== null && $targetProductId !== '') {
            $targetProductId = (string) $targetProductId;
        } else {
            $targetProductId = null;
        }

        $hasFilters = $request->query('start_date') !== null || $request->query('end_date') !== null;

        $defaultStartDate = \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d');
        $defaultEndDate = \Carbon\Carbon::now()->startOfMonth()->addMonth()->format('Y-m-d');

        $startDate = $defaultStartDate;
        $endDate = $defaultEndDate;
        $customerName = null;
        $productName = null;
        $rows = [];
        $totalRows = 0;

        if ($hasFilters) {
            $startDate = $request->input('start_date', '');
            $endDate = $request->input('end_date', '');

            try {
                $start = new \DateTime($startDate);
                $end = new \DateTime($endDate);
            } catch (\Exception $exception) {
                $start = new \DateTime($defaultStartDate);
                $end = new \DateTime($defaultEndDate);
            }

            if ($end < $start) {
                $end = clone $start;
            }

            $startDate = $start->format('Y-m-d');
            $endDate = $end->format('Y-m-d');

            if ($selectedOwnerId !== null && $selectedOwnerId !== '') {
                $customerName = DB::table('t_move_history')
                    ->where('from_owner_id', $selectedOwnerId)
                    ->whereNotNull('from_owner')
                    ->where('from_owner', '!=', '')
                    ->value('from_owner');
            }

            if ($targetProductId !== null) {
                $productName = DB::table('t_move_history')
                    ->where('product_internal_reference', $targetProductId)
                    ->whereNotNull('product_name')
                    ->where('product_name', '!=', '')
                    ->value('product_name');
            }

            $customer = (string) ($customerName ?? '');
            $product = (string) ($targetProductId ?? '');

            $openingByProductQuery = <<<'SQL'
SELECT IFNULL(product_internal_reference, '') AS product,
       IFNULL(SUM(
           CASE
               WHEN operation_type IN (
                   'PT Golden Multi Indotama: Receipts',
                   'PT Golden Multi Indotama: Repack Inbound',
                   'PT Golden Multi Indotama: Adjustment Inbound',
                   'PT Golden Multi Indotama: Credit Note/Return'
               ) THEN quantity

               WHEN operation_type IN (
                   'PT Golden Multi Indotama: Delivery Orders',
                   'PT Golden Multi Indotama: Return Receipts',
                   'PT Golden Multi Indotama: Repack Outbound',
                   'PT Golden Multi Indotama: Adjustment Outbound'
               ) THEN -quantity

               ELSE 0
           END
       ), 0) AS opening
FROM t_move_history
WHERE DATE(`date`) < ?
  AND (? = '' OR from_owner = ?)
GROUP BY product_internal_reference
SQL;

            $openingByProduct = [];
            foreach (DB::select($openingByProductQuery, [$startDate, $customer, $customer]) as $openingRow) {
                $openingByProduct[(string) $openingRow->product] = (float) $openingRow->opening;
            }

        $rowsQuery = <<<'SQL'
SELECT

    t.tgl_tran,

    t.kd_gudang,

    t.kd_cust,

    t.kd_brg,

    t.nm_brg,

    t.no_mobil,

    t.no_reference_1,

    t.no_reference_2 AS source_document,

    t.no_po_so,

    '' AS no_invoice,

    t.keterangan,

    t.mutasi_in,

    t.mutasi_out

FROM
(

SELECT

    DATE(`date`) AS tgl_tran,

    reference AS kd_gudang,

    from_owner AS kd_cust,

    product_internal_reference AS kd_brg,

    MAX(product_name) AS nm_brg,

    MAX(transfer_plate_number) AS no_mobil,

    GROUP_CONCAT(DISTINCT reference) AS no_reference_1,

    MAX(CASE
        WHEN operation_type IN (
            'PT Golden Multi Indotama: Receipts',
            'PT Golden Multi Indotama: Repack Inbound',
            'PT Golden Multi Indotama: Adjustment Inbound',
            'PT Golden Multi Indotama: Credit Note/Return',
            'PT Golden Multi Indotama: Delivery Orders',
            'PT Golden Multi Indotama: Return Receipts',
            'PT Golden Multi Indotama: Repack Outbound',
            'PT Golden Multi Indotama: Adjustment Outbound'
        ) THEN source_documents
        ELSE NULL
    END) AS no_reference_2,

    GROUP_CONCAT(DISTINCT so_contract) AS no_po_so,

    GROUP_CONCAT(DISTINCT CASE
        WHEN operation_type IN (
            'PT Golden Multi Indotama: Receipts',
            'PT Golden Multi Indotama: Repack Inbound',
            'PT Golden Multi Indotama: Adjustment Inbound',
            'PT Golden Multi Indotama: Credit Note/Return',
            'PT Golden Multi Indotama: Delivery Orders',
            'PT Golden Multi Indotama: Return Receipts',
            'PT Golden Multi Indotama: Repack Outbound',
            'PT Golden Multi Indotama: Adjustment Outbound'
        ) THEN operation_type
        ELSE NULL
    END) AS keterangan,

    SUM(

        CASE

            WHEN operation_type IN (
                'PT Golden Multi Indotama: Receipts',
                'PT Golden Multi Indotama: Repack Inbound',
                'PT Golden Multi Indotama: Adjustment Inbound',
                'PT Golden Multi Indotama: Credit Note/Return'
            ) THEN quantity

            ELSE 0

        END

    ) AS mutasi_in,

    SUM(

        CASE

            WHEN operation_type IN (
                'PT Golden Multi Indotama: Delivery Orders',
                'PT Golden Multi Indotama: Return Receipts',
                'PT Golden Multi Indotama: Repack Outbound',
                'PT Golden Multi Indotama: Adjustment Outbound'
            ) THEN quantity

            ELSE 0

        END

    ) AS mutasi_out

FROM t_move_history

WHERE DATE(`date`) BETWEEN ? AND ?

AND (? = '' OR from_owner = ?)
AND (? = '' OR product_internal_reference = ?)

GROUP BY

    DATE(`date`),
    from_owner,
    product_internal_reference,
    reference

HAVING mutasi_in > 0 OR mutasi_out > 0

) t

ORDER BY

    t.kd_brg,
    t.tgl_tran;
SQL;

        $rows = DB::select($rowsQuery, [
            $startDate,
            $endDate,
            $customer,
            $customer,
            $product,
            $product,
        ]);
        $rows = array_map(fn ($row) => (array) $row, $rows);
        $totalRows = count($rows);
        }

        $page = max(1, (int) $request->query('page', 1));
        $perPage = 25;
        $page = min($page, max(1, (int) ceil($totalRows / $perPage)));

        return Inertia::render('Portal/Odoo/StockCard/Index', [
            'rows' => $rows,
            'owners' => $owners,
            'products' => $products,
            'selectedOwnerId' => $selectedOwnerId,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'targetProductId' => $targetProductId,
            'applied' => $hasFilters,
            'customerName' => $customerName,
            'productName' => $productName,
            'openingByProduct' => $openingByProduct ?? [],
            'currentPage' => $page,
            'perPage' => $perPage,
            'totalRows' => $totalRows,
        ]);
    }

    public function import(Request $request): JsonResponse
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        $user?->loadMissing('department');

        if (strtoupper(trim((string) ($user?->department?->code ?? ''))) !== 'IT') {
            abort(403, 'Hanya departemen IT yang dapat mengakses import.');
        }

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,xls,xlsx'],
        ]);

        $file = $request->file('file');
        if (! $file instanceof UploadedFile) {
            return response()->json(['message' => 'File upload tidak valid.'], 422);
        }

        $headerInfo = $this->getNormalizedHeadersFromFile($file);
        if (! in_array('id', $headerInfo['normalized'], true)) {
            return response()->json([
                'message' => 'File tidak berisi data yang valid atau kolom ID tidak ditemukan.',
                'detected_headers' => $headerInfo['normalized'],
                'raw_headers' => $headerInfo['raw'],
            ], 422);
        }

        $fields = $this->buildHeaderFields($headerInfo['normalized']);
        if (! in_array('id', $fields, true)) {
            return response()->json([
                'message' => 'File tidak berisi data yang valid atau kolom ID tidak ditemukan.',
                'detected_headers' => $headerInfo['normalized'],
                'raw_headers' => $headerInfo['raw'],
            ], 422);
        }

        $rowGenerator = $this->streamParsedMoveHistoryRows($file, $fields);
        $parsedCount = 0;
        $inserted = 0;
        $chunk = [];
        $chunkSize = 500;

        foreach ($rowGenerator as $row) {
            $parsedCount++;
            $chunk[] = $row;

            if (count($chunk) >= $chunkSize) {
                $inserted += $this->insertChunkedRows($chunk);
                $chunk = [];
            }
        }

        if (! empty($chunk)) {
            $inserted += $this->insertChunkedRows($chunk);
        }

        if ($parsedCount === 0) {
            return response()->json([
                'message' => 'File tidak berisi data yang valid setelah parsing.',
                'detected_headers' => $headerInfo['normalized'],
                'raw_headers' => $headerInfo['raw'],
            ], 422);
        }

        $skipped = $parsedCount - $inserted;

        return response()->json([
            'inserted' => $inserted,
            'skipped' => $skipped,
            'message' => sprintf('%d baris baru disimpan. %d baris dilewati karena ID sudah ada.', $inserted, $skipped),
        ]);
    }

    private function streamParsedMoveHistoryRows(UploadedFile $file, array $fields): \Generator
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (in_array($extension, ['xls', 'xlsx'], true)) {
            yield from $this->streamSpreadsheetDataRows($file->getPathname(), $fields);
            return;
        }

        $delimiter = $this->detectCsvDelimiter($file->getPathname());
        yield from $this->streamCsvDataRows($file->getPathname(), $delimiter, $fields);
    }

    private function streamSpreadsheetDataRows(string $path, array $fields): \Generator
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);

        $spreadsheet = $reader->load($path);
        $sheet = $spreadsheet->getSheet(0);
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestColumn());
        $highestRow = $sheet->getHighestRow();

        $seenIds = [];
        for ($rowIndex = 2; $rowIndex <= $highestRow; ++$rowIndex) {
            $row = [];
            for ($colIndex = 1; $colIndex <= $highestColumnIndex; ++$colIndex) {
                $row[] = $sheet->getCellByColumnAndRow($colIndex, $rowIndex)->getValue();
            }

            if ($this->isBlankRow($row)) {
                continue;
            }

            $parsed = $this->mapRowToFields($row, $fields);
            $parsed['id'] = (string) ($parsed['id'] ?? '');
            if ($parsed['id'] === '' || isset($seenIds[$parsed['id']])) {
                continue;
            }

            $seenIds[$parsed['id']] = true;
            yield $parsed;
        }
    }

    private function streamCsvDataRows(string $path, string $delimiter, array $fields): \Generator
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return;
        }

        fgetcsv($handle, 0, $delimiter);
        $seenIds = [];

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if ($this->isBlankRow($row)) {
                continue;
            }

            $parsed = $this->mapRowToFields($row, $fields);
            $parsed['id'] = (string) ($parsed['id'] ?? '');
            if ($parsed['id'] === '' || isset($seenIds[$parsed['id']])) {
                continue;
            }

            $seenIds[$parsed['id']] = true;
            yield $parsed;
        }

        fclose($handle);
    }

    private function mapRowToFields(array $row, array $fields): array
    {
        $parsed = [];

        foreach ($fields as $columnIndex => $field) {
            if ($field === null) {
                continue;
            }

            $parsed[$field] = $this->castValue($field, $row[$columnIndex] ?? null);
        }

        return array_merge([
            'date' => null,
            'operation_type' => null,
            'from_owner' => null,
            'from_owner_id' => null,
            'display_name' => null,
            'product_internal_reference' => null,
            'product_name' => null,
            'lot_serial_number' => null,
            'expiration_date' => null,
            'source_location_storage_category' => null,
            'from_location' => null,
            'to_location' => null,
            'unit_of_measure' => null,
            'product_product_standard_qty_pallet' => 0,
            'quantity' => 0,
            'qty_in_kgs' => 0,
            'qty_in_actual_kgs' => 0,
            'product_category' => null,
            'reference' => null,
            'source_documents' => null,
            'destination_package' => null,
            'transfer_plate_number' => null,
            'status' => null,
            'stock_operation' => null,
            'so_contract' => null,
            'room_type' => null,
            'product' => null,
            'plat_number' => null,
            'expiration_date_2' => null,
            'created_on' => null,
            'product_customer_reference' => null,
        ], $parsed);
    }

    private function insertChunkedRows(array $rows): int
    {
        if (empty($rows)) {
            return 0;
        }

        return DB::table('t_move_history')->insertOrIgnore($rows);
    }

    private function parseMoveHistoryFile(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $rows = [];

        if (in_array($extension, ['xls', 'xlsx'], true)) {
            $spreadsheet = IOFactory::load($file->getPathname());
            $sheet = $spreadsheet->getSheet(0);
            $rows = $sheet->toArray(null, true, true, false);
        } else {
            $rows = $this->parseCsvRows($file->getPathname());
        }

        if (count($rows) < 2) {
            return [];
        }

        $headers = array_map(fn ($header) => $this->normalizeHeader($header), array_shift($rows));
        $fields = $this->buildHeaderFields($headers);

        if (! in_array('id', $fields, true)) {
            return [];
        }

        $parsedRows = [];
        foreach ($rows as $row) {
            if ($this->isBlankRow($row)) {
                continue;
            }

            $parsed = [];
            foreach ($fields as $columnIndex => $field) {
                if ($field === null) {
                    continue;
                }

                $parsed[$field] = $this->castValue($field, $row[$columnIndex] ?? null);
            }

            $parsed['id'] = (string) ($parsed['id'] ?? '');
            if ($parsed['id'] === '') {
                continue;
            }

            $parsedRows[$parsed['id']] = array_merge([
                'date' => null,
                'operation_type' => null,
                'from_owner' => null,
                'display_name' => null,
                'product_internal_reference' => null,
                'product_name' => null,
                'lot_serial_number' => null,
                'expiration_date' => null,
                'source_location_storage_category' => null,
                'from_location' => null,
                'to_location' => null,
                'unit_of_measure' => null,
                'product_product_standard_qty_pallet' => 0,
                'quantity' => 0,
                'qty_in_kgs' => 0,
                'qty_in_actual_kgs' => 0,
                'product_category' => null,
                'reference' => null,
                'source_documents' => null,
                'destination_package' => null,
                'transfer_plate_number' => null,
                'status' => null,
                'stock_operation' => null,
                'so_contract' => null,
                'room_type' => null,
                'product' => null,
                'plat_number' => null,
                'expiration_date_2' => null,
                'created_on' => null,
                'product_customer_reference' => null,
            ], $parsed);
        }

        return array_values($parsedRows);
    }

    private function buildHeaderFields(array $headers): array
    {
        $seen = [];
        $fields = [];

        foreach ($headers as $header) {
            $seen[$header] = ($seen[$header] ?? 0) + 1;
            $field = match ($header) {
                'date' => 'date',
                'operation_type' => 'operation_type',
                'from_owner' => 'from_owner',
                'from_owner_id' => 'from_owner_id',
                'display_name' => 'display_name',
                'product_internal_reference' => 'product_internal_reference',
                'product_name' => 'product_name',
                'lot_serial_number' => 'lot_serial_number',
                'expiration_date' => $seen[$header] > 1 ? 'expiration_date_2' : 'expiration_date',
                'source_location_storage_category' => 'source_location_storage_category',
                'from' => 'from_location',
                'to' => 'to_location',
                'unit_of_measure' => 'unit_of_measure',
                'product_product_standard_qty_pallet', 'product_product_standar_qty_pallet' => 'product_product_standard_qty_pallet',
                'quantity' => 'quantity',
                'qty_in_kgs' => 'qty_in_kgs',
                'qty_in_actual_kgs' => 'qty_in_actual_kgs',
                'product_category' => 'product_category',
                'reference' => 'reference',
                'source_documents' => 'source_documents',
                'destination_package' => 'destination_package',
                'transfer_plate_number' => 'transfer_plate_number',
                'status' => 'status',
                'stock_operation' => 'stock_operation',
                'id', 'move_id' => 'id',
                'so_contract' => 'so_contract',
                'room_type' => 'room_type',
                'product' => 'product',
                'plat_number' => 'plat_number',
                'created_on' => 'created_on',
                'product_customer_reference' => 'product_customer_reference',
                default => null,
            };

            $fields[] = $field;
        }

        return $fields;
    }

    private function normalizeHeader($value): string
    {
        $str = mb_strtolower((string) $value);
        $str = preg_replace('/[^a-z0-9]+/u', '_', $str);
        return trim($str, '_');
    }

    private function getNormalizedHeadersFromFile(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (in_array($extension, ['xls', 'xlsx'], true)) {
            try {
                $spreadsheet = IOFactory::load($file->getPathname());
                $sheet = $spreadsheet->getSheet(0);
                $firstRow = $sheet->rangeToArray('A1:' . $sheet->getHighestColumn() . '1', null, true, true, false);
                $raw = $firstRow[0] ?? [];
                $normalized = array_map(fn($h) => $this->normalizeHeader($h), $raw);

                return ['normalized' => $normalized, 'raw' => $raw];
            } catch (\Throwable $e) {
                return ['normalized' => [], 'raw' => []];
            }
        }

        // CSV fallback: read first line and split with best delimiter
        $path = $file->getPathname();
        if (!file_exists($path)) {
            return ['normalized' => [], 'raw' => []];
        }

        $firstLine = '';
        $handle = fopen($path, 'r');
        if ($handle !== false) {
            $firstLine = fgets($handle) ?: '';
            fclose($handle);
        }

        if ($firstLine === '') {
            return ['normalized' => [], 'raw' => []];
        }

        $delimiters = [',', ';', "\t", '|'];
        $best = ',';
        $bestCount = -1;
        foreach ($delimiters as $d) {
            $c = substr_count($firstLine, $d);
            if ($c > $bestCount) {
                $bestCount = $c;
                $best = $d;
            }
        }

        $raw = str_getcsv(trim($firstLine), $best);
        $normalized = array_map(fn($h) => $this->normalizeHeader($h), $raw);

        return ['normalized' => $normalized, 'raw' => $raw];
    }

    private function parseCsvRows(string $path): array
    {
        $delimiter = $this->detectCsvDelimiter($path);
        $rows = [];
        if (($handle = fopen($path, 'r')) === false) {
            return [];
        }

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rows[] = $row;
        }

        fclose($handle);
        return $rows;
    }

    private function detectCsvDelimiter(string $path): string
    {
        $default = ',';
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return $default;
        }

        $firstLine = fgets($handle);
        fclose($handle);
        if ($firstLine === false) {
            return $default;
        }

        $delimiters = [',', ';', '\t', '|'];
        $counts = array_map(fn ($delimiter) => substr_count($firstLine, $delimiter), $delimiters);
        arsort($counts);

        return array_key_first($counts) ?: $default;
    }

    private function isBlankRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function castValue(string $column, $value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (in_array($column, ['date', 'expiration_date', 'expiration_date_2'], true)) {
            try {
                return \Carbon\Carbon::parse($value)->format('Y-m-d');
            } catch (\Exception) {
                return null;
            }
        }

        if ($column === 'created_on') {
            try {
                return \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s');
            } catch (\Exception) {
                return null;
            }
        }

        if (in_array($column, ['product_product_standard_qty_pallet', 'quantity', 'qty_in_kgs', 'qty_in_actual_kgs'], true)) {
            $clean = preg_replace('/[^0-9,\.\-]/', '', $value);
            $clean = str_replace([','], ['.'], $clean);
            return is_numeric($clean) ? (float) $clean : 0;
        }

        return $value;
    }
}
