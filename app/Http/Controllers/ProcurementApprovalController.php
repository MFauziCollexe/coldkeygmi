<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequisition;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;

class ProcurementApprovalController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();
        $user?->loadMissing('department');

        abort_unless($this->isOwnerDepartmentUser($user), 403, 'Hanya departemen Owner yang dapat membuka approval procurement.');

        $purchaseRequisitions = PurchaseRequisition::query()
            ->with([
                'requester:id,name',
                'department:id,name,code',
                'prSuppliers.supplier:id,name',
                'prSuppliers.itemQuotes:id,pr_supplier_id,purchase_requisition_item_id,quoted_price,is_selected',
            ])
            ->where('status', 'waiting')
            ->whereHas('prSuppliers')
            ->orderByDesc('created_at')
            ->get()
            ->filter(function (PurchaseRequisition $purchaseRequisition) {
                return $purchaseRequisition->prSuppliers->contains(function ($prSupplier) {
                    return $prSupplier->itemQuotes->contains(fn ($quote) => $quote->quoted_price !== null);
                });
            })
            ->map(function (PurchaseRequisition $purchaseRequisition) {
                $selectedCount = $purchaseRequisition->prSuppliers
                    ->flatMap(fn ($prSupplier) => $prSupplier->itemQuotes)
                    ->where('is_selected', true)
                    ->count();

                return [
                    'id' => $purchaseRequisition->id,
                    'pr_number' => $purchaseRequisition->pr_number,
                    'pr_date' => optional($purchaseRequisition->pr_date)->toDateString(),
                    'requester_name' => optional($purchaseRequisition->requester)->name,
                    'department_name' => optional($purchaseRequisition->department)->name,
                    'status' => $purchaseRequisition->status,
                    'supplier_count' => $purchaseRequisition->prSuppliers->count(),
                    'selected_count' => $selectedCount,
                    'has_selection' => $selectedCount > 0,
                ];
            })
            ->values();

        return Inertia::render('Procurement/Approval/Index', [
            'title' => 'Procurement Approval',
            'description' => 'Procurement Approval',
            'purchaseRequisitions' => $purchaseRequisitions,
            'currentUser' => [
                'id' => $user?->id,
                'name' => $user?->name,
                'department_code' => $user?->department?->code,
                'department_name' => $user?->department?->name,
            ],
        ]);
    }

    private function isOwnerDepartmentUser(?User $user): bool
    {
        return strtoupper(trim((string) ($user?->department?->code ?? ''))) === 'OWNER';
    }
}
