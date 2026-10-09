@php
    $cardTitles = ['apar' => 'KARTU PEMELIHARAAN APAR', 'smoke_detector' => 'KARTU PEMELIHARAAN SMOKE DETECTOR', 'fire_alarm' => 'KARTU PEMELIHARAAN FIRE ALARM'];
    $cardNames = ['apar' => 'APAR', 'smoke_detector' => 'Smoke Detector', 'fire_alarm' => 'Fire Alarm'];
    $monthOptions = [
        ['key' => 'jan', 'label' => 'Jan'], ['key' => 'feb', 'label' => 'Feb'], ['key' => 'mar', 'label' => 'Mar'],
        ['key' => 'apr', 'label' => 'Apr'], ['key' => 'may', 'label' => 'May'], ['key' => 'jun', 'label' => 'Jun'],
        ['key' => 'jul', 'label' => 'Jul'], ['key' => 'aug', 'label' => 'Aug'], ['key' => 'sep', 'label' => 'Sep'],
        ['key' => 'oct', 'label' => 'Oct'], ['key' => 'nov', 'label' => 'Nov'], ['key' => 'dec', 'label' => 'Dec'],
    ];
    $activeMonth = (string) ($form['active_month'] ?? 'jan');
    $records = is_array($form['location_records'] ?? null) ? $form['location_records'] : [];
    if (!$records) {
        $cardType = (string) ($form['card_type'] ?? 'fire_alarm');
        $locationId = trim((string) ($form['location'] ?? ''));
        $records[$cardType.'::'.$locationId] = $form;
    }
    $locations = [
        'fire_alarm' => ['lobby_lantai_1' => 'Lantai 1 Dalam', 'area_office_lantai_2' => 'Lantai 2 Office', 'area_ruang_mesin' => 'Lantai 1 Belakang', 'area_lantai_3' => 'Lantai 3 Office'],
        'smoke_detector' => ['lantai_1_lobby' => 'Lantai 1 - Lobby', 'lantai_2_ruang_staff' => 'Lantai 2 - Ruang Staff', 'lantai_2_ruang_direktur' => 'Lantai 2 - Ruang Direktur', 'lantai_2_ruang_meeting' => 'Lantai 2 - Ruang Meeting', 'lantai_3_server' => 'Lantai 3 - R. Server', 'lantai_3_makan' => 'Lantai 3 - R. Makan', 'lantai_3_staff' => 'Lantai 3 - Staff', 'lantai_3_meeting' => 'Lantai 3 - R. Meeting'],
        'apar' => [
            'lantai_1_penghangat_powder_6kg_001' => 'Lantai 1 - R. Penghangat - Powder 6 Kg 001',
            'lantai_1_admin_powder_3kg_001' => 'Lantai 1 - R. Admin - Powder 3 Kg 001',
            'lantai_1_loading_dock_powder_6kg_002' => 'Lantai 1 - R. Loading Dock - Powder 6 Kg 002',
            'lantai_1_loading_dock_powder_6kg_003' => 'Lantai 1 - R. Loading Dock - Powder 6 Kg 003',
            'lantai_1_anteroom_powder_6kg_001' => 'Lantai 1 - R. Anteroom - Powder 6 Kg 001',
            'lantai_1_batrai_co2_5kg_001' => 'Lantai 1 - R. Batrai - CO2 5 Kg 001',
            'lantai_1_mesin_co2_5kg_002' => 'Lantai 1 - R. Mesin - CO2 5 Kg 002',
            'lantai_1_travo_pln_belakang_co2_5kg_001' => 'Lantai 1 - Travo PLN Belakang - CO2 5 Kg 001',
            'lantai_1_travo_pln_depan_co2_9kg_002' => 'Lantai 1 - Travo PLN Depan - CO2 9 Kg 002',
            'lantai_2_pantry_powder_6kg_005' => 'Lantai 2 - Pantry - Powder 6 Kg 005',
            'lantai_3_server_co2_5kg_003' => 'Lantai 3 - R. Server - CO2 5 Kg 003',
            'lantai_3_makan_powder_6kg_006' => 'Lantai 3 - R. Makan - Powder 6 Kg 006',
        ],
    ];
@endphp

@foreach($records as $recordKey => $locationState)
@php
    [$cardType, $locationId] = array_pad(explode('::', (string) $recordKey, 2), 2, '');
    if ($cardType === '') $cardType = (string) ($form['card_type'] ?? 'fire_alarm');
    if ($locationId === '') $locationId = trim((string) ($form['location'] ?? ''));
    $rows = is_array($locationState['rows'] ?? null) ? $locationState['rows'] : [];
    $checkDates = is_array($locationState['monthly_check_dates'] ?? null) ? $locationState['monthly_check_dates'] : [];
    $monthlyNotes = is_array($locationState['monthly_notes'] ?? null) ? $locationState['monthly_notes'] : [];
    $activeNote = $monthlyNotes[$activeMonth] ?? '';
    $locationLabel = $locations[$cardType][$locationId] ?? ucwords(str_replace('_', ' ', $locationId));
@endphp
<div class="form-page">
    @include('pdf.checklist-templates.partials.hse_brand_header', ['entry' => $entry, 'subtitle' => 'Kartu pemeliharaan APAR, smoke detector, dan fire alarm'])
    <table class="fire-controls"><tr><td>Jenis Kartu: <strong>{{ $cardNames[$cardType] ?? $cardType }}</strong></td><td>Bulan Aktif: <strong>{{ collect($monthOptions)->firstWhere('key', $activeMonth)['label'] ?? $activeMonth }}</strong></td></tr></table>
    <table class="fire-location"><tr><td><strong>{{ $cardTitles[$cardType] ?? 'KARTU PEMELIHARAAN' }}</strong></td></tr><tr><td><strong>Lokasi:</strong> {{ $locationLabel }}</td></tr></table>
    <table class="fire-matrix">
        <thead>
            <tr><th rowspan="2" class="fire-item">Item Check</th><th colspan="12">Tahun {{ $form['year'] ?? '-' }}</th></tr>
            <tr>@foreach($monthOptions as $month)<th class="{{ $month['key'] === $activeMonth ? 'active-month' : '' }}">{{ $month['label'] }}</th>@endforeach</tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                <tr><td class="fire-item">{{ $row['name'] ?? '' }}</td>
                    @foreach($monthOptions as $month)
                        @php $status = $row['months'][$month['key']] ?? ''; @endphp
                        <td class="center {{ $month['key'] === $activeMonth ? 'active-month' : '' }}">
                            @if($status === 'yes') <span class="check-yes">&#10003;</span>
                            @elseif($status === 'no') <span class="check-no">&#10005;</span>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
            <tr><td class="fire-item"><strong>Tanggal</strong></td>@foreach($monthOptions as $month)<td class="center active-date {{ $month['key'] === $activeMonth ? 'active-month' : '' }}">{{ $checkDates[$month['key']] ?? '' }}</td>@endforeach</tr>
        </tbody>
    </table>
    <div class="form-panel"><div class="form-panel-title">Keterangan Bulan {{ collect($monthOptions)->firstWhere('key', $activeMonth)['label'] ?? $activeMonth }}</div><div class="textarea-box">{{ trim((string) $activeNote) !== '' ? $activeNote : ' ' }}</div></div>
</div>
@endforeach
<style>
    @page { size: A4 landscape; }
    .fire-controls td { width: 50%; border: 1px solid #000; padding: 5px 8px; font-size: 8px; }
    .fire-location td { border: 1px solid #000; padding: 5px 8px; text-align: center; }
    .fire-location td:first-child { font-size: 11px; }
    .fire-matrix { table-layout: fixed; }
    .fire-matrix th, .fire-matrix td { padding: 4px 3px; font-size: 7px; }
    .fire-matrix .fire-item { width: 33%; }
    .fire-matrix td.center { text-align: center; }
    .fire-matrix th.active-month, .fire-matrix td.active-month { background: #fffbeb; }
</style>
