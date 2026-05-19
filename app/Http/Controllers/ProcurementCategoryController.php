<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RemembersIndexUrl;
use App\Models\ProcurementCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ProcurementCategoryController extends Controller
{
    use RemembersIndexUrl;

    public function index(Request $request)
    {
        $this->rememberIndexUrl($request, 'procurement-categories');

        $query = ProcurementCategory::query();

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->input('is_active') === '1');
        }

        return Inertia::render('MasterData/ProcurementCategory/Index', [
            'categories' => $query->orderBy('name')->paginate(10)->withQueryString(),
            'filters' => $request->only(['search', 'is_active', 'page']),
        ]);
    }

    public function create()
    {
        return Inertia::render('MasterData/ProcurementCategory/Create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:procurement_categories,name'],
            'is_active' => ['boolean'],
        ]);

        ProcurementCategory::create([
            'name' => trim((string) $data['name']),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return $this->redirectToRememberedIndex($request, 'procurement-categories', 'procurement-category.index')
            ->with('success', 'Master category berhasil dibuat.');
    }

    public function edit(ProcurementCategory $procurementCategory)
    {
        return Inertia::render('MasterData/ProcurementCategory/Edit', [
            'category' => $procurementCategory,
        ]);
    }

    public function update(Request $request, ProcurementCategory $procurementCategory)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('procurement_categories', 'name')->ignore($procurementCategory->id)],
            'is_active' => ['boolean'],
        ]);

        $procurementCategory->update([
            'name' => trim((string) $data['name']),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return $this->redirectToRememberedIndex($request, 'procurement-categories', 'procurement-category.index')
            ->with('success', 'Master category berhasil diperbarui.');
    }

    public function destroy(Request $request, ProcurementCategory $procurementCategory)
    {
        $procurementCategory->delete();

        return $this->redirectToRememberedIndex($request, 'procurement-categories', 'procurement-category.index')
            ->with('success', 'Master category berhasil dihapus.');
    }
}
