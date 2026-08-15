<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\TPo;
use App\Models\TProduct;
use App\Models\TTally;
use Illuminate\Http\Request;
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

        return Inertia::render('GMISL/Utility/Rcs/Index', [
            'customers' => $customers,
            'tallies' => $tallies,
            'products' => $products,
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
            'entries' => ['required', 'array', 'min:1'],
            'entries.*.item' => ['required', 'string'],
            'entries.*.pallet' => ['required', 'integer', 'min:1'],
            'entries.*.kg' => ['required', 'numeric', 'min:0'],
        ]);

        foreach ($validated['entries'] as $entry) {
            TTally::create([
                't_po_id' => $validated['t_po_id'],
                'item' => $entry['item'],
                'pallet' => $entry['pallet'],
                'kg' => $entry['kg'],
            ]);
        }

        session()->flash('success', 'Data tally berhasil disimpan.');

        return back();
    }
}
