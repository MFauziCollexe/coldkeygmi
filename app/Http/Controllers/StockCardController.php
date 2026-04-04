<?php

namespace App\Http\Controllers;

use App\Models\StockCardItem;
use App\Models\StockCardItemType;
use App\Models\StockCardRequest;
use App\Models\StockCardStockIn;
use App\Models\StockCardUnit;
use App\Support\AccessRuleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class StockCardController extends Controller
{
    private const ACCESS_MODULE = 'stock_card';

    public function masterIndex(Request $request): Response
    {
        $search = trim((string) $request->string('q'));
        $canManageMaster = $this->accessRules()->allows($request->user(), self::ACCESS_MODULE, 'manage_master');

        $items = $this->baseItemsQuery($search, false)
            ->orderBy('name')
            ->get();

        return Inertia::render('MasterData/StockCard/Index', [
            'items' => $items->map(fn (StockCardItem $item) => $this->mapItemForList($item))->values(),
            'filters' => [
                'q' => $search,
            ],
            'canManageMaster' => $canManageMaster,
            'itemTypes' => StockCardItemType::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->pluck('name')
                ->values(),
            'units' => StockCardUnit::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->pluck('name')
                ->values(),
        ]);
    }

    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('q'));
        $canAddStock = $this->accessRules()->allows($request->user(), self::ACCESS_MODULE, 'add_stock');
        $canRequestStock = $this->accessRules()->allows($request->user(), self::ACCESS_MODULE, 'request_stock');
        $canViewHistory = $this->accessRules()->allows($request->user(), self::ACCESS_MODULE, 'view_history');

        $items = $this->baseItemsQuery($search)
            ->orderBy('name')
            ->get();

        $selectedItemId = (int) ($request->integer('item_id') ?: ($items->first()->id ?? 0));
        $selectedItem = $items->firstWhere('id', $selectedItemId);

        return Inertia::render('GMISL/Utility/StockCard/Index', [
            'items' => $items->map(fn (StockCardItem $item) => $this->mapItemForList($item))->values(),
            'selectedItem' => $selectedItem ? $this->mapItemForList($selectedItem) : null,
            'cardRows' => $canViewHistory && $selectedItem ? $this->buildCardRows($selectedItem) : [],
            'filters' => [
                'q' => $search,
                'item_id' => $selectedItemId ?: '',
            ],
            'abilities' => [
                'add_stock' => $canAddStock,
                'request_stock' => $canRequestStock,
                'view_history' => $canViewHistory,
            ],
            'meta' => [
                'total_items' => $items->count(),
                'low_stock_items' => $items->filter(function (StockCardItem $item) {
                    return (float) $item->current_stock <= (float) $item->minimum_stock;
                })->count(),
                'document_no' => 'GMI/FR/HRGA/01',
                'revision_no' => '00',
                'effective_date' => '27 Oktober 2025',
                'page' => '1 of 1',
                'classification' => 'Confidential',
            ],
        ]);
    }

    public function storeItem(Request $request): RedirectResponse
    {
        abort_unless(
            $this->accessRules()->allows($request->user(), self::ACCESS_MODULE, 'manage_master'),
            403
        );

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'item_type' => ['required', 'string', 'max:255', 'exists:stock_card_item_types,name'],
            'unit' => ['required', 'string', 'max:50', 'exists:stock_card_units,name'],
            'minimum_stock' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
        ]);

        StockCardItem::create([
            'item_code' => $this->generateItemCode(),
            'name' => $data['name'],
            'item_type' => $data['item_type'],
            'unit' => $data['unit'],
            'minimum_stock' => $data['minimum_stock'] ?? 0,
            'description' => $data['description'] ?? null,
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        return redirect()
            ->route('stock-card.master.index')
            ->with('success', 'Master barang berhasil ditambahkan.');
    }

    public function storeStockIn(Request $request): RedirectResponse
    {
        abort_unless(
            $this->accessRules()->allows($request->user(), self::ACCESS_MODULE, 'add_stock'),
            403
        );

        $data = $request->validate([
            'stock_card_item_id' => ['required', 'exists:stock_card_items,id'],
            'transaction_date' => ['required', 'date'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($request, $data) {
            StockCardStockIn::create([
                'stock_card_item_id' => $data['stock_card_item_id'],
                'transaction_date' => $data['transaction_date'],
                'quantity' => $data['quantity'],
                'notes' => $data['notes'] ?? null,
                'created_by' => $request->user()?->id,
            ]);

            $this->recalculateItemStock((int) $data['stock_card_item_id']);
        });

        return redirect()
            ->route('stock-card.index', ['item_id' => $data['stock_card_item_id']])
            ->with('success', 'Stock masuk berhasil ditambahkan.');
    }

    public function storeRequest(Request $request): RedirectResponse
    {
        abort_unless(
            $this->accessRules()->allows($request->user(), self::ACCESS_MODULE, 'request_stock'),
            403
        );

        $data = $request->validate([
            'stock_card_item_id' => ['required', 'exists:stock_card_items,id'],
            'request_date' => ['required', 'date'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'requested_by_name' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $item = StockCardItem::findOrFail($data['stock_card_item_id']);
        $projectedStock = $this->calculateProjectedStock($item->id, $data['request_date'], (float) $data['quantity']);

        if ($projectedStock < 0) {
            return redirect()
                ->route('stock-card.index', ['item_id' => $item->id])
                ->withErrors(['request_quantity' => 'Stock tidak cukup untuk permintaan ini.']);
        }

        DB::transaction(function () use ($request, $data) {
            StockCardRequest::create([
                'stock_card_item_id' => $data['stock_card_item_id'],
                'request_date' => $data['request_date'],
                'quantity' => $data['quantity'],
                'requested_by_name' => $data['requested_by_name'],
                'notes' => $data['notes'] ?? null,
                'created_by' => $request->user()?->id,
            ]);

            $this->recalculateItemStock((int) $data['stock_card_item_id']);
        });

        return redirect()
            ->route('stock-card.index', ['item_id' => $item->id])
            ->with('success', 'Permintaan stock berhasil disimpan.');
    }

    private function baseItemsQuery(string $search = '', bool $activeOnly = true)
    {
        return StockCardItem::query()
            ->when($activeOnly, fn ($query) => $query->where('is_active', true))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('item_type', 'like', "%{$search}%")
                        ->orWhere('unit', 'like', "%{$search}%")
                        ->orWhere('item_code', 'like', "%{$search}%");
                });
            });
    }

    private function mapItemForList(StockCardItem $item): array
    {
        return [
            'id' => $item->id,
            'item_code' => $item->item_code,
            'name' => $item->name,
            'item_type' => $item->item_type,
            'unit' => $item->unit,
            'current_stock' => $this->formatQuantity($item->current_stock),
            'minimum_stock' => $this->formatQuantity($item->minimum_stock),
            'description' => $item->description,
            'is_active' => (bool) $item->is_active,
        ];
    }

    private function buildCardRows(StockCardItem $item): array
    {
        $rows = collect();

        foreach ($item->stockIns()->with('creator:id,name')->orderBy('transaction_date')->orderBy('id')->get() as $entry) {
            $rows->push([
                'date' => optional($entry->transaction_date)->format('d/m/Y'),
                'sort_date' => optional($entry->transaction_date)->format('Y-m-d') ?? '',
                'sort_id' => 'in-' . $entry->id,
                'incoming' => (float) $entry->quantity,
                'outgoing' => 0,
                'note' => $entry->notes ?: optional($entry->creator)->name ?: '-',
                'type' => 'stock_in',
            ]);
        }

        foreach ($item->stockRequests()->with('creator:id,name')->orderBy('request_date')->orderBy('id')->get() as $entry) {
            $note = trim(($entry->requested_by_name ?: '') . ($entry->notes ? ' - ' . $entry->notes : ''));

            $rows->push([
                'date' => optional($entry->request_date)->format('d/m/Y'),
                'sort_date' => optional($entry->request_date)->format('Y-m-d') ?? '',
                'sort_id' => 'out-' . $entry->id,
                'incoming' => 0,
                'outgoing' => (float) $entry->quantity,
                'note' => $note ?: optional($entry->creator)->name ?: '-',
                'type' => 'request',
            ]);
        }

        $runningStock = 0;

        return $rows
            ->sortBy([
                ['sort_date', 'asc'],
                ['sort_id', 'asc'],
            ])
            ->values()
            ->map(function (array $row) use (&$runningStock, $item) {
                $runningStock += $row['incoming'];
                $runningStock -= $row['outgoing'];

                return [
                    'date' => $row['date'],
                    'type' => $row['type'],
                    'incoming' => $row['incoming'] > 0 ? $this->formatQuantity($row['incoming']) . ' ' . $item->unit : '-',
                    'outgoing' => $row['outgoing'] > 0 ? $this->formatQuantity($row['outgoing']) . ' ' . $item->unit : '-',
                    'balance' => $this->formatQuantity($runningStock) . ' ' . $item->unit,
                    'note' => $row['note'],
                ];
            })
            ->all();
    }

    private function recalculateItemStock(int $itemId): void
    {
        $item = StockCardItem::findOrFail($itemId);

        $totalIn = (float) $item->stockIns()->sum('quantity');
        $totalOut = (float) $item->stockRequests()->sum('quantity');

        $item->update([
            'current_stock' => max($totalIn - $totalOut, 0),
            'updated_by' => auth()->id(),
        ]);
    }

    private function calculateProjectedStock(int $itemId, string $requestDate, float $requestQty): float
    {
        $date = Carbon::parse($requestDate)->format('Y-m-d');

        $totalIn = (float) StockCardStockIn::query()
            ->where('stock_card_item_id', $itemId)
            ->whereDate('transaction_date', '<=', $date)
            ->sum('quantity');

        $totalOut = (float) StockCardRequest::query()
            ->where('stock_card_item_id', $itemId)
            ->whereDate('request_date', '<=', $date)
            ->sum('quantity');

        return $totalIn - $totalOut - $requestQty;
    }

    private function generateItemCode(): string
    {
        $lastId = (int) StockCardItem::max('id') + 1;

        return 'SC-' . str_pad((string) $lastId, 4, '0', STR_PAD_LEFT);
    }

    private function formatQuantity(float|string $value): string
    {
        $formatted = number_format((float) $value, 2, '.', '');

        return rtrim(rtrim($formatted, '0'), '.');
    }

    private function accessRules(): AccessRuleService
    {
        return app(AccessRuleService::class);
    }
}
