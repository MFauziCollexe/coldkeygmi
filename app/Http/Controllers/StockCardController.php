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
use Illuminate\Support\Collection;
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
        $itemIdInput = trim((string) $request->input('item_id', 'all'));
        $canAddStock = $this->accessRules()->allows($request->user(), self::ACCESS_MODULE, 'add_stock');
        $canRequestStock = $this->accessRules()->allows($request->user(), self::ACCESS_MODULE, 'request_stock');
        $canApproveRequest = $this->accessRules()->allows($request->user(), self::ACCESS_MODULE, 'approve_request');
        $canViewHistory = $this->accessRules()->allows($request->user(), self::ACCESS_MODULE, 'view_history');

        $items = $this->baseItemsQuery($search)
            ->orderBy('name')
            ->get();

        $selectedItemId = ctype_digit($itemIdInput) ? (int) $itemIdInput : null;
        $selectedItem = $selectedItemId ? $items->firstWhere('id', $selectedItemId) : null;

        return Inertia::render('GMISL/Utility/StockCard/Index', [
            'items' => $items->map(fn (StockCardItem $item) => $this->mapItemForList($item))->values(),
            'selectedItem' => $selectedItem ? $this->mapItemForList($selectedItem) : null,
            'cardRows' => $canViewHistory
                ? ($selectedItem ? $this->buildCardRows($selectedItem) : $this->buildAllCardRows($items))
                : [],
            'filters' => [
                'q' => $search,
                'item_id' => $selectedItem ? (string) $selectedItem->id : 'all',
            ],
            'abilities' => [
                'add_stock' => $canAddStock,
                'request_stock' => $canRequestStock,
                'approve_request' => $canApproveRequest,
                'view_history' => $canViewHistory,
            ],
            'pendingRequests' => StockCardRequest::query()
                ->with(['item:id,name,unit', 'creator:id,name'])
                ->where('status', 'pending')
                ->orderBy('request_date')
                ->orderBy('id')
                ->get()
                ->map(fn (StockCardRequest $requestItem) => [
                    'id' => $requestItem->id,
                    'request_date' => optional($requestItem->request_date)->format('d/m/Y'),
                    'item_name' => $requestItem->item?->name ?? '-',
                    'quantity' => $this->formatQuantity($requestItem->quantity) . ' ' . ($requestItem->item?->unit ?? ''),
                    'requested_by_name' => $requestItem->requested_by_name,
                    'notes' => $requestItem->notes,
                    'creator_name' => $requestItem->creator?->name,
                ])
                ->values(),
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
            'minimum_stock' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
        ]);

        StockCardItem::create([
            'item_code' => $this->generateItemCode(),
            'name' => $data['name'],
            'item_type' => $data['item_type'],
            'unit' => $data['unit'],
            'minimum_stock' => (int) ($data['minimum_stock'] ?? 0),
            'description' => $data['description'] ?? null,
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        return redirect()
            ->route('stock-card.master.index')
            ->with('success', 'Master barang berhasil ditambahkan.');
    }

    public function updateItem(Request $request, StockCardItem $stockCardItem): RedirectResponse
    {
        abort_unless(
            $this->accessRules()->allows($request->user(), self::ACCESS_MODULE, 'manage_master'),
            403
        );

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'item_type' => ['required', 'string', 'max:255', 'exists:stock_card_item_types,name'],
            'unit' => ['required', 'string', 'max:50', 'exists:stock_card_units,name'],
            'minimum_stock' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $stockCardItem->update([
            'name' => $data['name'],
            'item_type' => $data['item_type'],
            'unit' => $data['unit'],
            'minimum_stock' => (int) ($data['minimum_stock'] ?? 0),
            'description' => $data['description'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? false),
            'updated_by' => $request->user()?->id,
        ]);

        return redirect()
            ->route('stock-card.master.index')
            ->with('success', 'Master barang berhasil diperbarui.');
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
            'quantity' => ['required', 'integer', 'gt:0'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($request, $data) {
            StockCardStockIn::create([
                'stock_card_item_id' => $data['stock_card_item_id'],
                'transaction_date' => $data['transaction_date'],
                'quantity' => (int) $data['quantity'],
                'notes' => $data['notes'] ?? null,
                'created_by' => $request->user()?->id,
            ]);

            $this->recalculateItemStock((int) $data['stock_card_item_id']);
        });

        return redirect()
            ->route('stock-card.index', $this->stockCardRedirectParams($request))
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
            'quantity' => ['required', 'integer', 'gt:0'],
            'requested_by_name' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($request, $data) {
            StockCardRequest::create([
                'stock_card_item_id' => $data['stock_card_item_id'],
                'request_date' => $data['request_date'],
                'quantity' => (int) $data['quantity'],
                'requested_by_name' => $data['requested_by_name'],
                'notes' => $data['notes'] ?? null,
                'status' => 'pending',
                'created_by' => $request->user()?->id,
            ]);
        });

        return redirect()
            ->route('stock-card.index', $this->stockCardRedirectParams($request))
            ->with('success', 'Permintaan stock berhasil disimpan dan menunggu approval.');
    }

    public function approveRequest(Request $request, StockCardRequest $stockCardRequest): RedirectResponse
    {
        abort_unless(
            $this->accessRules()->allows($request->user(), self::ACCESS_MODULE, 'approve_request'),
            403
        );

        if ($stockCardRequest->status === 'approved') {
            return redirect()
                ->route('stock-card.index', $this->stockCardRedirectParams($request))
                ->with('success', 'Permintaan stock sudah pernah di-approve.');
        }

        $projectedStock = $this->calculateProjectedStock(
            (int) $stockCardRequest->stock_card_item_id,
            $stockCardRequest->request_date->format('Y-m-d'),
            (int) $stockCardRequest->quantity
        );

        if ($projectedStock < 0) {
            return redirect()
                ->route('stock-card.index', $this->stockCardRedirectParams($request))
                ->withErrors(['request_quantity' => 'Stock tidak cukup untuk menyetujui permintaan ini.']);
        }

        DB::transaction(function () use ($request, $stockCardRequest) {
            $stockCardRequest->update([
                'status' => 'approved',
                'approved_by' => $request->user()?->id,
                'approved_at' => now(),
            ]);

            $this->recalculateItemStock((int) $stockCardRequest->stock_card_item_id);
        });

        return redirect()
            ->route('stock-card.index', $this->stockCardRedirectParams($request))
            ->with('success', 'Permintaan stock berhasil di-approve.');
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
                'sort_stamp' => optional($entry->created_at)->format('Y-m-d H:i:s') ?? '',
                'sort_id' => (int) $entry->id,
                'incoming' => (float) $entry->quantity,
                'outgoing' => 0,
                'note' => $entry->notes ?: optional($entry->creator)->name ?: '-',
                'type' => 'stock_in',
            ]);
        }

        foreach ($item->stockRequests()->with('creator:id,name')->where('status', 'approved')->orderBy('request_date')->orderBy('id')->get() as $entry) {
            $note = trim(($entry->requested_by_name ?: '') . ($entry->notes ? ' - ' . $entry->notes : ''));

            $rows->push([
                'date' => optional($entry->request_date)->format('d/m/Y'),
                'sort_date' => optional($entry->request_date)->format('Y-m-d') ?? '',
                'sort_stamp' => optional($entry->approved_at)->format('Y-m-d H:i:s')
                    ?? optional($entry->created_at)->format('Y-m-d H:i:s')
                    ?? '',
                'sort_id' => (int) $entry->id,
                'incoming' => 0,
                'outgoing' => (float) $entry->quantity,
                'note' => $note ?: optional($entry->creator)->name ?: '-',
                'type' => 'request',
            ]);
        }

        return $rows
            ->sortBy([
                ['sort_date', 'asc'],
                ['sort_stamp', 'asc'],
                ['sort_id', 'asc'],
            ])
            ->values()
            ->reduce(function (Collection $carry, array $row) use ($item) {
                $previousBalance = (float) ($carry->last()['balance_raw'] ?? 0);
                $currentBalance = $previousBalance + $row['incoming'] - $row['outgoing'];

                $carry->push([
                    'date' => $row['date'],
                    'item_name' => $item->name,
                    'sort_date' => $row['sort_date'],
                    'sort_stamp' => $row['sort_stamp'],
                    'sort_id' => $row['sort_id'],
                    'type' => $row['type'],
                    'incoming' => $row['incoming'] > 0 ? $this->formatQuantity($row['incoming']) . ' ' . $item->unit : '-',
                    'outgoing' => $row['outgoing'] > 0 ? $this->formatQuantity($row['outgoing']) . ' ' . $item->unit : '-',
                    'balance' => $this->formatQuantity($currentBalance) . ' ' . $item->unit,
                    'balance_raw' => $currentBalance,
                    'note' => $row['note'],
                    'is_latest_balance' => false,
                ]);

                return $carry;
            }, collect())
            ->sortBy([
                ['sort_date', 'desc'],
                ['sort_stamp', 'desc'],
                ['sort_id', 'desc'],
            ])
            ->values()
            ->map(function (array $row, int $index) {
                $row['is_latest_balance'] = $index === 0;
                return $row;
            })
            ->all();
    }

    private function buildAllCardRows($items): array
    {
        $rows = collect($items)
            ->flatMap(function (StockCardItem $item) {
                return collect($this->buildCardRows($item))
                    ->map(function (array $row) use ($item) {
                        return [
                            'date' => $row['date'],
                            'item_name' => $row['item_name'] ?? $item->name,
                            'sort_date' => $this->sortDateValue($row['date']),
                            'sort_stamp' => $row['sort_stamp'] ?? '',
                            'sort_id' => $row['sort_id'] ?? 0,
                            'sort_item_type' => strtolower((string) $item->item_type),
                            'sort_item_name' => strtolower((string) $item->name),
                            'type' => $row['type'],
                            'incoming' => $row['incoming'],
                            'outgoing' => $row['outgoing'],
                            'balance' => $row['balance'],
                            'note' => $row['note'],
                            'is_latest_balance' => $row['is_latest_balance'] ?? false,
                        ];
                    });
            })
            ->sortBy([
                ['sort_item_type', 'asc'],
                ['sort_stamp', 'desc'],
                ['sort_date', 'desc'],
                ['type', 'desc'],
                ['sort_item_name', 'asc'],
                ['sort_id', 'desc'],
            ])
            ->values();

        return $rows
            ->map(function (array $row) {
                unset($row['sort_date'], $row['sort_stamp'], $row['sort_item_type'], $row['sort_item_name'], $row['sort_id']);
                return $row;
            })
            ->all();
    }

    private function sortDateValue(?string $date): string
    {
        if (!$date) {
            return '';
        }

        try {
            return Carbon::createFromFormat('d/m/Y', $date)->format('Y-m-d');
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function recalculateItemStock(int $itemId): void
    {
        $item = StockCardItem::findOrFail($itemId);

        $totalIn = (float) $item->stockIns()->sum('quantity');
        $totalOut = (float) $item->stockRequests()->where('status', 'approved')->sum('quantity');

        $item->update([
            'current_stock' => max($totalIn - $totalOut, 0),
            'updated_by' => auth()->id(),
        ]);
    }

    private function calculateProjectedStock(int $itemId, string $requestDate, int $requestQty): float
    {
        $date = Carbon::parse($requestDate)->format('Y-m-d');

        $totalIn = (float) StockCardStockIn::query()
            ->where('stock_card_item_id', $itemId)
            ->whereDate('transaction_date', '<=', $date)
            ->sum('quantity');

        $totalOut = (float) StockCardRequest::query()
            ->where('stock_card_item_id', $itemId)
            ->where('status', 'approved')
            ->whereDate('request_date', '<=', $date)
            ->sum('quantity');

        return $totalIn - $totalOut - $requestQty;
    }

    private function generateItemCode(): string
    {
        $lastId = (int) StockCardItem::max('id') + 1;

        return 'SC-' . str_pad((string) $lastId, 4, '0', STR_PAD_LEFT);
    }

    private function formatQuantity(float|string|int $value): string
    {
        return (string) ((int) round((float) $value));
    }

    private function accessRules(): AccessRuleService
    {
        return app(AccessRuleService::class);
    }

    private function stockCardRedirectParams(Request $request): array
    {
        $itemId = trim((string) $request->input('return_item_id', 'all'));
        $search = trim((string) $request->input('return_q', ''));

        $params = [];

        if ($itemId !== '' && strtolower($itemId) !== 'all') {
            $params['item_id'] = $itemId;
        }

        if ($search !== '') {
            $params['q'] = $search;
        }

        return $params;
    }
}
