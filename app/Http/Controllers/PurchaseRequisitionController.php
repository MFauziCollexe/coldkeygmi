<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RemembersIndexUrl;
use App\Models\ProcurementMasterItem;
use App\Models\PurchaseRequisitionAttachment;
use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionItem;
use App\Models\PurchaseRequisitionSupplier;
use App\Models\PurchaseRequisitionSupplierItemQuote;
use App\Models\StockCardUnit;
use App\Models\Supplier;
use App\Models\User;
use App\Services\ImageMergeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PurchaseRequisitionController extends Controller
{
    use RemembersIndexUrl;

    private const ACCESS_MODULE = 'gmisl.procurement.purchase_requisition';
    private const OWNER_DEPARTMENT_CODE = 'OWNER';
    private const IT_DEPARTMENT_CODE = 'IT';
    private const FAT_DEPARTMENT_CODE = 'FAT';
    private const PAYMENT_METHOD_OPTIONS = [
        'Tunai',
        'COD',
        'Transfer',
        'E-wallet',
        'QRIS',
        'Kredit',
        'Debit',
        'Cek',
        'Giro',
    ];

    public function index(Request $request)
    {
        $this->rememberIndexUrl($request, 'purchase_requisitions');

        /** @var \App\Models\User|null $user */
        $user = $request->user();
        $user?->loadMissing(['department']);
        $isItUser = $this->isItDepartmentUser($user);
        $purchaseRequisitions = $this->visiblePurchaseRequisitionsQuery($user)
            ->with([
                'requester:id,name,department_id',
                'department:id,name,code',
                'approvedBy:id,name',
                'items:id,purchase_requisition_id,procurement_master_item_id,line_no,item_code,item_name,description_of_goods,specification,unit,quantity,required_date,price,product_name,uom,qty',
                'attachments:id,purchase_requisition_id,filename,path,mime_type,size',
                'suppliers:id',
            ])
            ->orderByRaw("CASE WHEN status = 'waiting' THEN 0 WHEN status = 'approved' THEN 1 WHEN status = 'process' THEN 2 WHEN status = 'done' THEN 3 ELSE 4 END")
            ->orderByDesc('created_at')
            ->when(!$isItUser, fn ($q) => $q->limit(50))
            ->get()
            ->map(function (PurchaseRequisition $purchaseRequisition) use ($user) {
                return [
                    'id' => $purchaseRequisition->id,
                    'pr_number' => $purchaseRequisition->pr_number,
                    'pr_date' => optional($purchaseRequisition->pr_date)->toDateString(),
                    'request_date' => optional($purchaseRequisition->request_date)->toDateString(),
                    'priority' => $purchaseRequisition->priority,
                    'status' => $purchaseRequisition->status,
                    'note' => $purchaseRequisition->note,
                    'department_name' => optional($purchaseRequisition->department)->name,
                    'department_code' => optional($purchaseRequisition->department)->code,
                    'requester_name' => optional($purchaseRequisition->requester)->name,
                    'created_at' => $purchaseRequisition->created_at?->format('Y-m-d H:i'),
                    'approved_at' => $purchaseRequisition->approved_at?->format('Y-m-d H:i'),
                    'approved_by_name' => optional($purchaseRequisition->approvedBy)->name,
'can_edit' => $this->canEdit($user, $purchaseRequisition),
                     'can_approve' => $this->canApprove($user, $purchaseRequisition),
                    'items' => $purchaseRequisition->items
                        ->map(fn ($item) => $this->itemPayload($item))
                        ->values(),
                    'attachments' => $purchaseRequisition->attachments
                        ->map(fn ($attachment) => [
                            'id' => $attachment->id,
                            'filename' => $attachment->filename,
                            'url' => $attachment->signed_url ?: Storage::disk('public')->url($attachment->path),
                            'mime_type' => $attachment->mime_type,
                            'size' => $this->formatFileSize((int) ($attachment->size ?? 0)),
                        ])
                        ->values(),
                ];
            })
            ->values();

        return Inertia::render('Procurement/PurchaseRequisition/Index', [
            'title' => 'Purchase Requisition',
            'description' => 'Purchase Requisition List',
            'currentUser' => [
                'id' => $user?->id,
                'name' => $user?->name,
                'department_id' => $user?->department_id,
                'department_name' => $user?->department?->name,
                'department_code' => $user?->department?->code,
            ],
            'purchaseRequisitions' => $purchaseRequisitions,
        ]);
    }

    public function create(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();
        $user?->loadMissing(['department']);
        $today = now()->toDateString();

        return Inertia::render('Procurement/PurchaseRequisition/Create', [
            'title' => 'Create Purchase Requisition',
            'description' => 'Create Purchase Requisition',
            'defaults' => [
                'pr_number' => PurchaseRequisition::generateNumber(now()),
                'pr_date' => $today,
                'priority' => 'medium',
                'department_id' => $user?->department_id,
                'status' => 'draft',
                'requestor' => $user?->name,
            ],
            'uomOptions' => $this->uomOptions(),
            'masterItems' => $this->masterItemOptions(),
            'minimumRequiredDate' => Carbon::parse($today)->addDays(3)->toDateString(),
            'currentUser' => $this->currentUserPayload($user),
        ]);
    }

    public function store(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        $user?->loadMissing('department');
        $userDepartmentId = (int) ($user?->department_id ?? 0);

        if ($userDepartmentId <= 0) {
            return redirect()->back()->withErrors([
                'department_id' => 'User belum memiliki department aktif.',
            ]);
        }

        $validated = $request->validate([
            'priority' => ['required', 'in:medium,urgent,low'],
            'department_id' => ['required', 'exists:departments,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'note' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.procurement_master_item_id' => ['nullable', 'integer', 'exists:procurement_master_items,id'],
            'items.*.item_code' => ['required', 'string', 'max:100'],
            'items.*.item_name' => ['required', 'string', 'max:255'],
            'items.*.description_of_goods' => ['required', 'string'],
            'items.*.specification' => ['nullable', 'string'],
            'items.*.unit' => ['required', 'string', 'max:100', 'exists:stock_card_units,name'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.required_date' => ['required', 'date'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:10240'],
        ]);

        if ((int) $validated['department_id'] !== $userDepartmentId) {
            return redirect()->back()->withErrors([
                'department_id' => 'Department PR harus sesuai dengan department user pembuat.',
            ]);
        }

        $prDate = now()->startOfDay();
        $minimumRequiredDate = $prDate->copy()->addDays(3);
        $requiredDateErrors = $this->requiredDateErrors($validated['items'], $minimumRequiredDate);
        if ($requiredDateErrors !== []) {
            return redirect()->back()->withErrors($requiredDateErrors)->withInput();
        }

        DB::transaction(function () use ($request, $validated, $user, $userDepartmentId) {
            $purchaseRequisition = PurchaseRequisition::create([
                'pr_number' => PurchaseRequisition::generateNumber(now()),
                'pr_date' => now()->toDateString(),
                'request_date' => now()->toDateString(),
                'priority' => $validated['priority'],
                'requested_by' => $user->id,
                'department_id' => $userDepartmentId,
                'supplier_id' => $validated['supplier_id'] ?? null,
                'status' => 'waiting',
                'note' => $validated['note'] ?? null,
            ]);

            $this->syncItems($purchaseRequisition, $validated['items']);
            $this->syncSupplierComparisonRows($purchaseRequisition);

            if ($request->hasFile('attachments')) {
                foreach ((array) $request->file('attachments') as $file) {
                    $path = $file->store('purchase-requisitions/' . $purchaseRequisition->id, 'public');

                    $purchaseRequisition->attachments()->create([
                        'filename' => $file->getClientOriginalName(),
                        'path' => $path,
                        'mime_type' => $file->getClientMimeType(),
                        'size' => $file->getSize(),
                    ]);
                }
            }
        });

        return redirect()
            ->route('purchase-requisition.index')
            ->with('success', 'Purchase requisition berhasil disimpan.');
    }

    public function edit(Request $request, PurchaseRequisition $purchaseRequisition)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();
        $user?->loadMissing('department');

        abort_unless($this->canEdit($user, $purchaseRequisition), 403, 'Purchase requisition ini tidak bisa diubah.');

        $purchaseRequisition->load([
            'requester:id,name,department_id',
            'department:id,name,code',
            'items:id,purchase_requisition_id,procurement_master_item_id,line_no,item_code,item_name,description_of_goods,specification,unit,quantity,required_date,price,product_name,uom,qty',
            'attachments:id,purchase_requisition_id,filename,path,mime_type,size',
        ]);

        return Inertia::render('Procurement/PurchaseRequisition/Edit', [
            'title' => 'Edit Purchase Requisition',
            'description' => 'Edit Purchase Requisition',
            'purchaseRequisition' => [
                'id' => $purchaseRequisition->id,
                'pr_number' => $purchaseRequisition->pr_number,
                'pr_date' => optional($purchaseRequisition->pr_date)->toDateString(),
                'priority' => $purchaseRequisition->priority,
                'status' => $purchaseRequisition->status,
                'note' => $purchaseRequisition->note,
                'reject_note' => $purchaseRequisition->reject_note,
                'items' => $purchaseRequisition->items
                    ->map(fn ($item) => $this->itemPayload($item))
                    ->values(),
                'attachments' => $purchaseRequisition->attachments
                    ->map(fn ($attachment) => [
                        'id' => $attachment->id,
                        'filename' => $attachment->filename,
                        'mime_type' => $attachment->mime_type,
                        'size' => $this->formatFileSize((int) ($attachment->size ?? 0)),
                        'url' => Storage::disk('public')->url($attachment->path),
                    ])
                    ->values(),
            ],
            'uomOptions' => $this->uomOptions(),
            'masterItems' => $this->masterItemOptions(),
            'minimumRequiredDate' => ($purchaseRequisition->pr_date ? Carbon::parse($purchaseRequisition->pr_date)->addDays(3)->toDateString() : now()->addDays(3)->toDateString()),
            'currentUser' => $this->currentUserPayload($user),
        ]);
    }

    public function update(Request $request, PurchaseRequisition $purchaseRequisition)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();
        $user?->loadMissing('department');

        if (!$this->canEdit($user, $purchaseRequisition)) {
            abort(403, 'Purchase requisition ini tidak bisa diubah.');
        }

        $validated = $request->validate([
            'priority' => ['required', 'in:medium,urgent,low'],
            'department_id' => ['required', 'exists:departments,id'],
            'note' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.procurement_master_item_id' => ['nullable', 'integer', 'exists:procurement_master_items,id'],
            'items.*.item_code' => ['required', 'string', 'max:100'],
            'items.*.item_name' => ['required', 'string', 'max:255'],
            'items.*.description_of_goods' => ['required', 'string'],
            'items.*.specification' => ['nullable', 'string'],
            'items.*.unit' => ['required', 'string', 'max:100', 'exists:stock_card_units,name'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.required_date' => ['required', 'date'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:10240'],
            'delete_attachment_ids' => ['nullable', 'array'],
            'delete_attachment_ids.*' => ['integer'],
        ]);

        if ((int) $validated['department_id'] !== (int) ($user?->department_id ?? 0)) {
            return redirect()->back()->withErrors([
                'department_id' => 'Department PR harus sesuai dengan department user pembuat.',
            ]);
        }

        $prDate = ($purchaseRequisition->pr_date ? Carbon::parse($purchaseRequisition->pr_date) : now())->startOfDay();
        $minimumRequiredDate = $prDate->copy()->addDays(3);
        $requiredDateErrors = $this->requiredDateErrors($validated['items'], $minimumRequiredDate);
        if ($requiredDateErrors !== []) {
            return redirect()->back()->withErrors($requiredDateErrors)->withInput();
        }

        DB::transaction(function () use ($request, $validated, $purchaseRequisition) {
            $purchaseRequisition->update([
                'request_date' => $purchaseRequisition->pr_date ?: now()->toDateString(),
                'priority' => $validated['priority'],
                'note' => $validated['note'] ?? null,
            ]);

            $purchaseRequisition->items()->delete();

            $this->syncItems($purchaseRequisition, $validated['items']);
            $this->syncSupplierComparisonRows($purchaseRequisition);

            $attachmentIdsToDelete = collect($validated['delete_attachment_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->values();

            if ($attachmentIdsToDelete->isNotEmpty()) {
                $attachments = PurchaseRequisitionAttachment::query()
                    ->where('purchase_requisition_id', $purchaseRequisition->id)
                    ->whereIn('id', $attachmentIdsToDelete)
                    ->get();

                foreach ($attachments as $attachment) {
                    if ($attachment->path && Storage::disk('public')->exists($attachment->path)) {
                        Storage::disk('public')->delete($attachment->path);
                    }

                    $attachment->delete();
                }
            }

            if ($request->hasFile('attachments')) {
                foreach ((array) $request->file('attachments') as $file) {
                    $path = $file->store('purchase-requisitions/' . $purchaseRequisition->id, 'public');

                    $purchaseRequisition->attachments()->create([
                        'filename' => $file->getClientOriginalName(),
                        'path' => $path,
                        'mime_type' => $file->getClientMimeType(),
                        'size' => $file->getSize(),
                    ]);
                }
            }
        });

        return $this->redirectToRememberedIndex($request, 'purchase_requisitions', 'purchase-requisition.index')
            ->with('success', 'Purchase requisition berhasil diperbarui.');
    }

    public function approve(Request $request, PurchaseRequisition $purchaseRequisition)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();
        $user?->loadMissing('department');

        if (!$this->isOwnerDepartmentUser($user)) {
            abort(403, 'Hanya department Owner yang dapat approve purchase requisition.');
        }

        if (strtolower(trim((string) $purchaseRequisition->status)) !== 'waiting') {
            return redirect()->back()->withErrors([
                'status' => 'Hanya PR dengan status waiting yang bisa di-approve.',
            ]);
        }

        if (!$purchaseRequisition->suppliers()->exists()) {
            return redirect()->back()->withErrors([
                'vendor' => 'PR harus memiliki minimal 1 vendor sebelum bisa di-approve.',
            ]);
        }

        $purchaseRequisition->update([
            'status' => 'approved',
            'approved_by' => $user?->id,
            'approved_at' => now(),
        ]);

        return redirect()->route('purchase-requisition.index')->with('success', 'Purchase requisition berhasil di-approve.');
    }

    public function reject(Request $request, PurchaseRequisition $purchaseRequisition)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();
        $user?->loadMissing('department');

        if (!$this->isOwnerDepartmentUser($user)) {
            abort(403, 'Hanya department Owner yang dapat reject purchase requisition.');
        }

        if (strtolower(trim((string) $purchaseRequisition->status)) !== 'waiting') {
            return redirect()->back()->withErrors([
                'status' => 'Hanya PR dengan status waiting yang bisa di-reject.',
            ]);
        }

        $validated = $request->validate([
            'reject_note' => ['required', 'string', 'max:500'],
        ]);

        $purchaseRequisition->update([
            'status' => 'rejected',
            'reject_note' => $validated['reject_note'],
            'approved_by' => null,
            'approved_at' => null,
        ]);

        return redirect()->route('purchase-requisition.index')->with('success', 'Purchase requisition berhasil di-reject.');
    }

    public function syncSuppliers(Request $request, PurchaseRequisition $purchaseRequisition)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();
        $user?->loadMissing('department');

        if (!$this->isFatDepartmentUser($user)) {
            abort(403, 'Hanya departemen FAT yang dapat menambahkan vendor.');
        }

        $validated = $request->validate([
            'supplier_ids' => ['present', 'array', 'max:3'],
            'supplier_ids.*' => ['exists:suppliers,id'],
        ]);

        $purchaseRequisition->suppliers()->sync($validated['supplier_ids']);
        $this->syncSupplierComparisonRows($purchaseRequisition->fresh());

        return redirect()->route('purchase-requisition.show', $purchaseRequisition->id)
            ->with('success', 'Vendor berhasil diperbarui.');
    }

    public function saveSupplierComparisons(Request $request, PurchaseRequisition $purchaseRequisition)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();
        $user?->loadMissing('department');

        if (!$this->isFatDepartmentUser($user) && !$this->isOwnerDepartmentUser($user)) {
            abort(403, 'Hanya departemen FAT dan Owner yang dapat mengubah komparasi vendor.');
        }

        $validated = $request->validate([
            'comparisons' => ['required', 'array', 'min:1', 'max:3'],
            'comparisons.*.supplier_id' => ['required', 'exists:suppliers,id'],
            'comparisons.*.lead_time' => ['nullable', 'string', 'max:100'],
            'comparisons.*.payment_terms' => ['nullable', 'string', Rule::in(self::PAYMENT_METHOD_OPTIONS)],
            'comparisons.*.items' => ['required', 'array', 'min:1'],
            'comparisons.*.items.*.purchase_requisition_item_id' => ['required', 'integer', 'exists:purchase_requisition_items,id'],
            'comparisons.*.items.*.quoted_price' => ['nullable', 'numeric', 'min:0'],
            'comparisons.*.items.*.is_selected' => ['nullable', 'boolean'],
        ]);

        $purchaseRequisition->loadMissing([
            'items:id,purchase_requisition_id',
            'prSuppliers.supplier:id',
            'prSuppliers.itemQuotes:id,pr_supplier_id,purchase_requisition_item_id,quoted_price,is_selected',
        ]);

        $this->syncSupplierComparisonRows($purchaseRequisition);
        $purchaseRequisition->load([
            'items:id,purchase_requisition_id',
            'prSuppliers.itemQuotes:id,pr_supplier_id,purchase_requisition_item_id,quoted_price,is_selected',
        ]);

        $prSuppliers = $purchaseRequisition->prSuppliers->keyBy('supplier_id');
        $itemIds = $purchaseRequisition->items->pluck('id')->map(fn ($id) => (int) $id)->all();

        DB::transaction(function () use ($validated, $prSuppliers, $itemIds, $purchaseRequisition) {
            PurchaseRequisitionSupplierItemQuote::query()
                ->whereIn('purchase_requisition_item_id', $itemIds)
                ->update(['is_selected' => false]);

            foreach ($validated['comparisons'] as $comparison) {
                $supplierId = (int) $comparison['supplier_id'];
                /** @var PurchaseRequisitionSupplier|null $prSupplier */
                $prSupplier = $prSuppliers->get($supplierId);

                if (!$prSupplier) {
                    continue;
                }

                $prSupplier->update([
                    'lead_time' => $comparison['lead_time'] ?? null,
                    'payment_terms' => $comparison['payment_terms'] ?? null,
                ]);

                foreach ($comparison['items'] as $itemPayload) {
                    $itemId = (int) $itemPayload['purchase_requisition_item_id'];
                    if (!in_array($itemId, $itemIds, true)) {
                        continue;
                    }

                    PurchaseRequisitionSupplierItemQuote::query()->updateOrCreate(
                        [
                            'pr_supplier_id' => $prSupplier->id,
                            'purchase_requisition_item_id' => $itemId,
                        ],
                        [
                            'quoted_price' => array_key_exists('quoted_price', $itemPayload) && $itemPayload['quoted_price'] !== null && $itemPayload['quoted_price'] !== ''
                                ? $itemPayload['quoted_price']
                                : null,
                            'is_selected' => (bool) ($itemPayload['is_selected'] ?? false),
                        ]
                    );
                }
            }
        });

        return redirect()->route('purchase-requisition.show', $purchaseRequisition->id)
            ->with('success', 'Komparasi vendor berhasil disimpan.');
    }

    public function show(Request $request, PurchaseRequisition $purchaseRequisition)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();
        $user?->loadMissing(['department']);

        $isVisible = $this->visiblePurchaseRequisitionsQuery($user)
            ->where('id', $purchaseRequisition->id)
            ->exists();

        if (!$isVisible && (int) $purchaseRequisition->requested_by === (int) ($user?->id ?? 0)) {
            $isVisible = true;
        }

        if (!$isVisible) {
            abort(403, 'Anda tidak memiliki akses untuk melihat purchase requisition ini.');
        }

        $purchaseRequisition->load([
            'requester:id,name,department_id',
            'department:id,name,code',
            'approvedBy:id,name',
            'items:id,purchase_requisition_id,procurement_master_item_id,line_no,item_code,item_name,description_of_goods,specification,unit,quantity,required_date,price,product_name,uom,qty',
            'attachments:id,purchase_requisition_id,filename,path,mime_type,size,original_path,signed_path,signature_status,signed_by,signed_at,signature_meta',
            'suppliers:id,name,code,contact_person,phone,email',
            'prSuppliers.supplier:id,name,supplier_type,code,contact_person,phone,email',
            'prSuppliers.itemQuotes:id,pr_supplier_id,purchase_requisition_item_id,quoted_price,is_selected',
        ]);

        // Eager load signer for attachments
        $purchaseRequisition->load(['attachments.signer:id,name']);

        $canApprove = $this->canApprove($user, $purchaseRequisition);
        $canReject = $canApprove; // same condition: owner dept + status waiting
        $backUrl = $request->query('return_to');

        if (!is_string($backUrl) || $backUrl === '' || !str_starts_with($backUrl, '/')) {
            $backUrl = '/gmisl/procurement/purchase-requisition';
        }

        return Inertia::render('Procurement/PurchaseRequisition/Show', [
            'title' => 'Detail Purchase Requisition',
            'description' => 'Detail Purchase Requisition',
            'backUrl' => $backUrl,
            'purchaseRequisition' => [
                'id' => $purchaseRequisition->id,
                'pr_number' => $purchaseRequisition->pr_number,
                'pr_date' => optional($purchaseRequisition->pr_date)->toDateString(),
                'priority' => $purchaseRequisition->priority,
                'status' => $purchaseRequisition->status,
                'note' => $purchaseRequisition->note,
                'reject_note' => $purchaseRequisition->reject_note,
                'po_comment' => $purchaseRequisition->po_comment,
                'requested_by' => $purchaseRequisition->requested_by,
                'department_name' => optional($purchaseRequisition->department)->name,
                'department_code' => optional($purchaseRequisition->department)->code,
                'requester_name' => optional($purchaseRequisition->requester)->name,
                'created_at' => $purchaseRequisition->created_at?->format('Y-m-d H:i'),
                'approved_at' => $purchaseRequisition->approved_at?->format('Y-m-d H:i'),
                'approved_by_name' => optional($purchaseRequisition->approvedBy)->name,
                'items' => $purchaseRequisition->items
                    ->map(fn ($item) => $this->itemPayload($item))
                    ->values(),
                'attachments' => $purchaseRequisition->attachments
                    ->map(fn ($attachment) => [
                        'id' => $attachment->id,
                        'filename' => $attachment->filename,
                        'url' => $attachment->signed_url ?: Storage::disk('public')->url($attachment->path),
                        'original_url' => $attachment->original_url ?: Storage::disk('public')->url($attachment->path),
                        'signed_url' => $attachment->signed_url,
                        'signature_status' => $attachment->signature_status,
                        'signed_by_name' => optional($attachment->signer)->name,
                        'signed_at' => optional($attachment->signed_at)?->toDateTimeString(),
                        'signature_meta' => $attachment->signature_meta,
                        'mime_type' => $attachment->mime_type,
                        'size' => $this->formatFileSize((int) ($attachment->size ?? 0)),
                        'uploader_name' => optional($attachment->purchaseRequisition?->requester)->name, // fallback
                        'purchase_requisition_id' => $attachment->purchase_requisition_id,
                        'is_image' => $this->isImageFile($attachment->filename),
                    ])
                    ->values(),
'can_approve' => $canApprove,
                 'can_reject' => $canReject,
                 'suppliers' => $purchaseRequisition->suppliers
                    ->map(fn ($supplier) => [
                        'id' => $supplier->id,
                        'name' => $supplier->name,
                        'supplier_type' => $supplier->supplier_type,
                        'code' => $supplier->code,
                        'contact_person' => $supplier->contact_person,
                        'phone' => $supplier->phone,
                        'email' => $supplier->email,
                    ])
                    ->values(),
                'supplier_comparisons' => $this->supplierComparisonsPayload($purchaseRequisition),
            ],
            'uomOptions' => $this->uomOptions(),
            'masterItems' => $this->masterItemOptions(),
            'allSuppliers' => $this->supplierOptions(),
            'paymentMethodOptions' => self::PAYMENT_METHOD_OPTIONS,
            'currentUser' => $this->currentUserPayload($user),
        ]);
    }

    private function visiblePurchaseRequisitionsQuery(?User $user)
    {
        $departmentId = (int) ($user?->department_id ?? 0);
        $isOwnerUser = $this->isOwnerDepartmentUser($user);
        $isItUser = $this->isItDepartmentUser($user);
        $isFatUser = $this->isFatDepartmentUser($user);

        return PurchaseRequisition::query()
            ->where(function ($query) use ($departmentId, $isOwnerUser, $isItUser, $isFatUser, $user) {
                // IT and FAT department users have full visibility to all PRs
                if ($isItUser || $isFatUser) {
                    $query->whereNotNull('id');
                    return;
                }

                if ($departmentId > 0) {
                    $query->orWhere('department_id', $departmentId);
                }

                if ($user?->id) {
                    $query->orWhere('requested_by', (int) $user->id);
                }

                if ($isOwnerUser) {
                    $query->orWhereIn('status', ['waiting', 'approved', 'rejected']);
                }
            });
    }

    private function canApprove(?User $user, PurchaseRequisition $purchaseRequisition): bool
    {
        $suppliersLoaded = $purchaseRequisition->relationLoaded('suppliers')
            ? $purchaseRequisition->suppliers->isNotEmpty()
            : $purchaseRequisition->suppliers()->exists();

        return $this->isOwnerDepartmentUser($user)
            && strtolower(trim((string) $purchaseRequisition->status)) === 'waiting'
            && $suppliersLoaded;
    }

    private function canEdit(?User $user, PurchaseRequisition $purchaseRequisition): bool
    {
        return (int) ($user?->id ?? 0) > 0
            && (int) $purchaseRequisition->requested_by === (int) $user->id
            && strtolower(trim((string) $purchaseRequisition->status)) === 'waiting';
    }

    private function isOwnerDepartmentUser(?User $user): bool
    {
        return $this->departmentCode($user) === self::OWNER_DEPARTMENT_CODE;
    }

    private function isItDepartmentUser(?User $user): bool
    {
        return $this->departmentCode($user) === self::IT_DEPARTMENT_CODE;
    }

    private function isFatDepartmentUser(?User $user): bool
    {
        return $this->departmentCode($user) === self::FAT_DEPARTMENT_CODE;
    }

    private function departmentCode(?User $user): string
    {
        return strtoupper(trim((string) ($user?->department?->code ?? '')));
    }

    private function uomOptions()
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

    private function supplierOptions()
    {
        return Supplier::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'supplier_type', 'code', 'contact_person'])
            ->map(fn (Supplier $supplier) => [
                'id' => $supplier->id,
                'name' => $supplier->name,
                'supplier_type' => $supplier->supplier_type,
                'code' => $supplier->code,
                'contact_person' => $supplier->contact_person,
            ])
            ->values();
    }

    private function masterItemOptions()
    {
        return ProcurementMasterItem::query()
            ->where('is_active', true)
            ->orderBy('item_code')
            ->get([
                'id',
                'item_code',
                'item_name',
                'description_of_goods',
                'specification',
                'unit',
                'default_price',
            ])
            ->map(fn (ProcurementMasterItem $item) => [
                'id' => $item->id,
                'item_code' => $item->item_code,
                'item_name' => $item->item_name,
                'description_of_goods' => $item->description_of_goods,
                'specification' => $item->specification,
                'unit' => $item->unit,
                'default_price' => $item->default_price,
                'label' => $item->item_code . ' - ' . $item->item_name,
            ])
            ->values();
    }

    private function currentUserPayload(?User $user): array
    {
        return [
            'id' => $user?->id,
            'name' => $user?->name,
            'department_id' => $user?->department_id,
            'department_name' => $user?->department?->name,
            'department_code' => $user?->department?->code,
        ];
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

     public function destroy(Request $request, PurchaseRequisition $purchaseRequisition)
     {
         /** @var \App\Models\User|null $user */
         $user = $request->user();
         $user?->loadMissing('department');

         if (!$this->isItDepartmentUser($user)) {
             abort(403, 'Hanya departemen IT yang dapat menghapus purchase requisition.');
         }

         $purchaseRequisition->items()->delete();
         $purchaseRequisition->attachments()->delete();
         $purchaseRequisition->delete();

         return redirect()->route('purchase-requisition.index')->with('success', 'Purchase requisition berhasil dihapus.');
     }

     /**
      * Sign attachment (Owner only)
      * POST /purchase-requisitions/{purchaseRequisition}/attachments/{attachment}/sign
      */
     public function signAttachment(
         Request $request,
         PurchaseRequisition $purchaseRequisition,
         PurchaseRequisitionAttachment $attachment
     ) {
         /** @var \App\Models\User|null $user */
         $user = $request->user();
         $user?->loadMissing('department');

         // 1. Authorization: Only Owner department
         abort_unless($this->isOwnerDepartmentUser($user), 403,
             'Hanya departemen Owner yang dapat menandatangani attachment.');

         // 2. Verify attachment belongs to this PR
         abort_unless($attachment->purchase_requisition_id === $purchaseRequisition->id, 404,
             'Attachment tidak sesuai dengan purchase requisition ini.');

         // 3. PR must be in waiting or approved status
         abort_unless(in_array(strtolower(trim((string) $purchaseRequisition->status)), ['waiting', 'approved'], true), 403,
             'Hanya PR dengan status waiting atau approved yang bisa ditandatangani.');

         // 4. Attachment must be in pending status and be an image
         abort_unless($attachment->signature_status === 'pending', 403,
             'Attachment sudah ditandatangani atau ditolak.');
         abort_unless($this->isImageFile($attachment->filename), 403,
             'Hanya file gambar (JPG, PNG) yang bisa ditandatangani.');

         // 5. Validate input
         $validated = $request->validate([
             'signature_data' => ['required', 'string'],
             'position_x' => ['required', 'numeric', 'min:0', 'max:1'],
             'position_y' => ['required', 'numeric', 'min:0', 'max:1'],
             'scale' => ['nullable', 'numeric', 'min:0.1', 'max:5'],
             'output_format' => ['nullable', 'in:jpg,png,webp'],
         ]);

         // 6. Ensure original file still exists on the public disk
         if (!$attachment->path || !Storage::disk('public')->exists($attachment->path)) {
             abort(404, 'File asli attachment tidak ditemukan.');
         }

         // 7. Merge signature using service
         $mergeService = new ImageMergeService();

         try {
             $mergedBlob = $mergeService->merge(
                 documentPath: $attachment->path,
                 signatureBase64: $validated['signature_data'],
                 position: [
                     'x' => $validated['position_x'],
                     'y' => $validated['position_y'],
                 ],
                 scale: $validated['scale'] ?? 1.0,
                 outputFormat: $validated['output_format'] ?? 'jpg',
                 quality: 85
             );
         } catch (\Exception $e) {
             \Log::error('Signature merge failed for attachment ' . $attachment->id . ': ' . $e->getMessage());
             return back()->withErrors(['merge' => 'Gagal menggabungkan tanda tangan. Silakan coba lagi.']);
         }

         // 8. Save signed file
         $originalName = pathinfo($attachment->filename, PATHINFO_FILENAME);
         $signedFilename = sprintf(
             'signed_%d_%s_%s.jpg',
             $attachment->id,
             preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalName),
             now()->format('Ymd_His')
         );
         $signedPath = 'purchase-requisitions/signed/' . $signedFilename;

         Storage::disk('public')->put($signedPath, $mergedBlob);

         // 9. Keep original path reference (already in $attachment->path)
         // 10. Update attachment record
         $attachment->update([
             'original_path' => $attachment->path, // backup original location
             'signed_path' => $signedPath,
             'signature_status' => 'signed',
             'signed_by' => $user->id,
             'signed_at' => now(),
             'signature_meta' => [
                 'position' => [
                     'x' => $validated['position_x'],
                     'y' => $validated['position_y'],
                 ],
                 'scale' => $validated['scale'] ?? 1.0,
                 'output_format' => $validated['output_format'] ?? 'jpg',
                 'ip_address' => $request->ip(),
                 'user_agent' => $request->userAgent(),
             ],
         ]);

         // 11. Optional: Check if all attachments signed → could trigger auto-approval
         // $this->checkAndAutoApprovePr($purchaseRequisition);

         if ($request->expectsJson() || $request->wantsJson()) {
             return response()->json([
                 'message' => 'Attachment berhasil ditandatangani.',
                 'attachment' => [
                     'id' => $attachment->id,
                     'filename' => $attachment->filename,
                     'url' => $attachment->fresh()->signed_url ?: Storage::disk('public')->url($attachment->path),
                     'original_url' => $attachment->fresh()->original_url ?: Storage::disk('public')->url($attachment->path),
                     'signed_url' => $attachment->fresh()->signed_url,
                     'signature_status' => $attachment->signature_status,
                     'signed_by_name' => $user?->name,
                     'signed_at' => optional($attachment->signed_at)?->toDateTimeString(),
                     'purchase_requisition_id' => $attachment->purchase_requisition_id,
                 ],
             ]);
         }

         return redirect()
             ->back()
             ->with('success', 'Attachment berhasil ditandatangani.');
     }

     /**
      * Helper: Check if filename is an image
      */
     private function isImageFile(string $filename): bool
     {
         $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
         return in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
     }

     private function itemPayload(PurchaseRequisitionItem $item): array
     {
         return [
             'id' => $item->id,
             'procurement_master_item_id' => $item->procurement_master_item_id,
             'line_no' => $item->line_no,
             'item_code' => $item->item_code,
             'item_name' => $item->item_name ?: $item->product_name,
             'description_of_goods' => $item->description_of_goods ?: $item->product_name,
             'specification' => $item->specification,
             'unit' => $item->unit ?: $item->uom,
             'quantity' => $item->quantity ?? $item->qty,
             'required_date' => optional($item->required_date)->toDateString(),
             'price' => $item->price ?? 0,
             'product_name' => $item->item_name ?: $item->product_name,
             'uom' => $item->unit ?: $item->uom,
             'qty' => $item->quantity ?? $item->qty,
         ];
     }

     private function syncSupplierComparisonRows(PurchaseRequisition $purchaseRequisition): void
     {
         $purchaseRequisition->loadMissing([
             'items:id,purchase_requisition_id',
             'prSuppliers:id,purchase_requisition_id,supplier_id',
         ]);

         $itemIds = $purchaseRequisition->items->pluck('id')->all();

         foreach ($purchaseRequisition->prSuppliers as $prSupplier) {
             foreach ($itemIds as $itemId) {
                 PurchaseRequisitionSupplierItemQuote::query()->firstOrCreate([
                     'pr_supplier_id' => $prSupplier->id,
                     'purchase_requisition_item_id' => $itemId,
                 ]);
             }
         }
     }

     private function supplierComparisonsPayload(PurchaseRequisition $purchaseRequisition): array
     {
         $purchaseRequisition->loadMissing([
             'items:id,purchase_requisition_id,quantity',
             'prSuppliers.supplier:id,name,supplier_type,code,contact_person,phone,email',
             'prSuppliers.itemQuotes:id,pr_supplier_id,purchase_requisition_item_id,quoted_price,is_selected',
         ]);

         return $purchaseRequisition->prSuppliers
             ->map(function (PurchaseRequisitionSupplier $prSupplier) use ($purchaseRequisition) {
                 $quotesByItem = $prSupplier->itemQuotes->keyBy('purchase_requisition_item_id');
                 $itemsPayload = $purchaseRequisition->items
                     ->map(function (PurchaseRequisitionItem $item) use ($quotesByItem) {
                         /** @var PurchaseRequisitionSupplierItemQuote|null $quote */
                         $quote = $quotesByItem->get($item->id);

                             return [
                                 'purchase_requisition_item_id' => $item->id,
                                 'quoted_price' => $quote?->quoted_price,
                                 'quantity' => (float) ($item->quantity ?? 0),
                                 'is_selected' => (bool) ($quote?->is_selected ?? false),
                             ];
                         })
                     ->values();

                 $totalAmount = $itemsPayload->sum(
                     fn (array $item) => ((float) ($item['quoted_price'] ?? 0)) * ((float) ($item['quantity'] ?? 0))
                 );

                 return [
                     'supplier_id' => $prSupplier->supplier_id,
                     'pivot_id' => $prSupplier->id,
                     'name' => $prSupplier->supplier?->name,
                     'supplier_type' => $prSupplier->supplier?->supplier_type,
                     'code' => $prSupplier->supplier?->code,
                     'contact_person' => $prSupplier->supplier?->contact_person,
                     'phone' => $prSupplier->supplier?->phone,
                     'email' => $prSupplier->supplier?->email,
                     'lead_time' => $prSupplier->lead_time,
                     'payment_terms' => $prSupplier->payment_terms,
                     'total_amount' => $totalAmount,
                     'items' => $itemsPayload,
                 ];
             })
             ->values()
             ->all();
     }

     private function syncItems(PurchaseRequisition $purchaseRequisition, array $items): void
     {
         foreach (array_values($items) as $index => $item) {
             $purchaseRequisition->items()->create([
                 'procurement_master_item_id' => $item['procurement_master_item_id'] ?? null,
                 'line_no' => $index + 1,
                 'item_code' => trim((string) $item['item_code']),
                 'item_name' => trim((string) $item['item_name']),
                 'description_of_goods' => trim((string) $item['description_of_goods']),
                 'specification' => isset($item['specification']) ? trim((string) $item['specification']) : null,
                 'unit' => trim((string) $item['unit']),
                 'quantity' => $item['quantity'],
                 'required_date' => $item['required_date'],
                 'price' => $item['price'],
                 'product_name' => trim((string) $item['item_name']),
                 'uom' => trim((string) $item['unit']),
                 'qty' => $item['quantity'],
             ]);
         }
     }

     private function requiredDateErrors(array $items, Carbon $minimumRequiredDate): array
     {
         $errors = [];

         foreach (array_values($items) as $index => $item) {
             $requiredDate = isset($item['required_date']) ? Carbon::parse((string) $item['required_date'])->startOfDay() : null;

             if (!$requiredDate || $requiredDate->lt($minimumRequiredDate)) {
                 $errors["items.{$index}.required_date"] = 'Required Date minimal 3 hari setelah PR Date (' . $minimumRequiredDate->format('Y-m-d') . ').';
             }
         }

         return $errors;
     }

     private function allImageAttachmentsSigned(PurchaseRequisition $purchaseRequisition): bool
     {
         $purchaseRequisition->loadMissing('attachments');

         $imageAttachments = $purchaseRequisition->attachments->filter(
             fn (PurchaseRequisitionAttachment $attachment) => $this->isImageFile((string) $attachment->filename)
         );

         if ($imageAttachments->isEmpty()) {
             return true;
         }

         return $imageAttachments->every(
             fn (PurchaseRequisitionAttachment $attachment) => $attachment->signature_status === 'signed'
         );
     }
}
