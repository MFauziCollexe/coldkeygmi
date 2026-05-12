<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RemembersIndexUrl;
use App\Models\PurchaseRequisitionAttachment;
use App\Models\PurchaseRequisition;
use App\Models\StockCardUnit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class PurchaseRequisitionController extends Controller
{
    use RemembersIndexUrl;

    private const ACCESS_MODULE = 'gmisl.procurement.purchase_requisition';
    private const OWNER_DEPARTMENT_CODE = 'OWNER';

    public function index(Request $request)
    {
        $this->rememberIndexUrl($request, 'purchase_requisitions');

        /** @var \App\Models\User|null $user */
        $user = $request->user();
        $user?->loadMissing(['department']);
        $purchaseRequisitions = $this->visiblePurchaseRequisitionsQuery($user)
            ->with([
                'requester:id,name,department_id',
                'department:id,name,code',
                'approvedBy:id,name',
                'items:id,purchase_requisition_id,product_name,uom,qty',
                'attachments:id,purchase_requisition_id,filename,path,mime_type,size',
            ])
            ->orderByRaw("CASE WHEN status = 'waiting' THEN 0 WHEN status = 'approved' THEN 1 WHEN status = 'process' THEN 2 WHEN status = 'done' THEN 3 ELSE 4 END")
            ->orderByDesc('created_at')
            ->limit(50)
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
                        ->map(fn ($item) => [
                            'id' => $item->id,
                            'product_name' => $item->product_name,
                            'uom' => $item->uom,
                            'qty' => $item->qty,
                        ])
                        ->values(),
                    'attachments' => $purchaseRequisition->attachments
                        ->map(fn ($attachment) => [
                            'id' => $attachment->id,
                            'filename' => $attachment->filename,
                            'url' => Storage::disk('public')->url($attachment->path),
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
                'request_date' => $today,
                'priority' => 'medium',
                'department_id' => $user?->department_id,
                'status' => 'draft',
                'requestor' => $user?->name,
            ],
            'uomOptions' => $this->uomOptions(),
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
            'request_date' => ['required', 'date'],
            'priority' => ['required', 'in:medium,urgent,low'],
            'department_id' => ['required', 'exists:departments,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'note' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_name' => ['required', 'string', 'max:255'],
            'items.*.uom' => ['required', 'string', 'max:50', 'exists:stock_card_units,name'],
            'items.*.qty' => ['required', 'numeric', 'gt:0'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:10240'],
        ]);

        if ((int) $validated['department_id'] !== $userDepartmentId) {
            return redirect()->back()->withErrors([
                'department_id' => 'Department PR harus sesuai dengan department user pembuat.',
            ]);
        }

        DB::transaction(function () use ($request, $validated, $user, $userDepartmentId) {
            $purchaseRequisition = PurchaseRequisition::create([
                'pr_number' => PurchaseRequisition::generateNumber(now()),
                'pr_date' => now()->toDateString(),
                'request_date' => $validated['request_date'],
                'priority' => $validated['priority'],
                'requested_by' => $user->id,
                'department_id' => $userDepartmentId,
                'supplier_id' => $validated['supplier_id'] ?? null,
                'status' => 'waiting',
                'note' => $validated['note'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                $purchaseRequisition->items()->create([
                    'product_name' => $item['product_name'],
                    'uom' => $item['uom'],
                    'qty' => $item['qty'],
                ]);
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
            'items:id,purchase_requisition_id,product_name,uom,qty',
            'attachments:id,purchase_requisition_id,filename,path,mime_type,size',
        ]);

        return Inertia::render('Procurement/PurchaseRequisition/Edit', [
            'title' => 'Edit Purchase Requisition',
            'description' => 'Edit Purchase Requisition',
            'purchaseRequisition' => [
                'id' => $purchaseRequisition->id,
                'pr_number' => $purchaseRequisition->pr_number,
                'pr_date' => optional($purchaseRequisition->pr_date)->toDateString(),
                'request_date' => optional($purchaseRequisition->request_date)->toDateString(),
                'priority' => $purchaseRequisition->priority,
                'status' => $purchaseRequisition->status,
                'note' => $purchaseRequisition->note,
                'items' => $purchaseRequisition->items
                    ->map(fn ($item) => [
                        'id' => $item->id,
                        'product_name' => $item->product_name,
                        'uom' => $item->uom,
                        'qty' => $item->qty,
                    ])
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
            'request_date' => ['required', 'date'],
            'priority' => ['required', 'in:medium,urgent,low'],
            'department_id' => ['required', 'exists:departments,id'],
            'note' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_name' => ['required', 'string', 'max:255'],
            'items.*.uom' => ['required', 'string', 'max:50', 'exists:stock_card_units,name'],
            'items.*.qty' => ['required', 'numeric', 'gt:0'],
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

        DB::transaction(function () use ($request, $validated, $purchaseRequisition) {
            $purchaseRequisition->update([
                'request_date' => $validated['request_date'],
                'priority' => $validated['priority'],
                'note' => $validated['note'] ?? null,
            ]);

            $purchaseRequisition->items()->delete();

            foreach ($validated['items'] as $item) {
                $purchaseRequisition->items()->create([
                    'product_name' => $item['product_name'],
                    'uom' => $item['uom'],
                    'qty' => $item['qty'],
                ]);
            }

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

        $purchaseRequisition->update([
            'status' => 'approved',
            'approved_by' => $user?->id,
            'approved_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Purchase requisition berhasil di-approve.');
    }

    public function show(Request $request, PurchaseRequisition $purchaseRequisition)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();
        $user?->loadMissing(['department']);

        if (!$this->visiblePurchaseRequisitionsQuery($user)
            ->where('id', $purchaseRequisition->id)
            ->exists()) {
            abort(403, 'Anda tidak memiliki akses untuk melihat purchase requisition ini.');
        }

        $purchaseRequisition->load([
            'requester:id,name,department_id',
            'department:id,name,code',
            'approvedBy:id,name',
            'items:id,purchase_requisition_id,product_name,uom,qty',
            'attachments:id,purchase_requisition_id,filename,path,mime_type,size',
        ]);

        return Inertia::render('Procurement/PurchaseRequisition/Show', [
            'title' => 'Detail Purchase Requisition',
            'description' => 'Detail Purchase Requisition',
            'purchaseRequisition' => [
                'id' => $purchaseRequisition->id,
                'pr_number' => $purchaseRequisition->pr_number,
                'pr_date' => optional($purchaseRequisition->pr_date)->toDateString(),
                'request_date' => optional($purchaseRequisition->request_date)->toDateString(),
                'priority' => $purchaseRequisition->priority,
                'status' => $purchaseRequisition->status,
                'note' => $purchaseRequisition->note,
                'po_comment' => $purchaseRequisition->po_comment,
                'department_name' => optional($purchaseRequisition->department)->name,
                'department_code' => optional($purchaseRequisition->department)->code,
                'requester_name' => optional($purchaseRequisition->requester)->name,
                'created_at' => $purchaseRequisition->created_at?->format('Y-m-d H:i'),
                'approved_at' => $purchaseRequisition->approved_at?->format('Y-m-d H:i'),
                'approved_by_name' => optional($purchaseRequisition->approvedBy)->name,
                'items' => $purchaseRequisition->items
                    ->map(fn ($item) => [
                        'id' => $item->id,
                        'product_name' => $item->product_name,
                        'uom' => $item->uom,
                        'qty' => $item->qty,
                    ])
                    ->values(),
                'attachments' => $purchaseRequisition->attachments
                    ->map(fn ($attachment) => [
                        'id' => $attachment->id,
                        'filename' => $attachment->filename,
                        'url' => Storage::disk('public')->url($attachment->path),
                        'mime_type' => $attachment->mime_type,
                        'size' => $this->formatFileSize((int) ($attachment->size ?? 0)),
                    ])
                    ->values(),
            ],
            'uomOptions' => $this->uomOptions(),
            'currentUser' => $this->currentUserPayload($user),
        ]);
    }

    private function visiblePurchaseRequisitionsQuery(?User $user)
    {
        $departmentId = (int) ($user?->department_id ?? 0);
        $isOwnerUser = $this->isOwnerDepartmentUser($user);

        return PurchaseRequisition::query()
            ->where(function ($query) use ($departmentId, $isOwnerUser, $user) {
                if ($departmentId > 0) {
                    $query->orWhere('department_id', $departmentId);
                }

                if ($user?->id) {
                    $query->orWhere('requested_by', (int) $user->id);
                }

                if ($isOwnerUser) {
                    $query->orWhereIn('status', ['waiting', 'approved']);
                }
            });
    }

    private function canApprove(?User $user, PurchaseRequisition $purchaseRequisition): bool
    {
        return $this->isOwnerDepartmentUser($user)
            && strtolower(trim((string) $purchaseRequisition->status)) === 'waiting';
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
}
