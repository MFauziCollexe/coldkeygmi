@php
    $tid = $entry['template_id'] ?? '';
    $titleMap = [
        'kompresor_harian' => 'CHECKLIST HARIAN KOMPRESOR',
        'charger_baterai' => 'CHECKLIST HARIAN CHARGER BATERAI',
        'checklist_baterai' => 'CHECKLIST HARIAN BATERAI',
    ];
    $title = $titleMap[$tid] ?? 'CHECKLIST HARIAN';
    $noLabel = $tid === 'kompresor_harian' ? 'KOMPRESOR NO' : ($tid === 'charger_baterai' ? 'SERIAL NO' : 'BATTERY NO');
    $noField = $tid === 'kompresor_harian' ? 'compressor_no' : ($tid === 'charger_baterai' ? 'serial_no' : 'battery_no');
    $rows = $form['rows'] ?? [];
    $approvedDays = $form['approved_days'] ?? [];

    $sections_kompresor = [
        ['title' => 'A. STATUS MESIN', 'items' => [
            ['key' => 'status_mesin', 'label' => 'ON/OFF', 'type' => 'symbol'],
        ]],
        ['title' => 'B. VISUAL', 'items' => [
            ['key' => 'visual_bersih', 'label' => 'BERSIH', 'type' => 'symbol'],
            ['key' => 'visual_kotor', 'label' => 'KOTOR', 'type' => 'symbol'],
        ]],
        ['title' => 'C. PENGECEKAN', 'items' => [
            ['key' => 'tek_suct', 'label' => 'TEK. SUCT (Mpa)', 'type' => 'text'],
            ['key' => 'tek_disch', 'label' => 'TEK DISCH (Mpa)', 'type' => 'text'],
            ['key' => 'delta_tekanan_oli', 'label' => 'DELTA TEKANAN OLI (Mpa)', 'type' => 'text'],
            ['key' => 'check_1', 'label' => 'TEMP SUCT (deg C)', 'type' => 'text'],
            ['key' => 'check_2', 'label' => 'TEMP DISCH (deg C)', 'type' => 'text'],
            ['key' => 'check_3', 'label' => 'TEMP OLI (deg C)', 'type' => 'text'],
            ['key' => 'check_4', 'label' => 'LEVE OLI (%)', 'type' => 'text'],
        ]],
        ['title' => 'D. PERLAKUAN', 'items' => [
            ['key' => 'tambah_grease_motor', 'label' => 'TAMBAH GREASE MOTOR', 'type' => 'symbol'],
            ['key' => 'tambah_oli', 'label' => 'TAMBAH OLI (LITER)', 'type' => 'number'],
        ]],
        ['title' => 'E. HOURS METER', 'items' => [
            ['key' => 'hours_meter', 'label' => 'HOURS METER', 'type' => 'text'],
        ]],
    ];

    $sections_charger = [
        ['title' => 'A. KONDISI FISIK', 'items' => [
            ['key' => 'switch_on_off', 'label' => 'Switch ON/OFF', 'type' => 'symbol'],
            ['key' => 'kondisi_fisik', 'label' => 'Kondisi Fisik', 'type' => 'symbol'],
            ['key' => 'kabel_konektor', 'label' => 'Kabel & Konektor', 'type' => 'symbol'],
            ['key' => 'legrand', 'label' => 'Legrand', 'type' => 'symbol'],
            ['key' => 'display_charger', 'label' => 'Display Charger', 'type' => 'symbol'],
            ['key' => 'temuan', 'label' => 'Temuan', 'type' => 'symbol'],
        ]],
        ['title' => 'B. TINDAKAN', 'items' => [
            ['key' => 'tindakan', 'label' => 'Tindakan', 'type' => 'text'],
        ]],
    ];

    $sections_baterai = [
        ['title' => 'A. PENGECEKAN', 'items' => [
            ['key' => 'level_elektrolit', 'label' => 'Level Elektrolit', 'type' => 'symbol'],
            ['key' => 'kabel_konektor', 'label' => 'Kabel & Konektor', 'type' => 'symbol'],
            ['key' => 'cover_pelampung', 'label' => 'Cover Pelampung', 'type' => 'symbol'],
            ['key' => 'kebersihan_baterai', 'label' => 'Kebersihan Baterai', 'type' => 'symbol'],
            ['key' => 'voltage_dc', 'label' => 'Voltage DC', 'type' => 'symbol'],
        ]],
    ];

    $sectionMap = [
        'kompresor_harian' => $sections_kompresor,
        'charger_baterai' => $sections_charger,
        'checklist_baterai' => $sections_baterai,
    ];
    $sections = $sectionMap[$tid] ?? $sections_kompresor;
@endphp

<table>
    <tr>
        <td colspan="2" class="text-center font-bold" style="font-size:12px;">{{ $title }}</td>
    </tr>
    <tr>
        <td style="width:50%"><strong>{{ $noLabel }}:</strong> {{ $form[$noField] ?? '-' }}</td>
        <td style="width:50%"><strong>Doc:</strong> {{ $form['document_no'] ?? '-' }}</td>
    </tr>
    <tr>
        <td><strong>Periode:</strong> {{ $form['period'] ?? '-' }}</td>
        <td><strong>PIC:</strong> {{ $form['pic'] ?? '-' }}</td>
    </tr>
</table>

<table>
    <thead>
        <tr>
            <th style="width:8%">No</th>
            <th style="width:52%">ITEM</th>
            @foreach($rows as $row)
                <th style="width:3%; font-size:7px;">{{ \Carbon\Carbon::parse($row['date'] ?? '')->format('d') }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($sections as $section)
            <tr>
                <td colspan="{{ 2 + count($rows) }}" class="font-bold" style="background:#f3f4f6;">{{ $section['title'] }}</td>
            </tr>
            @foreach($section['items'] as $item)
                <tr>
                    <td class="text-center">{{ $item['no'] ?? $loop->iteration }}</td>
                    <td>{{ $item['label'] }}</td>
                    @foreach($rows as $row)
                        <td class="text-center">
                            @php $val = $row[$item['key']] ?? null; @endphp
                            @if($item['type'] === 'symbol')
                                @if($val === 'yes') <span class="check-yes">✓</span>
                                @elseif($val === 'no') <span class="check-no">✕</span>
                                @endif
                            @else
                                {{ $val }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        @endforeach
    </tbody>
</table>

@if(!empty($form['note']))
    <div class="note-box">
        <div class="note-label">Catatan / Temuan</div>
        <div>{{ $form['note'] }}</div>
    </div>
@endif

@if(!empty($approvedDays))
    <div style="margin-top:6px;">
        <strong>Hari yang sudah di-approve:</strong>
        {{ implode(', ', array_map('intval', $approvedDays)) }}
    </div>
@endif
