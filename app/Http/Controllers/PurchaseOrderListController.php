<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RemembersIndexUrl;
use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionItem;
use App\Models\PurchaseRequisitionSupplier;
use App\Models\PurchaseRequisitionSupplierItemQuote;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PurchaseOrderListController extends Controller
{
    use RemembersIndexUrl;

    private const ACCESS_MODULE = 'gmisl.procurement.purchase_order';
    private const OWNER_DEPARTMENT_CODE = 'OWNER';
    private const FAT_DEPARTMENT_CODE = 'FAT';
    private const IT_DEPARTMENT_CODE = 'IT';

    /**
     * Display Purchase Order index page
     */
    public function index(Request $request)
    {
        $this->rememberIndexUrl($request, 'purchase_orders');

        /** @var \App\Models\User|null $user */
        $user = $request->user();
        $user?->loadMissing('department');
        $search = trim((string) $request->query('search', ''));

        abort_unless($this->canAccessPurchaseOrderModule($user), 403, 'Hanya departemen Owner, FAT, atau IT yang dapat melihat purchase order.');

        $purchaseOrders = PurchaseRequisition::query()
            ->with([
                'requester:id,name,department_id',
                'department:id,name,code',
                'approvedBy:id,name',
                'items:id,purchase_requisition_id,item_code,item_name,description_of_goods,unit,quantity,required_date,price,product_name,uom,qty',
                'attachments:id,purchase_requisition_id,filename,path,mime_type,size',
                'prSuppliers.supplier:id,name,code',
                'prSuppliers.itemQuotes:id,pr_supplier_id,purchase_requisition_item_id,quoted_price,is_selected',
            ])
            ->whereIn('status', ['approved', 'process', 'done'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('pr_number', 'like', '%' . $search . '%')
                        ->orWhereHas('requester', fn ($requesterQuery) => $requesterQuery->where('name', 'like', '%' . $search . '%'))
                        ->orWhereHas('department', fn ($departmentQuery) => $departmentQuery->where('name', 'like', '%' . $search . '%'));
                });
            })
            ->orderByRaw("CASE WHEN status = 'approved' THEN 0 WHEN status = 'process' THEN 1 WHEN status = 'done' THEN 2 ELSE 3 END")
            ->orderByDesc('approved_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(function (PurchaseRequisition $purchaseRequisition) use ($user) {
                return [
                    'id' => $purchaseRequisition->id,
                    'pr_number' => $purchaseRequisition->pr_number,
                    'po_number' => $purchaseRequisition->po_number,
                    'pr_date' => optional($purchaseRequisition->pr_date)->toDateString(),
                    'request_date' => optional($purchaseRequisition->request_date)->toDateString(),
                    'priority' => $purchaseRequisition->priority,
                    'status' => $purchaseRequisition->status,
                    'note' => $purchaseRequisition->note,
                    'po_comment' => $purchaseRequisition->po_comment,
                    'po_photo_url' => $purchaseRequisition->po_photo_path
                        ? Storage::disk('public')->url($purchaseRequisition->po_photo_path)
                        : null,
                    'po_photo_filename' => $purchaseRequisition->po_photo_filename,
                    'po_photo_mime_type' => $purchaseRequisition->po_photo_mime_type,
                    'department_name' => optional($purchaseRequisition->department)->name,
                    'requester_name' => optional($purchaseRequisition->requester)->name,
                    'approved_at' => $purchaseRequisition->approved_at?->format('Y-m-d H:i'),
                    'approved_by_name' => optional($purchaseRequisition->approvedBy)->name,
                    'po_processed_at' => $purchaseRequisition->po_processed_at?->format('Y-m-d H:i'),
                'po_done_at' => $purchaseRequisition->po_done_at?->format('Y-m-d H:i'),
                'can_process' => $this->canProcess($user, $purchaseRequisition),
                'can_update_po' => $this->canUpdatePo($user, $purchaseRequisition),
                'can_done' => $this->canDone($user, $purchaseRequisition),
                'can_open' => $this->canOpen($user, $purchaseRequisition),
                'can_delete' => $this->isItDepartmentUser($user),
                'po_summary' => $this->purchaseOrderSummaryPayload($purchaseRequisition),
                'items' => $purchaseRequisition->items
                        ->map(fn ($item) => $this->itemPayload($item))
                        ->values(),
                    'attachments' => $purchaseRequisition->attachments
                        ->map(fn ($attachment) => [
                            'id' => $attachment->id,
                            'filename' => $attachment->filename,
                            'url' => Storage::disk('public')->url($attachment->path),
                            'size' => $this->formatFileSize((int) ($attachment->size ?? 0)),
                        ])
                        ->values(),
                ];
            })
            ->values();

        return Inertia::render('Procurement/PurchaseOrder/Index', [
            'title' => 'Purchase Order',
            'description' => 'Manage Purchase Orders',
            'purchaseOrders' => $purchaseOrders,
            'filters' => [
                'search' => $search,
            ],
            'currentUser' => [
                'id' => $user?->id,
                'name' => $user?->name,
                'department_id' => $user?->department_id,
                'department_name' => $user?->department?->name,
                'department_code' => $user?->department?->code,
            ],
        ]);
    }

    public function edit(Request $request, PurchaseRequisition $purchaseRequisition)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();
        $user?->loadMissing('department');

        abort_unless($this->canOpen($user, $purchaseRequisition), 403, 'Purchase order ini tidak bisa dibuka.');

        $purchaseRequisition->load([
            'requester:id,name,department_id',
            'department:id,name,code',
            'approvedBy:id,name',
            'items:id,purchase_requisition_id,item_code,item_name,description_of_goods,unit,quantity,required_date,price,product_name,uom,qty',
            'attachments:id,purchase_requisition_id,filename,path,mime_type,size',
            'prSuppliers.supplier:id,name,code',
            'prSuppliers.itemQuotes:id,pr_supplier_id,purchase_requisition_item_id,quoted_price,is_selected',
        ]);

        return Inertia::render('Procurement/PurchaseOrder/Form', [
            'title' => 'Purchase Order',
            'description' => 'Purchase Order Form',
            'purchaseOrder' => [
                'id' => $purchaseRequisition->id,
                'pr_number' => $purchaseRequisition->pr_number,
                'po_number' => $purchaseRequisition->po_number,
                'pr_date' => optional($purchaseRequisition->pr_date)->toDateString(),
                'request_date' => optional($purchaseRequisition->request_date)->toDateString(),
                'priority' => $purchaseRequisition->priority,
                'status' => $purchaseRequisition->status,
                'note' => $purchaseRequisition->note,
                'po_comment' => $purchaseRequisition->po_comment,
                'po_photo_url' => $purchaseRequisition->po_photo_path
                    ? Storage::disk('public')->url($purchaseRequisition->po_photo_path)
                    : null,
                'po_photo_filename' => $purchaseRequisition->po_photo_filename,
                'po_photo_mime_type' => $purchaseRequisition->po_photo_mime_type,
                'department_name' => optional($purchaseRequisition->department)->name,
                'requester_name' => optional($purchaseRequisition->requester)->name,
                'approved_at' => $purchaseRequisition->approved_at?->format('Y-m-d H:i'),
                'approved_by_name' => optional($purchaseRequisition->approvedBy)->name,
                'po_processed_at' => $purchaseRequisition->po_processed_at?->format('Y-m-d H:i'),
                'po_done_at' => $purchaseRequisition->po_done_at?->format('Y-m-d H:i'),
                'can_process' => $this->canProcess($user, $purchaseRequisition),
                'can_update_po' => $this->canUpdatePo($user, $purchaseRequisition),
                'can_done' => $this->canDone($user, $purchaseRequisition),
                'can_delete' => $this->isItDepartmentUser($user),
                'po_summary' => $this->purchaseOrderSummaryPayload($purchaseRequisition),
                'items' => $purchaseRequisition->items
                    ->map(fn ($item) => $this->itemPayload($item))
                    ->values(),
                'attachments' => $purchaseRequisition->attachments
                    ->map(fn ($attachment) => [
                        'id' => $attachment->id,
                        'filename' => $attachment->filename,
                        'url' => Storage::disk('public')->url($attachment->path),
                        'size' => $this->formatFileSize((int) ($attachment->size ?? 0)),
                    ])
                    ->values(),
            ],
            'currentUser' => [
                'id' => $user?->id,
                'name' => $user?->name,
                'department_id' => $user?->department_id,
                'department_name' => $user?->department?->name,
                'department_code' => $user?->department?->code,
            ],
        ]);
    }

    public function show(Request $request, PurchaseRequisition $purchaseRequisition)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();
        $user?->loadMissing('department');

        abort_unless($this->canOpen($user, $purchaseRequisition), 403, 'Purchase order ini tidak bisa dibuka.');

        $purchaseRequisition->load([
            'requester:id,name,department_id',
            'department:id,name,code',
            'approvedBy:id,name',
            'items:id,purchase_requisition_id,item_code,item_name,description_of_goods,unit,quantity,required_date,price,product_name,uom,qty',
            'attachments:id,purchase_requisition_id,filename,path,mime_type,size',
            'prSuppliers.supplier:id,name,code',
            'prSuppliers.itemQuotes:id,pr_supplier_id,purchase_requisition_item_id,quoted_price,is_selected',
        ]);

        return Inertia::render('Procurement/PurchaseOrder/Show', [
            'title' => 'Detail Purchase Order',
            'description' => 'Detail Purchase Order',
            'purchaseOrder' => [
                'id' => $purchaseRequisition->id,
                'pr_number' => $purchaseRequisition->pr_number,
                'po_number' => $purchaseRequisition->po_number,
                'pr_date' => optional($purchaseRequisition->pr_date)->toDateString(),
                'request_date' => optional($purchaseRequisition->request_date)->toDateString(),
                'priority' => $purchaseRequisition->priority,
                'status' => $purchaseRequisition->status,
                'note' => $purchaseRequisition->note,
                'po_comment' => $purchaseRequisition->po_comment,
                'po_photo_url' => $purchaseRequisition->po_photo_path
                    ? Storage::disk('public')->url($purchaseRequisition->po_photo_path)
                    : null,
                'po_photo_filename' => $purchaseRequisition->po_photo_filename,
                'po_photo_mime_type' => $purchaseRequisition->po_photo_mime_type,
                'department_name' => optional($purchaseRequisition->department)->name,
                'department_code' => optional($purchaseRequisition->department)->code,
                'requester_name' => optional($purchaseRequisition->requester)->name,
                'approved_at' => $purchaseRequisition->approved_at?->format('Y-m-d H:i'),
                'approved_by_name' => optional($purchaseRequisition->approvedBy)->name,
                'po_processed_at' => $purchaseRequisition->po_processed_at?->format('Y-m-d H:i'),
                'po_processed_by_name' => $purchaseRequisition->po_processed_by ? \App\Models\User::where('id', $purchaseRequisition->po_processed_by)->value('name') : null,
                'po_done_at' => $purchaseRequisition->po_done_at?->format('Y-m-d H:i'),
                'po_done_by_name' => $purchaseRequisition->po_done_by ? \App\Models\User::where('id', $purchaseRequisition->po_done_by)->value('name') : null,
                'can_process' => $this->canProcess($user, $purchaseRequisition),
                'can_update_po' => $this->canUpdatePo($user, $purchaseRequisition),
                'can_done' => $this->canDone($user, $purchaseRequisition),
                'can_delete' => $this->isItDepartmentUser($user),
                'po_summary' => $this->purchaseOrderSummaryPayload($purchaseRequisition),
                'items' => $purchaseRequisition->items
                    ->map(fn ($item) => $this->itemPayload($item))
                    ->values(),
                'attachments' => $purchaseRequisition->attachments
                    ->map(fn ($attachment) => [
                        'id' => $attachment->id,
                        'filename' => $attachment->filename,
                        'url' => Storage::disk('public')->url($attachment->path),
                        'size' => $this->formatFileSize((int) ($attachment->size ?? 0)),
                    ])
                    ->values(),
            ],
            'currentUser' => [
                'id' => $user?->id,
                'name' => $user?->name,
                'department_id' => $user?->department_id,
                'department_name' => $user?->department?->name,
                'department_code' => $user?->department?->code,
            ],
        ]);
    }

    public function process(Request $request, PurchaseRequisition $purchaseRequisition)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();
        $user?->loadMissing('department');

        if (!$this->isFatDepartmentUser($user)) {
            abort(403, 'Hanya tim FAT yang dapat memproses purchase order.');
        }

        if (strtolower(trim((string) $purchaseRequisition->status)) !== 'approved') {
            return redirect()->back()->withErrors([
                'status' => 'Hanya PR dengan status approved yang bisa diproses di Purchase Order.',
            ]);
        }

        if (!$this->hasSelectedVendorForEveryItem($purchaseRequisition)) {
            return redirect()->back()->withErrors([
                'vendor' => 'Pilih vendor untuk setiap item terlebih dahulu sebelum memproses purchase order.',
            ]);
        }

        $this->syncApprovedPurchaseOrderSnapshot($purchaseRequisition);

        $purchaseRequisition->update([
            'status' => 'process',
            'po_number' => $purchaseRequisition->po_number ?: PurchaseRequisition::generatePoNumber(now()),
            'po_processed_by' => $user?->id,
            'po_processed_at' => now(),
            'po_done_by' => null,
            'po_done_at' => null,
        ]);

        return redirect()->back()->with('success', 'Purchase order berhasil diproses oleh tim FAT.');
    }

    public function save(Request $request, PurchaseRequisition $purchaseRequisition)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();
        $user?->loadMissing('department');

        if (!$this->canUpdatePo($user, $purchaseRequisition)) {
            abort(403, 'Data purchase order hanya bisa diubah saat status process oleh tim FAT.');
        }

        $validated = $request->validate([
            'po_comment' => ['nullable', 'string'],
            'po_photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx', 'max:10240'],
        ]);

        $this->persistPoFields($request, $purchaseRequisition, $validated);

        return redirect()->back()->with('success', 'Komentar dan foto purchase order berhasil disimpan.');
    }

    public function done(Request $request, PurchaseRequisition $purchaseRequisition)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();
        $user?->loadMissing('department');

        if (!$this->canDone($user, $purchaseRequisition)) {
            abort(403, 'Hanya purchase order dengan status process yang bisa diubah menjadi done oleh tim FAT.');
        }

        $validated = $request->validate([
            'po_comment' => ['nullable', 'string'],
            'po_photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx', 'max:10240'],
        ]);

        $this->persistPoFields($request, $purchaseRequisition, $validated);

        $purchaseRequisition->update([
            'status' => 'done',
            'po_done_by' => $user?->id,
            'po_done_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Purchase order berhasil ditandai done.');
    }

    private function canProcess(?User $user, PurchaseRequisition $purchaseRequisition): bool
    {
        return $this->isFatDepartmentUser($user)
            && strtolower(trim((string) $purchaseRequisition->status)) === 'approved';
    }

    private function canUpdatePo(?User $user, PurchaseRequisition $purchaseRequisition): bool
    {
        return $this->isFatDepartmentUser($user)
            && strtolower(trim((string) $purchaseRequisition->status)) === 'process';
    }

    private function canDone(?User $user, PurchaseRequisition $purchaseRequisition): bool
    {
        return $this->canUpdatePo($user, $purchaseRequisition);
    }

    private function canOpen(?User $user, PurchaseRequisition $purchaseRequisition): bool
    {
        return $this->canAccessPurchaseOrderModule($user)
            && in_array(strtolower(trim((string) $purchaseRequisition->status)), ['approved', 'process', 'done'], true);
    }

    private function canAccessPurchaseOrderModule(?User $user): bool
    {
        return $this->isOwnerDepartmentUser($user)
            || $this->isFatDepartmentUser($user)
            || $this->isItDepartmentUser($user);
    }

    private function isOwnerDepartmentUser(?User $user): bool
    {
        return strtoupper(trim((string) ($user?->department?->code ?? ''))) === self::OWNER_DEPARTMENT_CODE;
    }

    private function isFatDepartmentUser(?User $user): bool
    {
        return strtoupper(trim((string) ($user?->department?->code ?? ''))) === self::FAT_DEPARTMENT_CODE;
    }

    private function persistPoFields(Request $request, PurchaseRequisition $purchaseRequisition, array $validated): void
    {
        $payload = [
            'po_comment' => $validated['po_comment'] ?? null,
        ];

        if ($request->hasFile('po_photo')) {
            if ($purchaseRequisition->po_photo_path && Storage::disk('public')->exists($purchaseRequisition->po_photo_path)) {
                Storage::disk('public')->delete($purchaseRequisition->po_photo_path);
            }

            $file = $request->file('po_photo');

            if (!$file) {
                throw ValidationException::withMessages([
                    'po_photo' => 'File foto purchase order tidak valid.',
                ]);
            }

            $path = $file->store('purchase-orders/' . $purchaseRequisition->id, 'public');

            $payload['po_photo_path'] = $path;
            $payload['po_photo_filename'] = $file->getClientOriginalName();
            $payload['po_photo_mime_type'] = $file->getClientMimeType();
        }

        $purchaseRequisition->update($payload);
    }

    private function formatFileSize(int $size): string
    {
        if ($size <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = (float) $size;
        $unitIndex = 0;

        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        $precision = $unitIndex === 0 ? 0 : 2;

        return number_format($bytes, $precision) . ' ' . $units[$unitIndex];
    }

    private function itemPayload($item): array
    {
        return [
            'id' => $item->id,
            'item_code' => $item->item_code,
            'item_name' => $item->item_name ?: $item->product_name,
            'description_of_goods' => $item->description_of_goods ?: $item->product_name,
            'unit' => $item->unit ?: $item->uom,
            'quantity' => $item->quantity ?? $item->qty,
            'required_date' => optional($item->required_date)->toDateString(),
            'price' => $item->price ?? 0,
            'product_name' => $item->item_name ?: $item->product_name,
            'uom' => $item->unit ?: $item->uom,
            'qty' => $item->quantity ?? $item->qty,
        ];
    }

    private function purchaseOrderSummaryPayload(PurchaseRequisition $purchaseRequisition): array
    {
        $purchaseRequisition->loadMissing([
            'items:id,purchase_requisition_id,item_code,item_name,description_of_goods,unit,quantity,required_date,price,product_name,uom,qty',
            'prSuppliers.supplier:id,name,code',
            'prSuppliers.itemQuotes:id,pr_supplier_id,purchase_requisition_item_id,quoted_price,is_selected',
        ]);

        $selectedRows = $purchaseRequisition->prSuppliers
            ->flatMap(function (PurchaseRequisitionSupplier $prSupplier) {
                return $prSupplier->itemQuotes
                    ->filter(fn (PurchaseRequisitionSupplierItemQuote $quote) => (bool) $quote->is_selected)
                    ->map(function (PurchaseRequisitionSupplierItemQuote $quote) use ($prSupplier) {
                        return [
                            'purchase_requisition_item_id' => (int) $quote->purchase_requisition_item_id,
                            'supplier_id' => (int) $prSupplier->supplier_id,
                            'supplier_name' => $prSupplier->supplier?->name,
                            'supplier_code' => $prSupplier->supplier?->code,
                            'payment_terms' => $prSupplier->payment_terms,
                            'quoted_price' => (float) ($quote->quoted_price ?? 0),
                        ];
                    });
            })
            ->keyBy('purchase_requisition_item_id');

        $items = $purchaseRequisition->items
            ->map(function (PurchaseRequisitionItem $item) use ($selectedRows) {
                $selectedRow = $selectedRows->get((int) $item->id);
                $quantity = (float) ($item->quantity ?? $item->qty ?? 0);
                $approvedPrice = $selectedRow['quoted_price'] ?? (float) ($item->price ?? 0);
                $lineTotal = $quantity * $approvedPrice;

                return [
                    'id' => $item->id,
                    'item_code' => $item->item_code,
                    'item_name' => $item->item_name ?: $item->product_name,
                    'description_of_goods' => $item->description_of_goods ?: $item->product_name,
                    'unit' => $item->unit ?: $item->uom,
                    'quantity' => $quantity,
                    'approved_price' => $approvedPrice,
                    'line_total' => $lineTotal,
                    'supplier_id' => $selectedRow['supplier_id'] ?? null,
                    'supplier_name' => $selectedRow['supplier_name'] ?? null,
                    'supplier_code' => $selectedRow['supplier_code'] ?? null,
                    'payment_terms' => $selectedRow['payment_terms'] ?? null,
                ];
            })
            ->values();

        $supplierGroups = $items
            ->groupBy(fn (array $item) => (string) ($item['supplier_id'] ?? ''))
            ->map(function ($groupedItems) {
                $first = $groupedItems->first();

                return [
                    'supplier_id' => $first['supplier_id'] ?? null,
                    'supplier_name' => $first['supplier_name'] ?? null,
                    'supplier_code' => $first['supplier_code'] ?? null,
                    'payment_terms' => $first['payment_terms'] ?? null,
                    'total_amount' => $groupedItems->sum('line_total'),
                ];
            })
            ->filter(fn (array $group) => $group['supplier_id'] !== null)
            ->values();

        return [
            'items' => $items->all(),
            'suppliers' => $supplierGroups->all(),
            'grand_total' => (float) $items->sum('line_total'),
        ];
    }

    private function syncApprovedPurchaseOrderSnapshot(PurchaseRequisition $purchaseRequisition): void
    {
        $purchaseRequisition->loadMissing([
            'items:id,purchase_requisition_id,price',
            'prSuppliers.itemQuotes:id,pr_supplier_id,purchase_requisition_item_id,quoted_price,is_selected',
        ]);

        $selectedQuotes = $purchaseRequisition->prSuppliers
            ->flatMap(fn (PurchaseRequisitionSupplier $prSupplier) => $prSupplier->itemQuotes)
            ->filter(fn (PurchaseRequisitionSupplierItemQuote $quote) => (bool) $quote->is_selected)
            ->keyBy('purchase_requisition_item_id');

        foreach ($purchaseRequisition->items as $item) {
            $selectedQuote = $selectedQuotes->get($item->id);

            if (!$selectedQuote) {
                continue;
            }

            $item->update([
                'price' => $selectedQuote->quoted_price ?? 0,
            ]);
        }
    }

    private function hasSelectedVendorForEveryItem(PurchaseRequisition $purchaseRequisition): bool
    {
        $purchaseRequisition->loadMissing([
            'items:id,purchase_requisition_id',
            'prSuppliers.itemQuotes:id,pr_supplier_id,purchase_requisition_item_id,is_selected',
        ]);

        $itemIds = $purchaseRequisition->items
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        if ($itemIds->isEmpty()) {
            return false;
        }

        $selectedItemIds = $purchaseRequisition->prSuppliers
            ->flatMap(fn (PurchaseRequisitionSupplier $prSupplier) => $prSupplier->itemQuotes)
            ->filter(fn (PurchaseRequisitionSupplierItemQuote $quote) => (bool) $quote->is_selected)
            ->pluck('purchase_requisition_item_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        return $itemIds->diff($selectedItemIds)->isEmpty();
    }

    public function destroy(Request $request, PurchaseRequisition $purchaseRequisition)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();
        $user?->loadMissing('department');

        if (!$this->isItDepartmentUser($user)) {
            abort(403, 'Hanya tim IT yang dapat menghapus purchase order.');
        }

        $status = strtolower(trim((string) $purchaseRequisition->status));
        if (!in_array($status, ['approved', 'process', 'done'], true)) {
            abort(403, 'Purchase order hanya bisa dihapus untuk status approved, process, atau done.');
        }

        if ($purchaseRequisition->po_photo_path && Storage::disk('public')->exists($purchaseRequisition->po_photo_path)) {
            Storage::disk('public')->delete($purchaseRequisition->po_photo_path);
        }

        $purchaseRequisition->update([
            'status' => 'approved',
            'po_number' => null,
            'po_comment' => null,
            'po_photo_path' => null,
            'po_photo_filename' => null,
            'po_photo_mime_type' => null,
            'po_processed_by' => null,
            'po_processed_at' => null,
            'po_done_by' => null,
            'po_done_at' => null,
        ]);

        return redirect()->route('purchase-order.index')
            ->with('success', 'Data purchase order berhasil direset. Purchase requisition tetap tersimpan.');
    }

    private function isItDepartmentUser(?User $user): bool
    {
        return strtoupper(trim((string) ($user?->department?->code ?? ''))) === self::IT_DEPARTMENT_CODE;
    }
}
