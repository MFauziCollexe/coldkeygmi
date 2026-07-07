<table>
    <tr>
        <td colspan="3" class="text-center font-bold" style="font-size:12px;">CHECKLIST KEBERSIHAN DAN SANITASI (WAREHOUSE AREA)</td>
    </tr>
    <tr>
        <td style="width:33%"><strong>Tanggal:</strong> {{ $form['date'] ?? $form['period'] ?? '-' }}</td>
        <td style="width:33%"><strong>Petugas:</strong> {{ $form['petugas'] ?? '-' }}</td>
        <td style="width:33%"><strong>HSE:</strong> {{ $form['hse'] ?? '-' }}</td>
    </tr>
    <tr>
        <td><strong>Frekuensi:</strong> {{ $form['frequency'] ?? '-' }}</td>
        <td><strong>Room Temp:</strong> {{ $form['room_temperature'] ?? '-' }}</td>
        <td><strong>Area:</strong> {{ implode(', ', $form['selected_areas'] ?? []) }}</td>
    </tr>
</table>

@php
    $areaRows = $form['area_rows'] ?? [];
    $iceRows = $form['ice_control_rows'] ?? [];
    $materialRows = $form['cleaning_material_rows'] ?? [];
@endphp

@if(!empty($areaRows))
    <div class="section-title">Area Check</div>
    <table>
        <thead>
            <tr>
                <th>Area</th>
                <th>Clean Condition</th>
                <th>No Ice Pooling</th>
                <th>No Odor</th>
                <th>Note</th>
            </tr>
        </thead>
        <tbody>
            @foreach($areaRows as $row)
                <tr>
                    <td>{{ $row['area'] ?? '-' }}</td>
                    <td class="text-center">{{ $row['clean_condition'] ? '✓' : '-' }}</td>
                    <td class="text-center">{{ $row['no_ice_pooling'] ? '✓' : '-' }}</td>
                    <td class="text-center">{{ $row['no_odor'] ? '✓' : '-' }}</td>
                    <td>{{ $row['note'] ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

@if(!empty($iceRows))
    <div class="section-title">Ice Control</div>
    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>Status</th>
                <th>Note</th>
            </tr>
        </thead>
        <tbody>
            @foreach($iceRows as $row)
                <tr>
                    <td>{{ $row['name'] ?? '-' }}</td>
                    <td class="text-center">{{ $row['status'] ? '✓' : '-' }}</td>
                    <td>{{ $row['note'] ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

@if(!empty($materialRows))
    <div class="section-title">Cleaning Material</div>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Material Name</th>
                <th>Halal</th>
                <th>Dosage</th>
            </tr>
        </thead>
        <tbody>
            @foreach($materialRows as $row)
                <tr>
                    <td class="text-center">{{ $row['no'] ?? $loop->iteration }}</td>
                    <td>{{ $row['material_name'] ?? '-' }}</td>
                    <td class="text-center">{{ $row['halal'] ? '✓' : '-' }}</td>
                    <td>{{ $row['dosage'] ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

@php $verification = $form['verification'] ?? []; @endphp
@if(!empty($verification))
    <div class="section-title">Verification</div>
    <table>
        <tr>
            <td><strong>Prepared:</strong> {{ $verification['prepared_name'] ?? '-' }} ({{ $verification['prepared_date'] ?? '-' }})</td>
            <td><strong>Verified:</strong> {{ $verification['verified_name'] ?? '-' }} ({{ $verification['verified_date'] ?? '-' }})</td>
        </tr>
    </table>
@endif
