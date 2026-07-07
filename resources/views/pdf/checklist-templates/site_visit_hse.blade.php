@php
    $sections = array_values(array_filter((array) ($form['sections'] ?? []), 'is_array'));
    $areaNotes = is_array($form['area_notes'] ?? null) ? $form['area_notes'] : [];
    $totalPages = max(count($sections), 1);
@endphp

@forelse($sections as $pageIndex => $section)
    @php
        $areaId = (string) ($section['id'] ?? '');
        $items = array_values(array_filter((array) ($section['items'] ?? []), 'is_array'));
        $note = trim((string) ($areaNotes[$areaId] ?? ''));
    @endphp

    <div class="form-page">
        @include('pdf.checklist-templates.partials.form_header', [
            'form' => $form,
            'title' => 'CHECKLIST SITE VISIT HSE',
            'pageText' => ($pageIndex + 1) . ' dari ' . $totalPages,
        ])

        <div class="control-row">
            <span class="control-label">Tanggal:</span>
            <span class="fake-input">{{ $form['date_value'] ?? '-' }}</span>
            <span style="display:inline-block; width:18px;"></span>
            <span class="control-label">Area:</span>
            <span class="fake-select">{{ $section['title'] ?? $areaId ?: '-' }}</span>
        </div>

        <table class="form-table">
            <thead>
                <tr>
                    <th style="width:9%;">No</th>
                    <th>ITEM</th>
                    <th style="width:24%;">Kondisi</th>
                </tr>
            </thead>
            <tbody>
                <tr class="section-row">
                    <td colspan="3">{{ $section['title'] ?? '-' }}</td>
                </tr>
                @forelse($items as $idx => $item)
                    @php $status = $item['status'] ?? ''; @endphp
                    <tr>
                        <td class="text-center">{{ $item['no'] ?? ($idx + 1) }}</td>
                        <td>{{ $item['name'] ?? $item['label'] ?? '' }}</td>
                        <td class="condition-cell">
                            @if($status === 'yes')
                                <span class="check-yes">&#10003;</span>
                            @elseif($status === 'no')
                                <span class="check-no">&#10005;</span>
                            @else
                                &nbsp;
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center">Belum ada item untuk area ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="form-panel">
            <div class="form-panel-title">Keterangan {{ $section['title'] ?? '' }}</div>
            <div class="textarea-box">{{ $note !== '' ? $note : ' ' }}</div>
            <div class="hint-text">Isi catatan ini jika ada item bertanda silang.</div>
        </div>
    </div>
@empty
    <div class="form-page">
        @include('pdf.checklist-templates.partials.form_header', [
            'form' => $form,
            'title' => 'CHECKLIST SITE VISIT HSE',
            'pageText' => '1 dari 1',
        ])
        <div class="note-box">Belum ada data area.</div>
    </div>
@endforelse
