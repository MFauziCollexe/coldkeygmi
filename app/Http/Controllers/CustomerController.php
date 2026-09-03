<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RemembersIndexUrl;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CustomerController extends Controller
{
    use RemembersIndexUrl;

    public function index(Request $request)
    {
        $this->rememberIndexUrl($request, 'customers');

        $query = Customer::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('customer_type')) {
            $query->where('customer_type', $request->customer_type);
        }

        $customers = $query->orderBy('name')->paginate(10)->withQueryString();

        $customers->getCollection()->transform(function ($item) {
            $item->logo_image_url = $item->logo_image ? Storage::url($item->logo_image) : null;
            return $item;
        });

        return Inertia::render('MasterData/Customer/Index', [
            'customers' => $customers,
            'filters' => $request->only(['search', 'customer_type', 'page']),
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
        $fields = $this->buildImportFields($headerInfo['normalized']);
        if (empty(array_filter($fields, fn ($field) => $field !== null))) {
            return response()->json([
                'message' => 'File tidak berisi kolom Customer yang dikenali.',
                'detected_headers' => $headerInfo['normalized'],
                'raw_headers' => $headerInfo['raw'],
            ], 422);
        }

        $processed = 0;
        $inserted = 0;
        $skipped = 0;
        $chunk = [];

        foreach ($this->streamImportRows($file, $fields) as $row) {
            $processed++;
            $chunk[] = $row;
            if (count($chunk) >= 500) {
                $result = $this->insertImportChunk($chunk);
                $inserted += $result['inserted'];
                $skipped += $result['skipped'];
                $chunk = [];
            }
        }

        if (! empty($chunk)) {
            $result = $this->insertImportChunk($chunk);
            $inserted += $result['inserted'];
            $skipped += $result['skipped'];
        }

        if ($processed === 0) {
            return response()->json(['message' => 'File tidak berisi data Customer yang valid.'], 422);
        }

        return response()->json([
            'inserted' => $inserted,
            'skipped' => $skipped,
            'message' => sprintf('%d baris diproses: %d baru, %d dilewati karena customers_id_odoo sudah ada.', $processed, $inserted, $skipped),
        ]);
    }

    public function create()
    {
        return Inertia::render('MasterData/Customer/Create');
    }

    public function store(Request $request)
    {
        $data = $this->validatePayload($request);

        if ($request->hasFile('logo_image')) {
            $data['logo_image'] = $request->file('logo_image')->store('customers', 'public');
        } else {
            $data['logo_image'] = null;
        }

        Customer::create($data);

        return $this->redirectToRememberedIndex($request, 'customers', 'customers.index')
            ->with('success', 'Customer created successfully.');
    }

    public function edit(Customer $customer)
    {
        $customer->logo_image_url = $customer->logo_image ? Storage::url($customer->logo_image) : null;
        return Inertia::render('MasterData/Customer/Edit', [
            'customer' => $customer,
        ]);
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $this->validatePayload($request, $customer->id);

        if ($request->hasFile('logo_image')) {
            if ($customer->logo_image) {
                Storage::disk('public')->delete($customer->logo_image);
            }
            $data['logo_image'] = $request->file('logo_image')->store('customers', 'public');
        } else {
            $data['logo_image'] = $customer->logo_image;
        }

        $customer->update($data);

        return $this->redirectToRememberedIndex($request, 'customers', 'customers.index')
            ->with('success', 'Customer updated successfully.');
    }

    public function destroy(Request $request, Customer $customer)
    {
        if ($customer->logo_image) {
            Storage::disk('public')->delete($customer->logo_image);
        }
        $customer->delete();
        return $this->redirectToRememberedIndex($request, 'customers', 'customers.index')
            ->with('success', 'Customer deleted successfully.');
    }

    private function validatePayload(Request $request, ?int $customerId = null): array
    {
        return $request->validate([
            'customer_type' => 'required|in:individual,company',
            'name' => 'required|string|max:255',
            'address_line_1' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'zip' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'is_active' => 'boolean',
            'logo_image' => 'nullable|image|max:2048',
        ]);
    }

    private function buildImportFields(array $headers): array
    {
        return array_map(fn ($header) => match ($header) {
            'company_type' => 'customer_type',
            'city' => 'city',
            'state' => 'state',
            'country' => 'country',
            'zip' => 'zip',
            'phone' => 'phone',
            'mobile' => 'mobile',
            'email' => 'email',
            'website_link', 'website' => 'website',
            'is_pkp' => 'is_pkp',
            'invoice_transaction_code' => 'invoice_transaction_code',
            'tags', 'tag' => 'tags',
            'id' => 'customers_id_odoo',
            'active' => 'is_active',
            'name' => 'name',
            default => null,
        }, $headers);
    }

    private function streamImportRows(UploadedFile $file, array $fields): \Generator
    {
        $extension = strtolower($file->getClientOriginalExtension());
        if (in_array($extension, ['xls', 'xlsx'], true)) {
            $reader = IOFactory::createReaderForFile($file->getPathname());
            $reader->setReadDataOnly(true);
            $sheet = $reader->load($file->getPathname())->getSheet(0);
            $highestColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestColumn());
            for ($rowIndex = 2; $rowIndex <= $sheet->getHighestRow(); $rowIndex++) {
                $row = [];
                for ($columnIndex = 1; $columnIndex <= $highestColumn; $columnIndex++) {
                    $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex);
                    $row[] = $sheet->getCell($column . $rowIndex)->getValue();
                }
                if (! $this->isBlankImportRow($row)) {
                    yield $this->mapImportRow($row, $fields);
                }
            }
            return;
        }

        $handle = fopen($file->getPathname(), 'r');
        if ($handle === false) {
            return;
        }
        $delimiter = $this->detectImportDelimiter($file->getPathname());
        fgetcsv($handle, 0, $delimiter);
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (! $this->isBlankImportRow($row)) {
                yield $this->mapImportRow($row, $fields);
            }
        }
        fclose($handle);
    }

    private function mapImportRow(array $row, array $fields): array
    {
        $data = [
            'customer_type' => 'company',
            'city' => null, 'state' => null, 'country' => null, 'zip' => null,
            'phone' => null, 'mobile' => null, 'email' => null, 'website' => null,
            'is_pkp' => false, 'invoice_transaction_code' => null, 'tags' => null,
            'customers_id_odoo' => null, 'is_active' => true, 'name' => null,
        ];

        foreach ($fields as $index => $field) {
            if ($field === null) {
                continue;
            }
            $value = trim((string) ($row[$index] ?? ''));
            if (in_array($field, ['is_pkp', 'is_active'], true)) {
                $data[$field] = in_array(strtolower($value), ['1', 'true', 'yes', 'y', 'ya', 'active'], true);
            } elseif ($field === 'customer_type') {
                $data[$field] = strtolower($value) === 'individual' ? 'individual' : 'company';
            } else {
                $data[$field] = $value === '' ? null : $value;
            }
        }

        return $data;
    }

    private function insertImportChunk(array $chunk): array
    {
        $ids = array_values(array_filter(array_column($chunk, 'customers_id_odoo'), fn ($id) => $id !== null && $id !== ''));
        $existingIds = Customer::whereIn('customers_id_odoo', $ids)->pluck('customers_id_odoo')->flip();
        $seenIds = [];
        $toInsert = [];
        $skipped = 0;

        foreach ($chunk as $row) {
            $id = $row['customers_id_odoo'];
            if ($id !== null && $id !== '' && ($existingIds->has($id) || isset($seenIds[$id]))) {
                $skipped++;
                continue;
            }
            if ($id !== null && $id !== '') {
                $seenIds[$id] = true;
            }
            $toInsert[] = $row;
        }

        if (! empty($toInsert)) {
            Customer::insert($toInsert);
        }

        return ['inserted' => count($toInsert), 'skipped' => $skipped];
    }

    private function getNormalizedHeadersFromFile(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        if (in_array($extension, ['xls', 'xlsx'], true)) {
            $sheet = IOFactory::load($file->getPathname())->getSheet(0);
            $raw = $sheet->rangeToArray('A1:' . $sheet->getHighestColumn() . '1', null, true, true, false)[0] ?? [];
        } else {
            $handle = fopen($file->getPathname(), 'r');
            $line = $handle !== false ? (fgets($handle) ?: '') : '';
            if ($handle !== false) fclose($handle);
            $delimiter = $this->detectImportDelimiter($file->getPathname());
            $raw = $line === '' ? [] : str_getcsv(trim($line), $delimiter);
        }
        $normalized = array_map(fn ($header) => $this->normalizeImportHeader($header), $raw);
        return ['normalized' => $normalized, 'raw' => $raw];
    }

    private function normalizeImportHeader($value): string
    {
        return trim(preg_replace('/[^a-z0-9]+/u', '_', strtolower((string) $value)), '_');
    }

    private function detectImportDelimiter(string $path): string
    {
        $handle = fopen($path, 'r');
        $line = $handle !== false ? (fgets($handle) ?: '') : '';
        if ($handle !== false) fclose($handle);
        $delimiters = [',', ';', "\t", '|'];
        usort($delimiters, fn ($a, $b) => substr_count($line, $b) <=> substr_count($line, $a));
        return $delimiters[0];
    }

    private function isBlankImportRow(array $row): bool
    {
        return ! array_filter($row, fn ($value) => trim((string) $value) !== '');
    }
}
