@php
    $period = trim((string) ($form['period'] ?? ''));
    $periodDate = preg_match('/^\d{4}-\d{2}$/', $period) ? \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $period.'-01') : now()->startOfMonth();
    $days = range(1, $periodDate->daysInMonth);
    $rows = is_array($form['rows'] ?? null) ? $form['rows'] : [];
    $employees = is_array($form['generated_employees'] ?? null) ? $form['generated_employees'] : [];
    $groups = $employees ? $employees : [['rows' => $rows, 'leave_days' => []]];
    $genderLabels = ['male' => 'Laki-Laki', 'female' => 'Perempuan'];
    $rowNames = [
        'suhu_tubuh_tidak_panas' => 'Suhu tubuh tidak panas',
        'tidak_mempunyai_luka_terbuka' => 'Tidak mempunyai luka terbuka',
        'jaket_thermal_bersih' => 'Jaket thermal bersih',
        'sarung_tangan_bersih' => 'Sarung Tangan Bersih',
        'kuku_pendek_tidak_diwarnai' => 'Kuku pendek & tidak diwarnai/dicat',
        'tidak_memakai_perhiasan' => 'Tidak memakai perhiasan/aksesoris/jam tangan',
        'tidak_membawa_barang_pribadi' => 'Tidak membawa barang bawaan (barang pribadi) ke area warehouse',
        'tidak_membawa_makanan' => 'Tidak membawa makanan & minuman ke area warehouse (selain produk customer)',
        'rambut_rapi_pendek' => 'Rambut rapi & pendek untuk karyawan',
        'tidak_berjenggot' => 'Tidak berjenggot/cambang/kumis untuk karyawan',
        'tidak_memakai_bulu_mata' => 'Tidak memakai bulu mata palsu/eye shadow',
        'plester_perban_in' => 'Plester/Perban (In)',
        'plester_perban_out' => 'Plester/Perban (Out)',
    ];
@endphp

<div class="form-page">
    @include('pdf.checklist-templates.partials.hse_brand_header', ['entry' => $entry, 'subtitle' => 'Kartu checklist personal hygiene karyawan'])
    <div class="personal-document">No. Dokumen: FRM.HSE.09.01</div>
    <div class="personal-title">KARTU CHECKLIST PERSONAL HYGIENE</div>

    <table class="personal-info">
        <tr><td class="label">Tahun</td><td>{{ $form['year'] ?? $periodDate->format('Y') }}</td><td class="label">Bulan</td><td>{{ $period }}</td></tr>
        <tr><td class="label">Nama Karyawan</td><td colspan="3">{{ $form['employee_name'] ?? '-' }}</td></tr>
        <tr><td class="label">Jenis Kelamin</td><td colspan="3"><span class="gender-box">{{ ($form['gender'] ?? '') === 'male' ? '&#10003;' : '&nbsp;' }}</span> Laki-Laki&nbsp;&nbsp;&nbsp; <span class="gender-box">{{ ($form['gender'] ?? '') === 'female' ? '&#10003;' : '&nbsp;' }}</span> Perempuan</td></tr>
        <tr><td class="label">NIK</td><td colspan="3">{{ $form['nik'] ?? '-' }}</td></tr>
        <tr><td class="label">Bagian</td><td colspan="3">{{ $form['bagian'] ?? '-' }}</td></tr>
    </table>

    @if($employees)
        <div class="generated-note">Generate bulanan untuk {{ count($employees) }} karyawan aktif. Kotak merah untuk Minggu, tanggal merah, dan cuti dibiarkan kosong.</div>
    @endif

    @foreach($groups as $group)
        @if($employees)
            <table class="employee-heading"><tr><td>Nama: {{ $group['name'] ?? '-' }}</td><td>NIK: {{ $group['nik'] ?? '-' }}</td><td>Gender: {{ $genderLabels[strtolower((string) ($group['gender'] ?? ''))] ?? ($group['gender'] ?? '-') }}</td><td>Position: {{ $group['position'] ?? '-' }}</td></tr></table>
        @endif
        <table class="personal-matrix">
            <thead>
                <tr><th rowspan="2" class="parameter">PARAMETER</th><th colspan="{{ count($days) }}">TANGGAL</th></tr>
                <tr>@foreach($days as $day)<th class="day {{ $periodDate->copy()->day($day)->isSunday() || in_array($day, $group['leave_days'] ?? [], true) ? 'red-day' : '' }}">{{ $day }}</th>@endforeach</tr>
            </thead>
            <tbody>
                @foreach(($group['rows'] ?? []) as $rowIndex => $row)
                    @php $rowId = (string) ($row['id'] ?? ''); @endphp
                    <tr><td class="parameter">{{ $row['name'] ?? $rowNames[$rowId] ?? '' }}</td>
                        @foreach($days as $day)
                            @php $isUnavailable = $periodDate->copy()->day($day)->isSunday() || in_array($day, $group['leave_days'] ?? [], true); $status = $row['days'][$day] ?? ''; @endphp
                            <td class="day {{ $isUnavailable ? 'red-day' : '' }}">@if($status === 'yes') &#10003; @elseif($status === 'no') &#10005; @endif</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach
</div>
<style>
    @page { size: A4 landscape; }
    .personal-document { margin: 0 0 5px 80px; font-size: 8px; }
    .personal-title { margin-bottom: 8px; padding: 7px; background: #000; color: #fff; text-align: center; font-size: 17px; font-weight: 700; }
    .personal-info { table-layout: fixed; margin-bottom: 8px; }
    .personal-info td { padding: 5px 7px; }
    .personal-info .label { width: 17%; font-weight: 700; }
    .gender-box { display: inline-block; width: 10px; height: 10px; border: 1px solid #000; text-align: center; line-height: 8px; }
    .generated-note { margin: 7px 0; padding: 6px 8px; border: 1px solid #bae6fd; background: #f0f9ff; font-size: 8px; }
    .employee-heading { margin: 8px 0 0; }
    .employee-heading td { background: #f1f5f9; font-weight: 700; }
    .personal-matrix { table-layout: fixed; margin: 0 0 8px; }
    .personal-matrix th, .personal-matrix td { padding: 3px 2px; text-align: center; font-size: 6.4px; }
    .personal-matrix .parameter { width: 25%; text-align: left; font-weight: 700; }
    .personal-matrix th.parameter { text-align: center; }
    .personal-matrix .day { width: 2.42%; }
    .personal-matrix .red-day { background: #fee2e2; color: #b91c1c; }
</style>
