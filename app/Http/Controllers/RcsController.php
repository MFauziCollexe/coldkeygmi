<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\TPo;
use App\Models\TProduct;
use App\Models\TTally;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class RcsController extends Controller
{
    public function index(): Response
    {
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

        $tallyData = TTally::select('t_po_id', 'item', 'pallet', 'kg', 'is_finish')
            ->orderBy('t_po_id')
            ->orderBy('pallet')
            ->get()
            ->groupBy('t_po_id');

        return Inertia::render('GMISL/Utility/Rcs/Index', [
            'customers' => $customers,
            'tallies' => $tallies,
            'products' => $products,
            'tallyMaxPallet' => $tallyMaxPallet,
            'finishedPoIds' => $finishedPoIds,
            'tallyData' => $tallyData,
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
        ]);

        $isFinish = !empty($validated['is_finish']) ? 1 : 0;

        if (!empty($validated['entries'])) {
            foreach ($validated['entries'] as $entry) {
                TTally::create([
                    't_po_id' => $validated['t_po_id'],
                    'item' => $entry['item'],
                    'pallet' => $entry['pallet'],
                    'kg' => $entry['kg'],
                    'is_finish' => $isFinish,
                ]);
            }
        }

        if ($isFinish) {
            TTally::where('t_po_id', $validated['t_po_id'])
                ->where('is_finish', 0)
                ->update(['is_finish' => 1]);
        }

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

    public function destroy($id): RedirectResponse
    {
        TPo::where('id', $id)->delete();

        session()->flash('success', 'Data PO berhasil dihapus.');

        return back();
    }
}
