<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Plugging;
use App\Models\VehicleType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class PluggingController extends Controller
{
    public function index(Request $request)
    {
        $query = Plugging::query()->with('user:id,name,email');
        $customers = Customer::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        if ($request->filled('tanggal')) {
            $query->whereDate('tanggal', $request->tanggal);
        }

        if ($request->filled('customer')) {
            $query->where('customer', 'like', '%' . $request->customer . '%');
        }

        $pluggings = $query->orderByDesc('tanggal')->orderByDesc('jam_mulai')->paginate(10)->withQueryString();

        $pluggings->getCollection()->transform(function ($row) {
            $row->signature_image_url = $row->signature_image ? Storage::url($row->signature_image) : null;
            return $row;
        });

        $today = now()->toDateString();
        $monthStart = now()->copy()->startOfMonth()->toDateString();
        $monthEnd = now()->copy()->endOfMonth()->toDateString();

        $totalPerHari = Plugging::whereDate('tanggal', $today)->count();
        $totalPerBulan = Plugging::whereBetween('tanggal', [$monthStart, $monthEnd])->count();
        $rataDurasi = Plugging::whereNotNull('durasi_menit')->avg('durasi_menit');

        return Inertia::render('GMIUM/Plugging/Index', [
            'pluggings' => $pluggings,
            'filters' => $request->only(['tanggal', 'customer', 'page']),
            'customers' => $customers,
            'report' => [
                'total_per_hari' => $totalPerHari,
                'total_per_bulan' => $totalPerBulan,
                'rata_durasi_menit' => $rataDurasi ? round($rataDurasi, 2) : 0,
            ],
        ]);
    }

    public function create()
    {
        $customers = Customer::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
        $vehicleTypes = VehicleType::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('GMIUM/Plugging/Create', [
            'customers' => $customers,
            'vehicleTypes' => $vehicleTypes,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tanggal' => 'required|date',
            'jam_mulai' => 'required|date_format:H:i',
            'jam_selesai' => 'nullable|date_format:H:i',
            'customer_id' => 'required|exists:customers,id',
            'no_container_no_polisi' => 'required|string|max:255',
            'suhu_awal' => 'required|numeric',
            'suhu_akhir' => 'nullable|numeric',
            'lokasi' => 'nullable|string|max:255',
            'transporter' => 'nullable|string|max:255',
            'nomor_dokumen' => 'nullable|string|max:255',
            'rencana_waktu_kedatangan' => 'nullable|string|max:255',
            'jumlah_kendaraan' => 'nullable|integer|min:0',
            'vehicle_type_id' => 'nullable|exists:vehicle_types,id',
            'vehicle_type_name' => 'nullable|string|max:255',
            'pintu_loading' => 'nullable|string|max:255',
            'keterangan' => 'nullable|string',
        ]);

        $customer = Customer::findOrFail($data['customer_id']);
        $data['customer'] = $customer->name;
        unset($data['customer_id']);
        $vehicleTypeName = trim((string) ($data['vehicle_type_name'] ?? ''));
        if (!empty($data['vehicle_type_id'])) {
            $vehicleType = VehicleType::find($data['vehicle_type_id']);
            $data['jenis_kendaraan'] = $vehicleType?->name;
        } elseif ($vehicleTypeName !== '') {
            $vehicleType = VehicleType::whereRaw('LOWER(name) = ?', [mb_strtolower($vehicleTypeName)])->first();
            if (!$vehicleType) {
                $vehicleType = VehicleType::create([
                    'name' => $vehicleTypeName,
                    'is_active' => true,
                ]);
            } elseif (!$vehicleType->is_active) {
                $vehicleType->update(['is_active' => true]);
            }
            $data['jenis_kendaraan'] = $vehicleType->name;
        } else {
            $data['jenis_kendaraan'] = null;
        }
        unset($data['vehicle_type_id']);
        unset($data['vehicle_type_name']);

        $data['durasi_menit'] = $this->calculateDurationMinutes($data['jam_mulai'], $data['jam_selesai'] ?? null);
        $data['user_id'] = Auth::id();
        $data['status'] = 'active';
        $data['lokasi'] = (string) ($data['lokasi'] ?? '');
        $data['pic_customer'] = null;
        $data['signature_image'] = null;

        Plugging::create($data);

        return redirect()->route('plugging.index')->with('success', 'Data plugging berhasil dibuat.');
    }

    public function edit(Plugging $plugging)
    {
        $plugging->signature_image_url = $plugging->signature_image ? Storage::url($plugging->signature_image) : null;
        $customers = Customer::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
        $selectedCustomer = Customer::query()->where('name', $plugging->customer)->first();
        $vehicleTypes = VehicleType::query()->orderBy('name')->get(['id', 'name']);
        $selectedVehicleType = VehicleType::query()->where('name', $plugging->jenis_kendaraan)->first();

        return Inertia::render('GMIUM/Plugging/Edit', [
            'plugging' => $plugging,
            'customers' => $customers,
            'selectedCustomerId' => $selectedCustomer?->id,
            'vehicleTypes' => $vehicleTypes,
            'selectedVehicleTypeId' => $selectedVehicleType?->id,
        ]);
    }

    public function update(Request $request, Plugging $plugging)
    {
        $data = $request->validate([
            'tanggal' => 'required|date',
            'jam_mulai' => 'required|date_format:H:i',
            'jam_selesai' => 'nullable|date_format:H:i',
            'customer_id' => 'required|exists:customers,id',
            'no_container_no_polisi' => 'required|string|max:255',
            'suhu_awal' => 'required|numeric',
            'suhu_akhir' => 'nullable|numeric',
            'lokasi' => 'nullable|string|max:255',
            'transporter' => 'nullable|string|max:255',
            'nomor_dokumen' => 'nullable|string|max:255',
            'rencana_waktu_kedatangan' => 'nullable|string|max:255',
            'jumlah_kendaraan' => 'nullable|integer|min:0',
            'vehicle_type_id' => 'nullable|exists:vehicle_types,id',
            'pintu_loading' => 'nullable|string|max:255',
            'keterangan' => 'nullable|string',
        ]);

        $customer = Customer::findOrFail($data['customer_id']);
        $data['customer'] = $customer->name;
        unset($data['customer_id']);
        if (!empty($data['vehicle_type_id'])) {
            $vehicleType = VehicleType::find($data['vehicle_type_id']);
            $data['jenis_kendaraan'] = $vehicleType?->name;
        } else {
            $data['jenis_kendaraan'] = null;
        }
        unset($data['vehicle_type_id']);

        $data['durasi_menit'] = $this->calculateDurationMinutes($data['jam_mulai'], $data['jam_selesai'] ?? null);
        $data['status'] = $plugging->status;
        $data['lokasi'] = array_key_exists('lokasi', $data) ? (string) ($data['lokasi'] ?? '') : $plugging->lokasi;
        $data['pic_customer'] = $plugging->pic_customer;
        $data['signature_image'] = $plugging->signature_image;

        $plugging->update($data);

        return redirect()->route('plugging.index')->with('success', 'Data plugging berhasil diperbarui.');
    }

    public function approvalIndex(Request $request)
    {
        $canApprove = $this->isOperationalManager(Auth::user());

        $query = Plugging::query()->with('user:id,name,email');

        if ($request->filled('tanggal')) {
            $query->whereDate('tanggal', $request->tanggal);
        }

        if ($request->filled('customer')) {
            $query->where('customer', 'like', '%' . $request->customer . '%');
        }

        $query->where('status', 'active');

        $pluggings = $query->orderByDesc('tanggal')->orderByDesc('jam_mulai')->paginate(10)->withQueryString();

        $pluggings->getCollection()->transform(function ($row) {
            $row->signature_image_url = $row->signature_image ? Storage::url($row->signature_image) : null;
            return $row;
        });

        return Inertia::render('GMIUM/Plugging/Approval', [
            'pluggings' => $pluggings,
            'filters' => $request->only(['tanggal', 'customer', 'page']),
            'canApprove' => $canApprove,
        ]);
    }

    public function approve(Request $request, Plugging $plugging)
    {
        if (!$this->isOperationalManager(Auth::user())) {
            return redirect()
                ->route('plugging.index')
                ->with('error', 'Hanya manager Operational yang dapat melakukan approval plugging.');
        }

        $data = $request->validate([
            'pic_customer' => 'required|string|max:255',
            'signature_image' => 'required|image|max:2048',
        ]);

        if ($request->hasFile('signature_image')) {
            if ($plugging->signature_image) {
                Storage::disk('public')->delete($plugging->signature_image);
            }

            $data['signature_image'] = $request->file('signature_image')->store('plugging-signatures', 'public');
        }

        $plugging->update([
            'pic_customer' => $data['pic_customer'],
            'signature_image' => $data['signature_image'] ?? $plugging->signature_image,
            'status' => 'selesai',
        ]);

        return redirect()->route('plugging.approval.index')->with('success', 'Plugging berhasil di-approve.');
    }

    public function destroy(Plugging $plugging)
    {
        $plugging->delete();
        return redirect()->route('plugging.index')->with('success', 'Data plugging berhasil dihapus.');
    }

    public function export(Request $request)
    {
        $query = Plugging::query()->with('user:id,name,email');

        if ($request->filled('tanggal')) {
            $query->whereDate('tanggal', $request->tanggal);
        }
        if ($request->filled('customer')) {
            $query->where('customer', 'like', '%' . $request->customer . '%');
        }

        $rows = $query->orderByDesc('tanggal')->orderByDesc('jam_mulai')->get();

        $filename = 'plugging-' . now()->format('Ymd-His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];

        $callback = function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Tanggal',
                'Jam Mulai',
                'Jam Selesai',
                'Durasi (menit)',
                'Customer',
                'Transporter',
                'Nomor Dokumen',
                'Rencana Waktu Kedatangan',
                'Jumlah Kendaraan',
                'Jenis Kendaraan',
                'No Container / No Polisi',
                'Pintu Loading',
                'Suhu Awal',
                'Suhu Akhir',
                'Lokasi',
                'Keterangan',
                'PIC Customer',
                'Status',
                'User',
                'Created At',
            ]);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    optional($row->tanggal)->format('Y-m-d'),
                    $row->jam_mulai,
                    $row->jam_selesai,
                    $row->durasi_menit,
                    $row->customer,
                    $row->transporter,
                    $row->nomor_dokumen,
                    $row->rencana_waktu_kedatangan,
                    $row->jumlah_kendaraan,
                    $row->jenis_kendaraan,
                    $row->no_container_no_polisi,
                    $row->pintu_loading,
                    $row->suhu_awal,
                    $row->suhu_akhir,
                    $row->lokasi,
                    $row->keterangan,
                    $row->pic_customer,
                    $row->status,
                    $row->user?->name,
                    $row->created_at,
                ]);
            }

            fclose($handle);
        };

        return Response::stream($callback, 200, $headers);
    }

    private function calculateDurationMinutes(string $startTime, ?string $endTime): ?int
    {
        if (!$endTime) {
            return null;
        }

        $start = Carbon::createFromFormat('H:i', $startTime);
        $end = Carbon::createFromFormat('H:i', $endTime);

        if ($end->lessThan($start)) {
            $end->addDay();
        }

        return $start->diffInMinutes($end);
    }

    private function isOperationalManager($user): bool
    {
        if (!$user) {
            return false;
        }

        $user->loadMissing(['department:id,name,code', 'position:id,is_manager']);

        $isManager = (bool) optional($user->position)->is_manager;
        $departmentCode = strtoupper((string) optional($user->department)->code);
        $departmentName = strtoupper((string) optional($user->department)->name);
        $isOperationalDept = $departmentCode === 'OPS'
            || str_contains($departmentName, 'OPPERATIONAL')
            || str_contains($departmentName, 'OPERATIONAL');

        return $isManager && $isOperationalDept;
    }
}
