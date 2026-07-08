<table>
    <tr>
        <td style="width:33%"><strong>Periode:</strong> {{ $form['period'] ?? '-' }}</td>
        <td style="width:33%"><strong>PIC:</strong> {{ $form['pic'] ?? '-' }}</td>
        <td style="width:33%"><strong>Status:</strong>
            @if(!empty($form['approved']))
                Approved
            @else
                Draft
            @endif
        </td>
    </tr>
</table>

@php
    $rows = $form['rows'] ?? [];
    $approvedDays = $form['approved_days'] ?? [];
@endphp

@if(!empty($rows))
    @php
        $days = array_unique(array_map(function($r) { return $r['day'] ?? 0; }, $rows));
        sort($days);
    @endphp
    <table>
        <thead>
            <tr>
                <th style="width:8%">No</th>
                <th style="width:14%">Tanggal</th>
                <th style="width:14%">Pickup Time</th>
                <th style="width:16%">Handover</th>
                <th style="width:14%">Collector</th>
                <th style="width:16%">Foto</th>
                <th style="width:10%">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $idx => $row)
                @php
                    $preview = trim((string) ($row['collector_photo_preview'] ?? ''));
                @endphp
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td>{{ $row['date'] ?? 'Hari ' . ($row['day'] ?? '') }}</td>
                    <td class="text-center">{{ $row['pickup_time'] ?? '-' }}</td>
                    <td>{{ $row['handover_name'] ?? '-' }}</td>
                    <td>{{ $row['collector_name'] ?? '-' }}</td>
                    <td class="text-center">
                        @if($preview !== '')
                            <img src="{{ $row['collector_photo_preview'] }}" alt="Foto" style="width:60px; height:60px; object-fit:cover; border-radius:4px;">
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-center">
                        @if(in_array($row['day'] ?? 0, $approvedDays))
                            <span class="check-yes">&#10003;</span>
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
