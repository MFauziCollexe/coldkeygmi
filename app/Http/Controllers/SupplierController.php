<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RemembersIndexUrl;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SupplierController extends Controller
{
    use RemembersIndexUrl;

    public function index(Request $request)
    {
        $this->rememberIndexUrl($request, 'suppliers');

        $query = Supplier::query();

        if ($request->filled('search')) {
            $search = $request->string('search')->trim();
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', '%' . $search . '%')
                    ->orWhere('code', 'like', '%' . $search . '%')
                    ->orWhere('contact_person', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        $suppliers = $query->orderBy('name')->paginate(10)->withQueryString();

        return Inertia::render('MasterData/Supplier/Index', [
            'suppliers' => $suppliers,
            'filters' => $request->only(['search', 'page']),
        ]);
    }

    public function create()
    {
        return Inertia::render('MasterData/Supplier/Create');
    }

    public function store(Request $request)
    {
        Supplier::create($this->validatePayload($request));

        return $this->redirectToRememberedIndex($request, 'suppliers', 'suppliers.index')
            ->with('success', 'Supplier berhasil dibuat.');
    }

    public function edit(Supplier $supplier)
    {
        return Inertia::render('MasterData/Supplier/Edit', [
            'supplier' => $supplier,
        ]);
    }

    public function update(Request $request, Supplier $supplier)
    {
        $supplier->update($this->validatePayload($request, $supplier->id));

        return $this->redirectToRememberedIndex($request, 'suppliers', 'suppliers.index')
            ->with('success', 'Supplier berhasil diperbarui.');
    }

    public function destroy(Request $request, Supplier $supplier)
    {
        $supplier->delete();

        return $this->redirectToRememberedIndex($request, 'suppliers', 'suppliers.index')
            ->with('success', 'Supplier berhasil dihapus.');
    }

    public function list(Request $request)
    {
        return Supplier::query()
            ->select('id', 'name', 'supplier_type', 'code', 'contact_person', 'phone', 'email')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    private function validatePayload(Request $request, ?int $supplierId = null): array
    {
        $codeRule = 'nullable|string|max:50|unique:suppliers,code';
        if ($supplierId) {
            $codeRule .= ',' . $supplierId;
        }

        return $request->validate([
            'name' => 'required|string|max:255',
            'supplier_type' => 'nullable|string|max:100',
            'code' => $codeRule,
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
    }
}
