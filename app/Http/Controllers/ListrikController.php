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

        $user = $request->user();
        $user?->loadMissing('department');

        return Inertia::render('GMIUM/Listrik/Index', [
            'records' => $records,
            'filters' => [
                'bulan' => $bulan,
                'lokasi' => $request->get('lokasi'),
            ],
            'currentUser' => [
                'id' => $user?->id,
                'name' => $user?->name,
                'department_code' => $user?->department?->code,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $isIT = $this->isIT($user);

        $validated = $request->validate([
            'lokasi' => ['required', 'in:GMI,CRMI,Office'],
            'lbp' => ['required', 'numeric'],
            'wbp' => ['required_if:lokasi,GMI', 'nullable', 'numeric'],
            'total' => ['required_if:lokasi,GMI', 'nullable', 'numeric'],
            'kvarh' => ['nullable', 'numeric'],
            'foto' => ['nullable', 'image', 'max:5120'],
            'foto_1' => ['nullable', 'image', 'max:5120'],
            'foto_2' => ['nullable', 'image', 'max:5120'],
            'foto_3' => ['nullable', 'image', 'max:5120'],
            'foto_4' => ['nullable', 'image', 'max:5120'],
        ]);

        if ($isIT) {
            $validated += $request->validate([
                'tanggal' => ['nullable', 'date', 'date_format:Y-m-d'],
                'jam' => ['nullable', 'date_format:H:i'],
            ]);
        }

        $now = now();
        $data = [
            'tanggal' => $isIT && !empty($validated['tanggal']) ? $validated['tanggal'] : $now->toDateString(),
            'jam' => $isIT && !empty($validated['jam']) ? $validated['jam'] : $now->format('H:i'),
            'lbp' => $validated['lbp'],
            'wbp' => $validated['wbp'] ?? null,
            'total' => $validated['total'] ?? null,
            'kvarh' => $validated['kvarh'] ?? null,
            'foto_path' => null,
            'foto_path_2' => null,
            'foto_path_3' => null,
            'foto_path_4' => null,
        ];

        if ($request->hasFile('foto')) {
            $data['foto_path'] = $request->file('foto')->store('listrik', 'public');
        }
        if ($request->hasFile('foto_1')) {
            $data['foto_path'] = $request->file('foto_1')->store('listrik', 'public');
        }
        if ($request->hasFile('foto_2')) {
            $data['foto_path_2'] = $request->file('foto_2')->store('listrik', 'public');
        }
        if ($request->hasFile('foto_3')) {
            $data['foto_path_3'] = $request->file('foto_3')->store('listrik', 'public');
        }
        if ($request->hasFile('foto_4')) {
            $data['foto_path_4'] = $request->file('foto_4')->store('listrik', 'public');
        }

        if ($validated['lokasi'] === 'GMI') {
            Listrik::create(array_merge(['lokasi' => 'GMI'], $data));

            return back()->with('success', 'Data listrik GMI berhasil disimpan.');
        }

        // CRMI / Office: satu pencatatan per tanggal
        unset($data['foto_path_2'], $data['foto_path_3'], $data['foto_path_4']);
        $record = Listrik::firstOrNew([
            'lokasi' => $validated['lokasi'],
            'tanggal' => $data['tanggal'],
        ]);
        $record->fill($data);
        $record->save();

        return back()->with('success', 'Data listrik berhasil disimpan.');
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        $isIT = $this->isIT($user);

        if (!$isIT) {
            return back()->with('error', 'Hanya role IT yang dapat mengubah data listrik.');
        }

        $record = Listrik::findOrFail($id);

        $validated = $request->validate([
            'lokasi' => ['required', 'in:GMI,CRMI,Office'],
            'lbp' => ['required', 'numeric'],
            'wbp' => ['required_if:lokasi,GMI', 'nullable', 'numeric'],
            'total' => ['required_if:lokasi,GMI', 'nullable', 'numeric'],
            'kvarh' => ['nullable', 'numeric'],
            'tanggal' => ['nullable', 'date', 'date_format:Y-m-d'],
            'jam' => ['nullable', 'date_format:H:i'],
            'foto' => ['nullable', 'image', 'max:5120'],
            'foto_1' => ['nullable', 'image', 'max:5120'],
            'foto_2' => ['nullable', 'image', 'max:5120'],
            'foto_3' => ['nullable', 'image', 'max:5120'],
            'foto_4' => ['nullable', 'image', 'max:5120'],
        ]);

        $data = [
            'lokasi' => $validated['lokasi'],
            'tanggal' => !empty($validated['tanggal']) ? $validated['tanggal'] : $record->tanggal,
            'jam' => !empty($validated['jam']) ? $validated['jam'] : $record->jam,
            'lbp' => $validated['lbp'],
            'wbp' => $validated['wbp'] ?? null,
            'total' => $validated['total'] ?? null,
            'kvarh' => $validated['kvarh'] ?? null,
        ];

        if ($request->hasFile('foto')) {
            $data['foto_path'] = $request->file('foto')->store('listrik', 'public');
        }
        if ($request->hasFile('foto_1')) {
            $data['foto_path'] = $request->file('foto_1')->store('listrik', 'public');
        }
        if ($request->hasFile('foto_2')) {
            $data['foto_path_2'] = $request->file('foto_2')->store('listrik', 'public');
        }
        if ($request->hasFile('foto_3')) {
            $data['foto_path_3'] = $request->file('foto_3')->store('listrik', 'public');
        }
        if ($request->hasFile('foto_4')) {
            $data['foto_path_4'] = $request->file('foto_4')->store('listrik', 'public');
        }

        $record->update($data);

        return back()->with('success', 'Data listrik berhasil diubah.');
    }

    protected function isIT($user): bool
    {
        if (!$user) {
            return false;
        }

        $user->loadMissing('department');

        return strtoupper(trim((string) $user->department?->code)) === 'IT';
    }
}