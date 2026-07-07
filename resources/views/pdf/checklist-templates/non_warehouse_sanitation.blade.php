<table>
    <tr>
        <td style="width:33%"><strong>Periode:</strong> {{ $form['period'] ?? '-' }}</td>
        <td style="width:33%"><strong>PIC:</strong> {{ $form['pic'] ?? '-' }}</td>
        <td style="width:33%"><strong>Status:</strong>
            @if(!empty($form['approved']))
                Approved
            @elseif(!empty($form['approved_days']))
                Partial Approved
            @elseif(!empty($form['submitted_days']))
                Waiting HSE
            @else
                Draft
            @endif
        </td>
    </tr>
</table>

@php
    $areaOptions = [
        ['id' => 'lantai_1', 'name' => 'Lantai 1 Dalam'],
        ['id' => 'lantai_2', 'name' => 'Lantai 2 Office'],
        ['id' => 'lantai_1_depan', 'name' => 'Lantai 1 Depan'],
        ['id' => 'lantai_1_belakang', 'name' => 'Lantai 1 Belakang'],
    ];
    $rowsByArea = $form['rows_by_area'] ?? [];
    $areaScansByDay = $form['area_scans_by_day'] ?? [];
    $days = $form['days'] ?? range(1, 31);
    $submittedDays = $form['submitted_days'] ?? [];
    $approvedDays = $form['approved_days'] ?? [];
@endphp

@foreach($areaOptions as $area)
    @php
        $areaRows = $rowsByArea[$area['id']] ?? [];
    @endphp
    @if(!empty($areaRows))
        <div class="section-title">{{ $area['name'] }}</div>
        <table>
            <thead>
                <tr>
                    <th style="width:8%">No</th>
                    <th style="width:52%">Item</th>
                    @foreach(array_slice($days, 0, 7) as $d)
                        <th style="width:5.7%; font-size:7px;">H{{ $d }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($areaRows as $idx => $row)
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td>{{ $row['name'] ?? $row }}</td>
                        @foreach(array_slice($days, 0, 7) as $d)
                            <td class="text-center">
                                @php $val = $row['days'][$d] ?? null; @endphp
                                @if($val === 'yes') <span class="check-yes">✓</span>
                                @elseif($val === 'no') <span class="check-no">✕</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endforeach

@if(!empty($form['note']))
    <div class="note-box">
        <div class="note-label">Catatan</div>
        <div>{{ $form['note'] }}</div>
    </div>
@endif
