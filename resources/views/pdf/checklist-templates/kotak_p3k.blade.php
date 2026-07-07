<table class="info-table">
    <tr>
        <td class="label">Lokasi</td>
        <td class="value">{{ $form['location'] ?? '-' }}</td>
        <td class="label text-center">Approved</td>
        <td class="value text-center">{{ !empty($form['approved']) ? '✓' : '-' }}</td>
    </tr>
    <tr>
        <td class="label">No. / Tipe Kotak</td>
        <td class="value">{{ $form['box_type'] ?? '-' }}</td>
        <td class="label text-center">Prepared</td>
        <td class="value text-center">{{ $form['pic'] ?? '-' }}</td>
    </tr>
    <tr>
        <td class="label">PIC</td>
        <td class="value">{{ $form['pic'] ?? '-' }}</td>
        <td class="label">No. Doc</td>
        <td class="value">{{ $form['document_no'] ?? '-' }}</td>
    </tr>
    <tr>
        <td class="label">Tahun</td>
        <td class="value">{{ $form['year'] ?? '-' }}</td>
        <td class="label">Date</td>
        <td class="value">{{ $form['date'] ?? '-' }}</td>
    </tr>
</table>

@php
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
    $items = $form['items'] ?? [];
    $checkDates = $form['monthly_check_dates'] ?? [];
@endphp

<table>
    <thead>
        <tr>
            <th style="width:8%">No</th>
            <th style="width:28%">Item Check</th>
            <th style="width:8%">Jumlah</th>
            @foreach($months as $m)
                <th style="width:4.7%; font-size:7px;">{{ $m['label'] }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($items as $idx => $item)
            <tr>
                <td class="text-center">{{ $idx + 1 }}</td>
                <td>{{ $item['name'] ?? '' }}</td>
                <td class="text-center">{{ $item['quantity'] ?? '' }}</td>
                @foreach($months as $m)
                    <td class="text-center">
                        @php $val = $item['months'][$m['key']] ?? null; @endphp
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
            <td colspan="3" class="text-right font-bold">Tanggal Check</td>
            @foreach($months as $m)
                <td class="text-center" style="font-size:7px;">{{ $checkDates[$m['key']] ?? '' }}</td>
            @endforeach
        </tr>
    </tbody>
</table>

@php
    $activeMonth = $form['active_month'] ?? 'jan';
    $monthNames = ['jan'=>'Januari','feb'=>'Februari','mar'=>'Maret','apr'=>'April','mei'=>'Mei','jun'=>'Juni','jul'=>'Juli','agu'=>'Agustus','sep'=>'September','okt'=>'Oktober','nov'=>'November','des'=>'Desember'];
    $monthNoteKey = 'month_note_' . $activeMonth;
@endphp

@if(!empty($form[$monthNoteKey]) || !empty($form['month_note']))
    <div class="note-box">
        <div class="note-label">Keterangan Bulan {{ $monthNames[$activeMonth] ?? $activeMonth }}</div>
        <div>{{ $form[$monthNoteKey] ?? $form['month_note'] ?? '-' }}</div>
    </div>
@endif
