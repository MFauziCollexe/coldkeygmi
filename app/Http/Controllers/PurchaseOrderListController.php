<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequisition;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class PurchaseOrderListController extends Controller
{
    private const ACCESS_MODULE = 'gmisl.procurement.purchase_order';
    private const FAT_DEPARTMENT_CODE = 'FAT';

    /**
     * Display Purchase Order index page
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();
        $user?->loadMissing('department');
        $search = trim((string) $request->query('search', ''));

        $purchaseOrders = PurchaseRequisition::query()
            ->with([
                'requester:id,name,department_id',
                'department:id,name,code',
                'approvedBy:id,name',
                'items:id,purchase_requisition_id,product_name,uom,qty',
                'attachments:id,purchase_requisition_id,filename,path,mime_type,size',
            ])
            ->whereIn('status', ['approved', 'process'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('pr_number', 'like', '%' . $search . '%')
                        ->orWhereHas('requester', fn ($requesterQuery) => $requesterQuery->where('name', 'like', '%' . $search . '%'))
                        ->orWhereHas('department', fn ($departmentQuery) => $departmentQuery->where('name', 'like', '%' . $search . '%'));
                });
            })
            ->orderByRaw("CASE WHEN status = 'approved' THEN 0 WHEN status = 'process' THEN 1 ELSE 2 END")
            ->orderByDesc('approved_at')
            ->orderByDesc('created_at')
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
                    'requester_name' => optional($purchaseRequisition->requester)->name,
                    'approved_at' => $purchaseRequisition->approved_at?->format('Y-m-d H:i'),
                    'approved_by_name' => optional($purchaseRequisition->approvedBy)->name,
                    'can_process' => $this->canProcess($user, $purchaseRequisition),
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

        $purchaseRequisition->update([
            'status' => 'process',
        ]);

        return redirect()->back()->with('success', 'Purchase order berhasil diproses oleh tim FAT.');
    }

    private function canProcess(?User $user, PurchaseRequisition $purchaseRequisition): bool
    {
        return $this->isFatDepartmentUser($user)
            && strtolower(trim((string) $purchaseRequisition->status)) === 'approved';
    }

    private function isFatDepartmentUser(?User $user): bool
    {
        return strtoupper(trim((string) ($user?->department?->code ?? ''))) === self::FAT_DEPARTMENT_CODE;
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
