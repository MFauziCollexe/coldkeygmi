@php
    $headerTitle = $title ?? 'CHECKLIST';
    $logoPath = public_path('image/logo-gmi-clean.png');
    $logoSrc = null;

    if (is_file($logoPath)) {
        $mime = mime_content_type($logoPath) ?: 'image/png';
        $logoSrc = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
    }
@endphp

<table class="form-header">
    <colgroup>
        <col style="width:18%;">
        <col style="width:42%;">
        <col style="width:20%;">
        <col style="width:20%;">
    </colgroup>
    <tbody>
        <tr>
            <td rowspan="5" class="form-logo">
                @if($logoSrc)
                    <img src="{{ $logoSrc }}" alt="PT Golden Multi Indotama">
                @endif
            </td>
            <td colspan="3" class="company-name">PT GOLDEN MULTI INDOTAMA</td>
        </tr>
        <tr>
            <td rowspan="4" class="document-title">{!! nl2br(e($headerTitle)) !!}</td>
            <td class="document-meta-label">Doc. No.</td>
            <td class="document-meta-value">{{ $form['document_no'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="document-meta-label">Rev.</td>
            <td class="document-meta-value">{{ $form['rev'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="document-meta-label">Tanggal Efektif</td>
            <td class="document-meta-value">{{ $form['effective_date'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="document-meta-label">Halaman</td>
            <td class="document-meta-value">{{ $pageText ?? ($form['page'] ?? '-') }}</td>
        </tr>
    </tbody>
</table>
