<table>
    <tr>
        <td colspan="2" class="text-center font-bold" style="font-size:12px;">SITE VISIT MAINTENANCE</td>
    </tr>
    <tr>
        <td style="width:50%"><strong>Tipe:</strong> {{ $form['visit_type'] ?? '-' }}</td>
        <td style="width:50%"><strong>Area:</strong> {{ $form['selected_area'] ?? '-' }}</td>
    </tr>
    <tr>
        <td><strong>Tanggal:</strong> {{ $form['date_value'] ?? $form['period_value'] ?? '-' }}</td>
        <td><strong>PIC:</strong> {{ $form['pic'] ?? '-' }}</td>
    </tr>
</table>

@php
    $sections = $form['sections'] ?? [];
    $approvedAreas = $form['approved_areas'] ?? [];
@endphp

@if(!empty($sections))
    @foreach($sections as $section)
        @php
            $title = $section['title'] ?? $section['name'] ?? 'Section';
            $items = $section['items'] ?? [];
        @endphp
        <div class="section-title">{{ $title }}</div>
        <table>
            <thead>
                <tr>
                    <th style="width:8%">No</th>
                    <th style="width:72%">ITEM</th>
                    <th style="width:20%">Kondisi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $idx => $item)
                    @php $status = $item['status'] ?? null; @endphp
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td>{{ $item['name'] ?? $item['label'] ?? '' }}</td>
                        <td class="text-center">
                            @if($status === 'yes')
                                <span class="check-yes">✓</span>
                            @elseif($status === 'no')
                                <span class="check-no">✕</span>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach
@endif

@if(!empty($form['note']))
    <div class="note-box">
        <div class="note-label">Catatan</div>
        <div>{{ $form['note'] }}</div>
    </div>
@endif
