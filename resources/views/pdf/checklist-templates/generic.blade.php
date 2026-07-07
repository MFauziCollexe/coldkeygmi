<table class="info-table">
    @foreach($form as $key => $value)
        @if(is_scalar($value) && !empty($value) && !in_array($key, ['id', 'template_id', 'name', 'created_at', 'approved_at']))
            <tr>
                <td class="label" style="width:30%">{{ strtoupper(str_replace('_', ' ', $key)) }}</td>
                <td class="value" style="width:70%">{{ $value }}</td>
            </tr>
        @endif
    @endforeach
</table>

@php
    $rows = $form['rows'] ?? [];
@endphp

@if(!empty($rows) && is_array($rows))
    @php
        $columns = [];
        foreach($rows as $row) {
            if(is_array($row)) {
                foreach($row as $k => $v) {
                    if(!in_array($k, ['id'])) $columns[$k] = $k;
                }
            }
        }
    @endphp
    @if(!empty($columns))
        <div class="section-title">Detail</div>
        <table>
            <thead>
                <tr>
                    <th style="width:6%">#</th>
                    @foreach($columns as $col)
                        <th style="font-size:8px;">{{ strtoupper(str_replace('_', ' ', $col)) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $idx => $row)
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        @foreach($columns as $col)
                            <td class="text-center">
                                @php $val = $row[$col] ?? ''; @endphp
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
@endif

@if(!empty($form['note']))
    <div class="note-box">
        <div class="note-label">Catatan</div>
        <div>{{ $form['note'] }}</div>
    </div>
@endif
