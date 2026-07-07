@php
    $cardType = $form['card_type'] ?? '';
    $cardTitles = [
        'apar' => 'KARTU PEMELIHARAAN APAR',
        'smoke_detector' => 'KARTU PEMELIHARAAN SMOKE DETECTOR',
        'fire_alarm' => 'KARTU PEMELIHARAAN FIRE ALARM',
    ];
    $cardTitle = $cardTitles[$cardType] ?? 'KARTU PEMELIHARAAN';

    $itemsByType = [
        'apar' => [
            ['id' => 'terlihat_jelas', 'name' => 'Terlihat Jelas'],
            ['id' => 'mudah_dijangkau', 'name' => 'Mudah Dijangkau'],
            ['id' => 'tidak_terhalang_barang', 'name' => 'Tidak Terhalang Barang'],
            ['id' => 'pressure_normal', 'name' => 'Tekanan / pressure dalam kondisi normal'],
            ['id' => 'pin_segel_lengkap', 'name' => 'Pin pengaman dan segel lengkap'],
            ['id' => 'tabung_tidak_korosi', 'name' => 'Tabung tidak korosi / kerusakan'],
        ],
        'smoke_detector' => [
            ['id' => 'alarm_menyala_saat_asap', 'name' => 'Alarm menyala ketika ada asap di smoke detector'],
        ],
        'fire_alarm' => [
            ['id' => 'terlihat_jelas', 'name' => 'Terlihat Jelas'],
            ['id' => 'mudah_dijangkau', 'name' => 'Mudah Dijangkau'],
            ['id' => 'tidak_terhalang_barang', 'name' => 'Tidak Terhalang Barang'],
            ['id' => 'label_push_here_terbaca', 'name' => 'Label Push Here Terbaca Jelas'],
            ['id' => 'tombol_dapat_ditekan', 'name' => 'Tombol Dapat Ditekan saat Uji Fungsi'],
            ['id' => 'dapat_direset', 'name' => 'Dapat direset Kembali setelah pengujian'],
            ['id' => 'kondisi_bersih', 'name' => 'Kondisi Bersih dan Tidak Berdebu'],
        ],
    ];
    $items = $itemsByType[$cardType] ?? [];

    $months = [
        ['key' => 'jan', 'label' => 'Jan'],
        ['key' => 'feb', 'label' => 'Feb'],
        ['key' => 'mar', 'label' => 'Mar'],
        ['key' => 'apr', 'label' => 'Apr'],
        ['key' => 'mei', 'label' => 'Mei'],
        ['key' => 'jun', 'label' => 'Jun'],
        ['key' => 'jul', 'label' => 'Jul'],
        ['key' => 'agu', 'label' => 'Agu'],
        ['key' => 'sep', 'label' => 'Sep'],
        ['key' => 'okt', 'label' => 'Okt'],
        ['key' => 'nov', 'label' => 'Nov'],
        ['key' => 'des', 'label' => 'Des'],
    ];
    $checkDates = $form['monthly_check_dates'] ?? [];
@endphp

<table>
    <tr>
        <td colspan="4" class="text-center font-bold" style="font-size:12px;">{{ $cardTitle }}</td>
    </tr>
    <tr>
        <td style="width:25%"><strong>Lokasi:</strong> {{ $form['location'] ?? '-' }}</td>
        <td style="width:25%"><strong>PIC:</strong> {{ $form['pic'] ?? '-' }}</td>
        <td style="width:25%"><strong>No. Doc:</strong> {{ $form['document_no'] ?? '-' }}</td>
        <td style="width:25%"><strong>Tahun:</strong> {{ $form['year'] ?? '-' }}</td>
    </tr>
</table>

<table>
    <thead>
        <tr>
            <th style="width:8%">No</th>
            <th style="width:40%">Item Check</th>
            @foreach($months as $m)
                <th style="width:4.3%; font-size:7px;">{{ $m['label'] }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($items as $idx => $item)
            <tr>
                <td class="text-center">{{ $idx + 1 }}</td>
                <td>{{ $item['name'] }}</td>
                @foreach($months as $m)
                    <td class="text-center">
                        @php
                            $key = $item['id'] . '_' . $m['key'];
                            $val = $form['monthly_answers'][$key] ?? $form[$key] ?? null;
                        @endphp
                        @if($val === 'yes')
                            <span class="check-yes">✓</span>
                        @elseif($val === 'no')
                            <span class="check-no">✕</span>
                        @endif
                    </td>
                @endforeach
            </tr>
        @endforeach
        <tr>
            <td colspan="2" class="text-right font-bold">Tanggal Check</td>
            @foreach($months as $m)
                <td class="text-center" style="font-size:7px;">{{ $checkDates[$m['key']] ?? '' }}</td>
            @endforeach
        </tr>
    </tbody>
</table>

@if(!empty($form['month_note']))
    <div class="note-box">
        <div class="note-label">Catatan</div>
        <div>{{ $form['month_note'] }}</div>
    </div>
@endif
