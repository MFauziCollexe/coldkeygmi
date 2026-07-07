@php
    $sections = array_values(array_filter((array) ($form['sections'] ?? []), 'is_array'));
    $approvedDaysByArea = is_array($form['approved_days_by_area'] ?? null) ? $form['approved_days_by_area'] : [];
    $period = (string) ($form['period'] ?? '');
    $days = [];

    if (preg_match('/^(\d{4})-(\d{2})$/', $period, $matches)) {
        $year = (int) $matches[1];
        $month = (int) $matches[2];
        $lastDay = cal_days_in_month(CAL_GREGORIAN, $month, $year);

        for ($day = 1; $day <= $lastDay; $day++) {
            $timestamp = mktime(0, 0, 0, $month, $day, $year);
            $days[] = [
                'day' => $day,
                'is_sunday' => (int) date('w', $timestamp) === 0,
            ];
        }
    }

    if (empty($days)) {
        $days = array_map(fn ($day) => ['day' => $day, 'is_sunday' => false], range(1, 31));
    }

    $totalPages = max(count($sections), 1);
@endphp

@forelse($sections as $pageIndex => $section)
    @php
        $areaId = (string) ($section['id'] ?? '');
        $items = array_values(array_filter((array) ($section['items'] ?? []), 'is_array'));
        $approvedDays = array_map('intval', (array) ($approvedDaysByArea[$areaId] ?? []));
    @endphp

    <div class="form-page">
        @include('pdf.checklist-templates.partials.form_header', [
            'form' => $form,
            'title' => "CHECKLIST\nSARANA DAN PRASARANA",
            'pageText' => ($pageIndex + 1) . ' dari ' . $totalPages,
        ])

        <div class="control-row">
            <span class="control-label">Periode:</span>
            <span class="fake-input">{{ $period ?: '-' }}</span>
            <span style="display:inline-block; width:18px;"></span>
            <span class="control-label">Area:</span>
            <span class="fake-select">{{ $section['title'] ?? $areaId ?: '-' }}</span>
        </div>

        <table class="form-table">
            <thead>
                <tr>
                    <th style="width:4%;">No</th>
                    <th style="width:25%;">ITEM</th>
                    @foreach($days as $dayInfo)
                        <th style="font-size:6px; padding:2px; {{ $dayInfo['is_sunday'] ? 'background:#dc2626;color:#fff;' : (in_array($dayInfo['day'], $approvedDays, true) ? 'background:#bbf7d0;' : '') }}">
                            {{ $dayInfo['day'] }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                <tr class="section-row">
                    <td colspan="{{ 2 + count($days) }}">{{ $section['title'] ?? '-' }}</td>
                </tr>
                @forelse($items as $idx => $item)
                    <tr>
                        <td class="text-center">{{ $item['no'] ?? ($idx + 1) }}</td>
                        <td>{{ $item['name'] ?? $item['label'] ?? '' }}</td>
                        @foreach($days as $dayInfo)
                            @php
                                $day = $dayInfo['day'];
                                $value = $item['days'][$day] ?? $item['days'][(string) $day] ?? '';
                            @endphp
                            <td class="text-center" style="font-size:8px; padding:2px; {{ $dayInfo['is_sunday'] ? 'background:#dc2626;color:#fff;' : (in_array($day, $approvedDays, true) ? 'background:#dcfce7;' : '') }}">
                                @if(!$dayInfo['is_sunday'])
                                    @if($value === 'yes')
                                        <span class="check-yes">&#10003;</span>
                                    @elseif($value === 'no')
                                        <span class="check-no">&#10005;</span>
                                    @else
                                        &nbsp;
                                    @endif
                                @else
                                    &nbsp;
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ 2 + count($days) }}" class="text-center">Belum ada item untuk area ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@empty
    <div class="form-page">
        @include('pdf.checklist-templates.partials.form_header', [
            'form' => $form,
            'title' => "CHECKLIST\nSARANA DAN PRASARANA",
            'pageText' => '1 dari 1',
        ])
        <div class="note-box">Belum ada data area.</div>
    </div>
@endforelse
