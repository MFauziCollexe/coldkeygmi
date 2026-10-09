@php
    $months = [
        ['key' => 'jan', 'label' => 'Jan'], ['key' => 'feb', 'label' => 'Feb'], ['key' => 'mar', 'label' => 'Mar'],
        ['key' => 'apr', 'label' => 'Apr'], ['key' => 'may', 'label' => 'Mei'], ['key' => 'jun', 'label' => 'Jun'],
        ['key' => 'jul', 'label' => 'Jul'], ['key' => 'aug', 'label' => 'Agu'], ['key' => 'sep', 'label' => 'Sep'],
        ['key' => 'oct', 'label' => 'Okt'], ['key' => 'nov', 'label' => 'Nov'], ['key' => 'dec', 'label' => 'Des'],
    ];
    $locationLabels = [
        'ruang_admin' => 'Lantai 1 Depan (R.Admin)',
        'ruang_kontrol' => 'Lantai 1 Belakang (R.Kontrol)',
        'pos_security' => 'Lantai 1 Depan Luar (Pos Security)',
    ];
    $activeMonth = $form['active_month'] ?? 'jan';
    $locationStates = is_array($form['location_entries'] ?? null) ? $form['location_entries'] : [];
    if (!$locationStates) {
        $locationStates[trim((string) ($form['location'] ?? ''))] = $form;
    }
    $monthNames = ['jan'=>'Januari','feb'=>'Februari','mar'=>'Maret','apr'=>'April','may'=>'Mei','jun'=>'Juni','jul'=>'Juli','aug'=>'Agustus','sep'=>'September','oct'=>'Oktober','nov'=>'November','dec'=>'Desember'];
@endphp

@foreach($locationStates as $locationId => $locationState)
@php
    $locationId = trim((string) $locationId);
    $items = is_array($locationState['items'] ?? null) ? $locationState['items'] : [];
    $checkDates = is_array($locationState['monthly_check_dates'] ?? null) ? $locationState['monthly_check_dates'] : [];
    $monthNotes = is_array($locationState['monthly_notes'] ?? null) ? $locationState['monthly_notes'] : [];
    $approvedMonths = is_array($locationState['approved_months'] ?? null) ? $locationState['approved_months'] : [];
    $submittedMonths = is_array($locationState['submitted_months'] ?? null) ? $locationState['submitted_months'] : [];
    $activeStatus = in_array($activeMonth, $approvedMonths, true) ? 'Approved' : (in_array($activeMonth, $submittedMonths, true) ? 'Approval HSE' : 'Pending');
@endphp
<div class="form-page">
    @include('pdf.checklist-templates.partials.hse_brand_header', ['entry' => $entry, 'subtitle' => 'Check sheet kotak P3K'])
    <div class="kotak-title"><span>Check Sheet</span><strong>Kotak P3K</strong></div>
    <div class="kotak-active">Bulan Aktif: <strong>{{ $monthNames[$activeMonth] ?? $activeMonth }}</strong> <span>Status: {{ $activeStatus }}</span></div>

    <table class="kotak-info">
        <tr>
            <td>Lokasi</td><td class="value">{{ $locationLabels[$locationId] ?? $locationId ?: '-' }}</td>
            <td class="center">Approved</td><td class="center">Prepared</td>
        </tr>
        <tr>
            <td>No. / Tipe Kotak</td><td class="value">{{ $form['box_type'] ?? '-' }}</td>
            <td class="center">{{ $activeStatus }}</td><td class="center">{{ $form['pic'] ?? '-' }}</td>
        </tr>
        <tr>
            <td>PIC</td><td class="value">{{ $form['pic'] ?? '-' }}</td>
            <td>No. Doc: {{ $form['document_no'] ?? '-' }}</td><td>Rev: {{ $form['rev'] ?? '-' }}</td>
        </tr>
        <tr>
            <td>Tahun</td><td class="value">{{ $form['year'] ?? '-' }}</td>
            <td>Date: {{ $form['date'] ?? '-' }}</td><td>Page: {{ $form['page'] ?? '-' }}</td>
        </tr>
    </table>

    <table class="kotak-matrix">
        <thead>
            <tr><th>No</th><th>Item Check</th><th>Jumlah</th>@foreach($months as $month)<th>{{ $month['label'] }}</th>@endforeach</tr>
        </thead>
        <tbody>
            @foreach($items as $index => $item)
                <tr>
                    <td class="center">{{ $index + 1 }}</td><td>{{ $item['name'] ?? '' }}</td><td class="center">{{ $item['quantity'] ?? '' }}</td>
                    @foreach($months as $month)
                        @php $status = $item['months'][$month['key']] ?? ''; @endphp
                        <td class="center {{ $month['key'] === $activeMonth ? 'active-month' : '' }}">
                            @if($status === 'yes') <span class="check-yes">&#10003;</span>
                            @elseif($status === 'no') <span class="check-no">&#10005;</span>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
            <tr><td colspan="3" class="right"><strong>Tanggal Check</strong></td>@foreach($months as $month)<td class="center {{ $month['key'] === $activeMonth ? 'active-month' : '' }}">{{ $checkDates[$month['key']] ?? '' }}</td>@endforeach</tr>
        </tbody>
    </table>

    <div class="form-panel">
        <div class="form-panel-title">Keterangan Bulan {{ $monthNames[$activeMonth] ?? $activeMonth }}</div>
        <div class="textarea-box">{{ trim((string) ($monthNotes[$activeMonth] ?? '')) !== '' ? $monthNotes[$activeMonth] : ' ' }}</div>
    </div>
</div>
@endforeach

<style>
    @page { size: A4 landscape; }
    .kotak-title { width: 58%; margin: 0 auto 8px; border: 2px solid #000; padding: 4px 10px; text-align: center; }
    .kotak-title span, .kotak-title strong { display: block; }
    .kotak-title span { font-size: 12px; }
    .kotak-title strong { font-size: 22px; }
    .kotak-active { margin-bottom: 7px; font-size: 8px; }
    .kotak-active span { margin-left: 16px; }
    .kotak-info { table-layout: fixed; margin-bottom: 8px; }
    .kotak-info td { width: 25%; padding: 4px 6px; }
    .kotak-info .value { font-weight: 700; }
    .kotak-info .center, .kotak-matrix td.center { text-align: center; }
    .kotak-matrix { table-layout: fixed; }
    .kotak-matrix th, .kotak-matrix td { padding: 3px 3px; font-size: 7px; }
    .kotak-matrix th:nth-child(1) { width: 4%; }
    .kotak-matrix th:nth-child(2) { width: 28%; text-align: left; }
    .kotak-matrix th:nth-child(3) { width: 7%; }
    .kotak-matrix td.active-month { background: #fffbeb; }
    .kotak-matrix th { background: #e5e7eb; }
    .kotak-matrix .right { text-align: right; }
</style>
