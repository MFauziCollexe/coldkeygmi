<div class="form-page">
    @include('pdf.checklist-templates.partials.form_header', [
        'form' => $form,
        'title' => 'PEMANASAN (RUNNING) GENSET',
        'pageText' => '1 dari 1',
    ])

    <div class="control-row">
        <span class="control-label">Periode:</span>
        <span class="fake-input">{{ $form['period'] ?? '-' }}</span>
        <span style="display:inline-block; width:18px;"></span>
        <span class="control-label">PIC:</span>
        <span class="fake-input">{{ $form['pic'] ?? '-' }}</span>
    </div>

@php
    $rows = $form['rows'] ?? [];
@endphp

@if(!empty($rows))
    @php
        $allKeys = [];
        foreach($rows as $row) {
            foreach($row as $k => $v) {
                if(!in_array($k, ['day', 'date', 'id', 'no'])) $allKeys[$k] = $k;
            }
        }
        $columns = array_keys($allKeys);
    @endphp
    <table>
        <thead>
            <tr>
                <th style="width:6%">No</th>
                <th style="width:14%">Tanggal</th>
                @foreach($columns as $col)
                    <th style="font-size:8px;">{{ strtoupper(str_replace('_', ' ', $col)) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $idx => $row)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td>{{ $row['date'] ?? '-' }}</td>
                    @foreach($columns as $col)
                        <td class="text-center">
                            @php $val = $row[$col] ?? null; @endphp
                            @if($val === 'yes') <span class="check-yes">✓</span>
                            @elseif($val === 'no') <span class="check-no">✕</span>
                            @else {{ $val }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

@if(!empty($form['note']))
    <div class="note-box">
        <div class="note-label">Catatan</div>
        <div>{{ $form['note'] }}</div>
    </div>
@endif

</div>
