@php
    $brandLogoPath = public_path('image/logo-gmi-clean.png');
    $brandLogoSrc = null;
    if (is_file($brandLogoPath)) {
        $brandLogoMime = mime_content_type($brandLogoPath) ?: 'image/png';
        $brandLogoSrc = 'data:'.$brandLogoMime.';base64,'.base64_encode(file_get_contents($brandLogoPath));
    }
@endphp
<table class="hse-brand-header">
    <tr>
        <td class="hse-brand-logo">
            @if($brandLogoSrc)<img src="{{ $brandLogoSrc }}" alt="PT. Golden Multi Indotama">@endif
        </td>
        <td>
            <div class="hse-brand-name">PT. GOLDEN MULTI INDOTAMA</div>
            <div class="hse-brand-subtitle">{{ $subtitle }}</div>
        </td>
    </tr>
</table>
<div class="hse-created-date">Tanggal Dibuat: {{ $entry['created_date'] ?? '-' }}</div>
<style>
    .hse-brand-header { margin-bottom: 3px; border: 0; }
    .hse-brand-header td { border: 0; padding: 2px 4px; vertical-align: middle; }
    .hse-brand-logo { width: 72px; text-align: center; }
    .hse-brand-logo img { width: 52px; height: 52px; object-fit: contain; }
    .hse-brand-name { font-size: 15px; font-weight: 700; }
    .hse-brand-subtitle { margin-top: 3px; font-size: 9px; color: #475569; }
    .hse-created-date { margin: -2px 0 8px 80px; font-size: 8px; color: #64748b; }
</style>
