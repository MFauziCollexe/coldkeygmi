<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RemembersIndexUrl;
use App\Models\ProcurementCategory;
use App\Models\ProcurementMasterItem;
use App\Models\StockCardItemType;
use App\Models\StockCardUnit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ProcurementMasterItemController extends Controller
{
    use RemembersIndexUrl;

    public function index(Request $request)
    {
        $this->rememberIndexUrl($request, 'procurement-master-items');

        $query = ProcurementMasterItem::query();

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('item_code', 'like', "%{$search}%")
                    ->orWhere('item_name', 'like', "%{$search}%")
                    ->orWhere('description_of_goods', 'like', "%{$search}%")
                    ->orWhere('item_type', 'like', "%{$search}%");
            });
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->input('is_active') === '1');
        }

        return Inertia::render('Procurement/MasterItem/Index', [
            'items' => $query->orderBy('item_code')->paginate(10)->withQueryString(),
            'filters' => $request->only(['search', 'is_active', 'page']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Procurement/MasterItem/Create', [
            'itemTypeOptions' => $this->itemTypeOptions(),
            'unitOptions' => $this->unitOptions(),
            'categoryOptions' => $this->categoryOptions(),
            'generatedItemCode' => $this->generateItemCode(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['item_code'] = $this->generateItemCode();

        ProcurementMasterItem::create($data);

        return $this->redirectToRememberedIndex($request, 'procurement-master-items', 'procurement-master-item.index')
            ->with('success', 'Master item berhasil dibuat.');
    }

    public function update(Request $request, ProcurementMasterItem $procurementMasterItem)
    {
        $data = $this->validated($request);

        $procurementMasterItem->update($data);

        return $this->redirectToRememberedIndex($request, 'procurement-master-items', 'procurement-master-item.index')
            ->with('success', 'Master item berhasil diperbarui.');
    }

    private function generateItemCode(): string
    {
        $lastItem = ProcurementMasterItem::orderBy('id', 'desc')->first();
        $lastCode = $lastItem ? (int) substr($lastItem->item_code, 3) : 0;
        $newNumber = $lastCode + 1;
        return 'ITM' . str_pad((string) $newNumber, 5, '0', STR_PAD_LEFT);
    }

    public function destroy(Request $request, ProcurementMasterItem $procurementMasterItem)
    {
        $procurementMasterItem->delete();

        return $this->redirectToRememberedIndex($request, 'procurement-master-items', 'procurement-master-item.index')
            ->with('success', 'Master item berhasil dihapus.');
    }

    private function validated(Request $request, ?ProcurementMasterItem $item = null): array
    {
        $rules = [
            'item_name' => ['required', 'string', 'max:255'],
            'description_of_goods' => ['required', 'string'],
            'item_type' => ['nullable', 'string', 'max:255', 'exists:stock_card_item_types,name'],
            'category_id' => ['nullable', 'exists:procurement_categories,id'],
            'unit' => ['required', 'string', 'max:100', 'exists:stock_card_units,name'],
            'is_active' => ['boolean'],
        ];

        $validated = $request->validate($rules);

        return [
            'item_name' => trim((string) $validated['item_name']),
            'description_of_goods' => trim((string) $validated['description_of_goods']),
            'item_type' => isset($validated['item_type']) && $validated['item_type'] !== ''
                ? trim((string) $validated['item_type'])
                : null,
            'category_id' => $validated['category_id'] ?? null,
            'unit' => trim((string) $validated['unit']),
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ];
    }

    private function itemTypeOptions()
    {
        return StockCardItemType::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (StockCardItemType $itemType) => [
                'id' => $itemType->id,
                'name' => $itemType->name,
            ])
            ->values();
    }

    private function unitOptions()
    {
        return StockCardUnit::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (StockCardUnit $unit) => [
                'id' => $unit->id,
                'name' => $unit->name,
            ])
            ->values();
    }

    private function categoryOptions()
    {
        return ProcurementCategory::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (ProcurementCategory $category) => [
                'id' => $category->id,
                'name' => $category->name,
            ])
            ->values();
    }
}
