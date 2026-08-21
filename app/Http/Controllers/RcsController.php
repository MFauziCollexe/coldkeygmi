<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\TPo;
use App\Models\TProduct;
use App\Models\TTally;
use App\Support\AccessRuleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class RcsController extends Controller
{
    private const ACCESS_MODULE = 'utility.rcs';

    public function index(Request $request): Response
    {
        $user = $request->user();
        $canAddPo = $this->accessRules()->allows($user, self::ACCESS_MODULE, 'add_po');
        $canAddTally = $this->accessRules()->allows($user, self::ACCESS_MODULE, 'add_tally');
        $canDeletePo = $this->accessRules()->allows($user, self::ACCESS_MODULE, 'delete_po');
        $canDeleteTally = $this->accessRules()->allows($user, self::ACCESS_MODULE, 'delete_tally');
        $canApprove = $this->accessRules()->allows($user, self::ACCESS_MODULE, 'approve');

        $customers = Customer::query()
            ->where('is_active', true)
            ->whereNotNull('customers_id_odoo')
            ->where('customers_id_odoo', '!=', '')
            ->orderBy('name')
            ->get(['id', 'customers_id_odoo', 'name']);

        $tallies = TPo::query()
            ->with('customer:id,customers_id_odoo,name')
            ->orderByDesc('created_at')
            ->get();

        $products = TProduct::query()
            ->whereNotNull('internal_reference')
            ->whereNotNull('name')
            ->orderBy('internal_reference')
            ->get(['id', 'customer_id', 'internal_reference', 'name']);

        $tallyMaxPallet = TTally::select('t_po_id', DB::raw('MAX(pallet) as max_pallet'))
            ->groupBy('t_po_id')
            ->pluck('max_pallet', 't_po_id');

        $finishedPoIds = TTally::where('is_finish', 1)
            ->pluck('t_po_id')
            ->unique()
            ->values()
            ->toArray();

        $tallyData = TTally::query()
            ->leftJoin('users', 'users.id', '=', 't_tally.user_tally')
            ->select('t_tally.id', 't_tally.t_po_id', 't_tally.item', 't_tally.pallet', 't_tally.kg', 't_tally.is_finish', 't_tally.startdate', 't_tally.enddate', 't_tally.user_tally', 'users.name as checker_name')
            ->orderBy('t_tally.t_po_id')
            ->orderBy('t_tally.pallet')
            ->get()
            ->groupBy('t_po_id');

        return Inertia::render('GMISL/Utility/Rcs/Index', [
            'customers' => $customers,
            'tallies' => $tallies,
            'products' => $products,
            'tallyMaxPallet' => $tallyMaxPallet,
            'finishedPoIds' => $finishedPoIds,
            'tallyData' => $tallyData,
            'canAddPo' => $canAddPo,
            'canAddTally' => $canAddTally,
            'canDeletePo' => $canDeletePo,
            'canDeleteTally' => $canDeleteTally,
            'canApprove' => $canApprove,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'po' => ['required', 'string'],
            'nopol' => ['required', 'string'],
            'driver' => ['required', 'string'],
            'customer_id' => ['required', 'exists:customers,customers_id_odoo'],
            'transaksi' => ['required', 'in:Inbound,Outbound'],
        ]);

        TPo::create($validated);

        session()->flash('success', 'Data tally berhasil disimpan.');

        return back();
    }

    public function storeTally(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            't_po_id' => ['required', 'exists:t_po,id'],
            'is_finish' => ['nullable', 'boolean'],
            'entries' => ['nullable', 'array'],
            'entries.*.item' => ['required_with:entries', 'string'],
            'entries.*.pallet' => ['required_with:entries', 'integer', 'min:1'],
            'entries.*.kg' => ['required_with:entries', 'numeric', 'min:0'],
            'deleted_ids' => ['nullable', 'array'],
            'deleted_ids.*' => ['integer'],
        ]);

        $isFinish = !empty($validated['is_finish']) ? 1 : 0;
        $now = now();

        if (!empty($validated['deleted_ids'])) {
            TTally::whereIn('id', $validated['deleted_ids'])
                ->where('t_po_id', $validated['t_po_id'])
                ->delete();
        }

        if (!empty($validated['entries'])) {
            $poHasStartdate = TTally::where('t_po_id', $validated['t_po_id'])
                ->whereNotNull('startdate')
                ->exists();

            foreach ($validated['entries'] as $entry) {
                $data = [
                    't_po_id' => $validated['t_po_id'],
                    'item' => $entry['item'],
                    'pallet' => $entry['pallet'],
                    'kg' => $entry['kg'],
                    'is_finish' => $isFinish,
                    'user_tally' => auth()->id(),
                ];

                if (!$poHasStartdate) {
                    $data['startdate'] = $now;
                }

                TTally::create($data);
            }
        }

        if ($isFinish) {
            TTally::where('t_po_id', $validated['t_po_id'])
                ->where('is_finish', 0)
                ->update(['is_finish' => 1, 'enddate' => $now]);
        }

        TTally::where('t_po_id', $validated['t_po_id'])
            ->update(['enddate' => $now]);

        session()->flash('success', 'Data tally berhasil disimpan.');

        return back();
    }

    public function destroyTally(Request $request, $id): RedirectResponse
    {
        $items = $request->input('items');

        $query = TTally::where('t_po_id', $id);

        if (!empty($items) && is_array($items)) {
            $query->whereIn('item', $items);
        }

        $query->delete();

        session()->flash('success', 'Data tally berhasil dihapus.');

        return back();
    }

    public function approveTally(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tallies' => ['required', 'array'],
            'tallies.*.po_id' => ['required', 'integer'],
            'tallies.*.items' => ['required', 'array'],
        ]);

        $finishedItems = [];
        $draftItems = [];

        foreach ($validated['tallies'] as $tally) {
            $poId = $tally['po_id'];
            $items = $tally['items'];

            foreach ($items as $itemName) {
                $hasFinished = TTally::where('t_po_id', $poId)
                    ->where('item', $itemName)
                    ->where('is_finish', 1)
                    ->exists();

                $hasDraft = TTally::where('t_po_id', $poId)
                    ->where('item', $itemName)
                    ->where('is_finish', 0)
                    ->exists();

                if ($hasFinished) {
                    TTally::where('t_po_id', $poId)
                        ->where('item', $itemName)
                        ->where('is_finish', 1)
                        ->update(['is_finish' => 0]);
                    $finishedItems[] = $itemName;
                } elseif ($hasDraft) {
                    $draftItems[] = $itemName;
                }
            }
        }

        if (!empty($finishedItems)) {
            session()->flash('success', 'Item berhasil di-approve ke status Draft.');
        }

        if (!empty($draftItems)) {
            session()->flash('draft_items', $draftItems);
        }

        return back();
    }

    public function destroy($id): RedirectResponse
    {
        TPo::where('id', $id)->delete();

        session()->flash('success', 'Data PO berhasil dihapus.');

        return back();
    }

    private function accessRules(): AccessRuleService
    {
        return app(AccessRuleService::class);
    }
}
