<?php

namespace App\Http\Controllers\Portal\Odoo;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\TProduct;
use App\Support\AccessRuleService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\IOFactory;

class StockOnHandController extends Controller
{
    private const COLUMNS = [
        'owner',
        'location',
        'location_name',
        'storage_category',
        'product_product_name',
        'unit_of_measure',
        'product_product_standard_qty_pallet',
        'quantity',
        'inventoried_quantity',
        'available_quantity',
        'qty_in_actual_kgs',
        'lot_serial_number',
        'expiration_date',
        'incoming_date',
        'last_updated_on',
        'product_pack_size_cf',
        'product_category',
        'location_room_type',
        'package',
    ];

    public function index(Request $request): Response
    {
        $user = Auth::user();
        $userOwnerId = $user && !empty($user->from_owner_id) ? (string) $user->from_owner_id : null;

        $owners = Customer::query()
            ->whereNotNull('customers_id_odoo')
            ->where('customers_id_odoo', '!=', '')
            ->orderBy('name')
            ->get(['customers_id_odoo', 'name'])
            ->map(fn ($customer) => [
                'owner_id' => (string) $customer->customers_id_odoo,
                'owner_name' => $customer->name,
            ])
            ->values()
            ->all();

        $products = TProduct::query()
            ->whereNotNull('internal_reference')
            ->where('internal_reference', '!=', '')
            ->orderBy('internal_reference')
            ->get(['internal_reference', 'name', 'customer_id'])
            ->map(fn ($product) => [
                'owner_id' => $product->customer_id !== null && $product->customer_id !== '' ? (string) $product->customer_id : null,
                'product_id' => $product->internal_reference,
                'default_code' => $product->internal_reference,
                'product_name' => $product->name,
            ])
            ->values()
            ->all();

        $locations = DB::table('t_location')
            ->select('location')
            ->whereNotNull('location')
            ->where('location', '!=', '')
            ->orderBy('location')
            ->pluck('location')
            ->filter()
            ->map(fn ($location) => explode('/', (string) $location)[0])
            ->unique()
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

        $targetLocation = $request->input('location');
        if ($targetLocation !== null && $targetLocation !== '') {
            $targetLocation = (string) $targetLocation;
        } else {
            $targetLocation = null;
        }

        $hasFilters = $request->query('owner_id') !== null
            || $request->query('product_id') !== null
            || $request->query('location') !== null;

        $rows = [];
        $totalRows = 0;

        if ($hasFilters) {
            $query = DB::table('t_location');

            if ($userOwnerId !== null) {
                $query->where('owner_id', $userOwnerId);
            } elseif ($selectedOwnerId !== null && $selectedOwnerId !== '') {
                $query->where('owner_id', $selectedOwnerId);
            }

            if ($targetProductId !== null) {
                $query->where('product_internal_reference', $targetProductId);
            }

            if ($targetLocation !== null) {
                $query->where(function ($query) use ($targetLocation) {
                    $query->where('location', $targetLocation)
                        ->orWhere('location', 'LIKE', $targetLocation . '/%');
                });
            }

            $rows = $query
                ->select(self::COLUMNS)
                ->orderBy('location')
                ->orderBy('product_internal_reference')
                ->orderBy('lot_serial_number')
                ->get()
                ->map(fn ($row) => (array) $row)
                ->all();

            $totalRows = count($rows);
        }

        $page = max(1, (int) $request->query('page', 1));
        $perPage = 25;
        $page = min($page, max(1, (int) ceil($totalRows / $perPage)));

        return Inertia::render('Portal/Odoo/SOH/Index', [
            'rows' => $rows,
            'owners' => $owners,
            'products' => $products,
            'locations' => $locations,
            'selectedOwnerId' => $selectedOwnerId,
            'targetProductId' => $targetProductId,
            'targetLocation' => $targetLocation,
            'applied' => $hasFilters,
            'currentPage' => $page,
            'perPage' => $perPage,
            'totalRows' => $totalRows,
            'canImport' => $this->accessRules()->allows($user, 'portal.odoo.soh', 'import'),
        ]);
    }

    public function import(Request $request): JsonResponse
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (! $this->accessRules()->allows($user, 'portal.odoo.soh', 'import')) {
            abort(403, 'Anda tidak memiliki akses untuk mengimpor data SOH.');
        }

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,xls,xlsx'],
        ]);

        $file = $request->file('file');
        if (! $file instanceof UploadedFile) {
            return response()->json(['message' => 'File upload tidak valid.'], 422);
        }

        $headerInfo = $this->getNormalizedHeadersFromFile($file);
        $fields = $this->buildHeaderFields($headerInfo['normalized']);
        $mapped = array_filter($fields, fn ($field) => $field !== null);

        if (empty($mapped)) {
            return response()->json([
                'message' => 'File tidak berisi kolom yang dikenali untuk data Stock On Hand.',
                'detected_headers' => $headerInfo['normalized'],
                'raw_headers' => $headerInfo['raw'],
            ], 422);
        }

        $rowGenerator = $this->streamParsedLocationRows($file, $fields);
        $parsedCount = 0;
        $chunk = [];
        $chunkSize = 500;
        $tableCleared = false;

        foreach ($rowGenerator as $row) {
            if (! $tableCleared) {
                DB::table('t_location')->delete();
                $tableCleared = true;
            }

            $parsedCount++;
            $chunk[] = $row;

            if (count($chunk) >= $chunkSize) {
                DB::table('t_location')->insert($chunk);
                $chunk = [];
            }
        }

        if (! empty($chunk)) {
            DB::table('t_location')->insert($chunk);
        }

        if ($parsedCount === 0) {
            return response()->json([
                'message' => 'File tidak berisi data yang valid setelah parsing.',
                'detected_headers' => $headerInfo['normalized'],
                'raw_headers' => $headerInfo['raw'],
            ], 422);
        }

        return response()->json([
            'inserted' => $parsedCount,
            'message' => sprintf('%d baris berhasil diimport ke Stock On Hand.', $parsedCount),
        ]);
    }

    private function streamParsedLocationRows(UploadedFile $file, array $fields): \Generator
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

        for ($rowIndex = 2; $rowIndex <= $highestRow; ++$rowIndex) {
            $row = [];
            for ($colIndex = 1; $colIndex <= $highestColumnIndex; ++$colIndex) {
                $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                $row[] = $sheet->getCell($columnLetter . $rowIndex)->getValue();
            }

            if ($this->isBlankRow($row)) {
                continue;
            }

            yield $this->mapRowToFields($row, $fields);
        }
    }

    private function streamCsvDataRows(string $path, string $delimiter, array $fields): \Generator
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return;
        }

        fgetcsv($handle, 0, $delimiter);

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if ($this->isBlankRow($row)) {
                continue;
            }

            yield $this->mapRowToFields($row, $fields);
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
            'customer_reference' => null,
            'owner' => null,
            'location' => null,
            'location_parent_location' => null,
            'location_name' => null,
            'storage_category' => null,
            'product_display_name' => null,
            'product_internal_reference' => null,
            'product_product_name' => null,
            'unit_of_measure' => null,
            'product_product_standard_qty_pallet' => 0,
            'quantity' => 0,
            'inventoried_quantity' => 0,
            'available_quantity' => 0,
            'qty_in_actual_kgs' => 0,
            'lot_serial_number' => null,
            'expiration_date' => null,
            'incoming_date' => null,
            'last_updated_on' => null,
            'product_pack_size_cf' => null,
            'product_category' => null,
            'product_barcode' => null,
            'location_location_type' => null,
            'location_room_type' => null,
            'package' => null,
            'display_name' => null,
            'owner_id' => null,
            'product' => null,
            'product_name' => null,
        ], $parsed);
    }

    private function buildHeaderFields(array $headers): array
    {
        $fields = [];

        foreach ($headers as $header) {
            $fields[] = match ($header) {
                'customer_reference', 'product_customer_reference' => 'customer_reference',
                'owner' => 'owner',
                'location' => 'location',
                'location_parent_location' => 'location_parent_location',
                'location_name', 'location_location_name' => 'location_name',
                'storage_category', 'source_location_storage_category' => 'storage_category',
                'product_display_name' => 'product_display_name',
                'product_internal_reference' => 'product_internal_reference',
                'product_product_name' => 'product_product_name',
                'unit_of_measure' => 'unit_of_measure',
                'product_product_standard_qty_pallet',
                'product_product_standar_qty_pallet',
                'product_standar_qty_pallet' => 'product_product_standard_qty_pallet',
                'quantity' => 'quantity',
                'inventoried_quantity' => 'inventoried_quantity',
                'available_quantity' => 'available_quantity',
                'qty_in_actual_kgs', 'qty_actual_kgs' => 'qty_in_actual_kgs',
                'lot_serial_number' => 'lot_serial_number',
                'expiration_date' => 'expiration_date',
                'incoming_date' => 'incoming_date',
                'last_updated_on' => 'last_updated_on',
                'product_pack_size_cf', 'product_pack_size' => 'product_pack_size_cf',
                'product_category' => 'product_category',
                'product_barcode' => 'product_barcode',
                'location_location_type', 'location_type' => 'location_location_type',
                'location_room_type', 'room_type' => 'location_room_type',
                'package', 'destination_package' => 'package',
                'display_name' => 'display_name',
                'owner_id' => 'owner_id',
                'product' => 'product',
                'product_name' => 'product_name',
                default => null,
            };
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
                $normalized = array_map(fn ($h) => $this->normalizeHeader($h), $raw);

                return ['normalized' => $normalized, 'raw' => $raw];
            } catch (\Throwable $e) {
                return ['normalized' => [], 'raw' => []];
            }
        }

        $path = $file->getPathname();
        if (! file_exists($path)) {
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
        $normalized = array_map(fn ($h) => $this->normalizeHeader($h), $raw);

        return ['normalized' => $normalized, 'raw' => $raw];
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

        $delimiters = [',', ';', "\t", '|'];
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

        if (in_array($column, ['expiration_date', 'incoming_date'], true)) {
            try {
                return \Carbon\Carbon::parse($value)->format('Y-m-d');
            } catch (\Exception) {
                return null;
            }
        }

        if ($column === 'last_updated_on') {
            try {
                return \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s');
            } catch (\Exception) {
                return null;
            }
        }

        if (in_array($column, [
            'product_product_standard_qty_pallet',
            'quantity',
            'inventoried_quantity',
            'available_quantity',
            'qty_in_actual_kgs',
        ], true)) {
            $clean = preg_replace('/[^0-9,\.\-]/', '', $value);
            $clean = str_replace([','], ['.'], $clean);
            return is_numeric($clean) ? (float) $clean : 0;
        }

        return $value;
    }

    private function accessRules(): AccessRuleService
    {
        return app(AccessRuleService::class);
    }
}
