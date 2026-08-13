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
            'wbp' => ['required_if:lokasi,GMI', 'nullable', 'numeric'],
            'total' => ['required_if:lokasi,GMI', 'nullable', 'numeric'],
            'kvarh' => ['nullable', 'numeric'],
            'foto' => ['nullable', 'image', 'max:5120'],
        ]);

        $fotoPath = null;
        if ($request->hasFile('foto')) {
            $fotoPath = $request->file('foto')->store('listrik', 'public');
        }

        $now = now();
        $data = [
            'tanggal' => $now->toDateString(),
            'jam' => $now->format('H:i'),
            'lbp' => $validated['lbp'],
            'wbp' => $validated['wbp'] ?? null,
            'total' => $validated['total'] ?? null,
            'kvarh' => $validated['kvarh'] ?? null,
            'foto_path' => $fotoPath,
        ];

        if ($validated['lokasi'] === 'GMI') {
            Listrik::create(array_merge(['lokasi' => 'GMI'], $data));

            return back()->with('success', 'Data listrik berhasil disimpan.');
        }

        // CRMI / Office: satu pencatatan per tanggal
        $record = Listrik::firstOrNew([
            'lokasi' => $validated['lokasi'],
            'tanggal' => $now->toDateString(),
        ]);
        $record->fill($data);
        $record->save();

        return back()->with('success', 'Data listrik berhasil disimpan.');
    }
}
