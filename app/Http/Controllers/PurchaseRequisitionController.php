<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RemembersIndexUrl;
use App\Models\PurchaseRequisitionAttachment;
use App\Models\PurchaseRequisition;
use App\Models\StockCardUnit;
use App\Models\User;
use App\Services\ImageMergeService;
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
    private const IT_DEPARTMENT_CODE = 'IT';

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
                'items:id,purchase_requisition_id,product_name,uom,qty',
                'attachments:id,purchase_requisition_id,filename,path,mime_type,size',
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
                'reject_note' => $purchaseRequisition->reject_note,
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
            'items:id,purchase_requisition_id,product_name,uom,qty',
            'attachments:id,purchase_requisition_id,filename,path,mime_type,size,original_path,signed_path,signature_status,signed_by,signed_at,signature_meta',
        ]);

        // Eager load signer for attachments
        $purchaseRequisition->load(['attachments.signer:id,name']);

        $canApprove = $this->canApprove($user, $purchaseRequisition);
        $canReject = $canApprove; // same condition: owner dept + status waiting

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
                        'signed_url' => $attachment->signed_url,
                        'signature_status' => $attachment->signature_status,
                        'signed_by_name' => optional($attachment->signer)->name,
                        'signed_at' => optional($attachment->signed_at)?->toDateTimeString(),
                        'signature_meta' => $attachment->signature_meta,
                        'mime_type' => $attachment->mime_type,
                        'size' => $this->formatFileSize((int) ($attachment->size ?? 0)),
                        'uploader_name' => optional($attachment->purchaseRequisition?->requester)->name, // fallback
                        'purchase_requisition_id' => $attachment->purchase_requisition_id,
                    ])
                    ->values(),
                'can_approve' => $canApprove,
                'can_reject' => $canReject,
            ],
            'uomOptions' => $this->uomOptions(),
            'currentUser' => $this->currentUserPayload($user),
        ]);
    }

    private function visiblePurchaseRequisitionsQuery(?User $user)
    {
        $departmentId = (int) ($user?->department_id ?? 0);
        $isOwnerUser = $this->isOwnerDepartmentUser($user);
        $isItUser = $this->isItDepartmentUser($user);

        return PurchaseRequisition::query()
            ->where(function ($query) use ($departmentId, $isOwnerUser, $isItUser, $user) {
                // IT department users have full visibility to all PRs
                if ($isItUser) {
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

    private function isItDepartmentUser(?User $user): bool
    {
        return $this->departmentCode($user) === self::IT_DEPARTMENT_CODE;
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

         // 6. Get original file path
         $originalPath = storage_path('app/' . $attachment->path);
         if (!file_exists($originalPath)) {
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
}
