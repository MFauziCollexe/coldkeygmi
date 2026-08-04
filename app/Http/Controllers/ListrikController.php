<?php

namespace App\Http\Controllers;

use App\Models\Listrik;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ListrikController extends Controller
{
    public function index(Request $request)
    {
        $query = Listrik::query();

        $bulan = $request->get('bulan', now()->format('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', (string) $bulan)) {
            $bulan = now()->format('Y-m');
        }
        [$year, $month] = array_map('intval', explode('-', $bulan));
        $query->whereYear('tanggal', $year)->whereMonth('tanggal', $month);

        if ($request->filled('lokasi')) {
            $query->where('lokasi', $request->get('lokasi'));
        }

        $records = $query
            ->orderByDesc('tanggal')
            ->orderByDesc('jam')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('GMIUM/Listrik/Index', [
            'records' => $records,
            'filters' => [
                'bulan' => $bulan,
                'lokasi' => $request->get('lokasi'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'lokasi' => ['required', 'in:GMI,CRMI,Office'],
            'lbp' => ['required', 'numeric'],
            'wbp' => ['required', 'numeric'],
            'total' => ['required', 'numeric'],
            'kvarh' => ['nullable', 'numeric'],
        ]);

        Listrik::create([
            'lokasi' => $validated['lokasi'],
            'tanggal' => now()->toDateString(),
            'jam' => now()->format('H:i'),
            'lbp' => $validated['lbp'],
            'wbp' => $validated['wbp'],
            'total' => $validated['total'],
            'kvarh' => $validated['kvarh'] ?? null,
        ]);

        return back()->with('success', 'Data listrik berhasil disimpan.');
    }
}
