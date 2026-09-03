<?php

namespace App\Http\Controllers;

use App\Models\TProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProductsController extends Controller
{
    public function index(Request $request)
    {
        $query = TProduct::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('internal_reference', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%")
                    ->orWhere('customer', 'like', "%{$search}%")
                    ->orWhere('product_category', 'like', "%{$search}%");
            });
        }

        $products = $query->orderBy('name')->paginate(10)->withQueryString();

        return Inertia::render('MasterData/Products/Index', [
            'products' => $products,
            'filters' => $request->only(['search', 'page']),
        ]);
    }

    public function import(Request $request): JsonResponse
    {
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
                'message' => 'File tidak berisi kolom yang dikenali untuk data Products.',
                'detected_headers' => $headerInfo['normalized'],
                'raw_headers' => $headerInfo['raw'],
            ], 422);
        }

        $totalProcessed = 0;
        $insertedCount = 0;
        $skippedCount = 0;
        $chunk = [];
        $chunkSize = 500;

        foreach ($this->streamParsedRows($file, $fields) as $row) {
            $totalProcessed++;
            $chunk[] = $row;

            if (count($chunk) >= $chunkSize) {
                $result = $this->upsertChunk($chunk);
                $insertedCount += $result['inserted'];
                $skippedCount += $result['skipped'];
                $chunk = [];
            }
        }

        if (! empty($chunk)) {
            $result = $this->upsertChunk($chunk);
            $insertedCount += $result['inserted'];
            $skippedCount += $result['skipped'];
        }

        if ($totalProcessed === 0) {
            return response()->json([
                'message' => 'File tidak berisi data yang valid setelah parsing.',
                'detected_headers' => $headerInfo['normalized'],
                'raw_headers' => $headerInfo['raw'],
            ], 422);
        }

        return response()->json([
            'inserted' => $insertedCount,
            'skipped' => $skippedCount,
            'message' => sprintf('%d baris diproses: %d baru, %d dilewati karena products_id sudah ada.', $totalProcessed, $insertedCount, $skippedCount),
        ]);
    }

    private function streamParsedRows(UploadedFile $file, array $fields): \Generator
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (in_array($extension, ['xls', 'xlsx'], true)) {
            yield from $this->streamSpreadsheetRows($file->getPathname(), $fields);
            return;
        }

        $delimiter = $this->detectCsvDelimiter($file->getPathname());
        yield from $this->streamCsvRows($file->getPathname(), $delimiter, $fields);
    }

    private function streamSpreadsheetRows(string $path, array $fields): \Generator
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

    private function streamCsvRows(string $path, string $delimiter, array $fields): \Generator
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
            'customer' => null,
            'barcode' => null,
            'internal_reference' => null,
            'name' => null,
            'product_type' => null,
            'product_category' => null,
            'unit_of_measure' => null,
            'standard_qty_pallet' => 0,
            'pack_size_cf' => null,
            'length' => 0,
            'width' => 0,
            'height' => 0,
            'weight' => 0,
            'layer_stack' => null,
            'volume' => 0,
            'track_inventory' => null,
            'valuation_by_lot_serial_number' => null,
            'use_expiration_date' => null,
            'tags_name' => null,
            'routes' => null,
            'customer_id' => null,
            'optional_products_external_id' => null,
            'optional_products_id' => null,
            'products_external_id' => null,
            'products_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], $parsed);
    }

    private function buildHeaderFields(array $headers): array
    {
        $fields = [];

        foreach ($headers as $header) {
            $fields[] = match ($header) {
                'customer' => 'customer',
                'barcode' => 'barcode',
                'internal_reference', 'default_code' => 'internal_reference',
                'name' => 'name',
                'product_type' => 'product_type',
                'product_category' => 'product_category',
                'unit_of_measure' => 'unit_of_measure',
                'standard_qty_pallet', 'standar_qty_pallet' => 'standard_qty_pallet',
                'pack_size_cf', 'pack_size' => 'pack_size_cf',
                'length' => 'length',
                'width' => 'width',
                'height' => 'height',
                'weight' => 'weight',
                'layer_stack' => 'layer_stack',
                'volume' => 'volume',
                'track_inventory' => 'track_inventory',
                'valuation_by_lot_serial_number' => 'valuation_by_lot_serial_number',
                'use_expiration_date' => 'use_expiration_date',
                'tags_name', 'tags' => 'tags_name',
                'routes' => 'routes',
                'customer_id' => 'customer_id',
                'optional_products_external_id' => 'optional_products_external_id',
                'optional_products_id' => 'optional_products_id',
                'products_external_id' => 'products_external_id',
                'products_id' => 'products_id',
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

        if (in_array($column, ['standard_qty_pallet', 'length', 'width', 'height', 'weight', 'volume'], true)) {
            $clean = preg_replace('/[^0-9,\.\-]/', '', $value);
            $clean = str_replace([','], ['.'], $clean);
            return is_numeric($clean) ? (float) $clean : 0;
        }

        return $value;
    }

    private function upsertChunk(array $chunk): array
    {
        $inserted = 0;
        $skipped = 0;

        $withId = [];
        $withoutId = [];

        foreach ($chunk as $row) {
            $pid = $row['products_id'] ?? null;
            if ($pid !== null && trim((string) $pid) !== '') {
                $withId[] = $row;
            } else {
                $withoutId[] = $row;
            }
        }

        if (! empty($withoutId)) {
            TProduct::insert($withoutId);
            $inserted += count($withoutId);
        }

        if (! empty($withId)) {
            $existingIds = TProduct::whereIn('products_id', array_column($withId, 'products_id'))
                ->pluck('products_id')
                ->flip();

            $toInsert = [];
            $seenIds = [];

            foreach ($withId as $row) {
                $productId = $row['products_id'];
                if ($existingIds->has($productId) || isset($seenIds[$productId])) {
                    $skipped++;
                } else {
                    $toInsert[] = $row;
                    $seenIds[$productId] = true;
                }
            }

            if (! empty($toInsert)) {
                TProduct::insert($toInsert);
                $inserted += count($toInsert);
            }

        }

        return ['inserted' => $inserted, 'skipped' => $skipped];
    }
}
