<?php

namespace App\Http\Controllers;

use App\Models\Pdam;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PdamController extends Controller
{
    public function index(Request $request)
    {
        $query = Pdam::query();

        $bulan = $request->get('bulan', now()->format('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', (string) $bulan)) {
            $bulan = now()->format('Y-m');
        }
        [$year, $month] = array_map('intval', explode('-', $bulan));
        $query->whereYear('tanggal', $year)->whereMonth('tanggal', $month);

        $records = $query
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('GMIUM/Pdam/Index', [
            'records' => $records,
            'filters' => [
                'bulan' => $bulan,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'meter' => ['required', 'numeric'],
            'foto' => ['nullable', 'image', 'max:5120'],
        ]);

        $now = now();
        $record = Pdam::firstOrNew(['tanggal' => $now->toDateString()]);

        if ((int) $now->format('H') < 12) {
            $record->jam_1 = $now->format('H:i');
            $record->meter_1 = $validated['meter'];
            $reading = '1';
            if ($request->hasFile('foto')) {
                $record->foto_path_1 = $request->file('foto')->store('pdam', 'public');
            }
        } else {
            $record->jam_2 = $now->format('H:i');
            $record->meter_2 = $validated['meter'];
            $reading = '2';
            if ($request->hasFile('foto')) {
                $record->foto_path_2 = $request->file('foto')->store('pdam', 'public');
            }
        }

        $record->save();

        return back()->with('success', "Data PDAM (pembacaan {$reading}) berhasil disimpan.");
    }
}
