@php
    $rows = is_array($form['rows'] ?? null) ? $form['rows'] : [];
    $photos = [];
    $photoPaths = $form['area_photo_paths']['inspeksi_loker'] ?? [];
    $photoUrls = $form['area_photo_urls']['inspeksi_loker'] ?? [];
    $photoNames = $form['area_photo_names']['inspeksi_loker'] ?? [];
    $normalizeBucket = fn ($value) => is_array($value) ? array_values($value) : (trim((string) $value) !== '' ? [trim((string) $value)] : []);
    $photoPaths = $normalizeBucket($photoPaths);
    $photoUrls = $normalizeBucket($photoUrls);
    $photoNames = $normalizeBucket($photoNames);
    $photoSrc = function ($path, $url) {
        $candidate = trim((string) ($path ?: $url));
        if ($candidate === '') {
            return null;
        }

        $candidate = preg_replace('/\\?.*$/', '', $candidate);
        $candidate = preg_replace('#^https?://[^/]+/#', '/', $candidate);
        $candidate = preg_replace('#^/?storage/#', '', $candidate);
        $candidate = ltrim($candidate, '/');
        foreach ([public_path($candidate), public_path('storage/'.$candidate), storage_path('app/public/'.$candidate)] as $filePath) {
            if (is_file($filePath)) {
                $mime = mime_content_type($filePath) ?: 'image/jpeg';
                return 'data:'.$mime.';base64,'.base64_encode(file_get_contents($filePath));
            }
        }

        return null;
    };

    for ($index = 0, $count = max(count($photoPaths), count($photoUrls), count($photoNames)); $index < $count; $index++) {
        $src = $photoSrc($photoPaths[$index] ?? '', $photoUrls[$index] ?? '');
        if ($src !== null) {
            $photos[] = ['src' => $src, 'name' => $photoNames[$index] ?? ('Foto '.($index + 1))];
        }
    }
@endphp

<div class="form-page">
    @include('pdf.checklist-templates.partials.form_header', [
        'form' => $form,
        'entry' => $entry,
        'title' => 'CHECKLIST INSPEKSI LOKER',
        'pageText' => $form['page'] ?? 'Page 1 dari 1',
    ])

    <table class="locker-meta">
        <tr>
            <td><strong>Bulan:</strong> {{ $form['date_value'] ?? '-' }}</td>
            <td><strong>PIC:</strong> {{ $form['pic'] ?? '-' }}</td>
        </tr>
    </table>

    <table class="locker-matrix">
        <thead>
            <tr>
                <th class="locker-number">No.</th>
                <th class="locker-label">Parameter</th>
                @for($locker = 1; $locker <= 32; $locker++)
                    <th class="locker-cell">{{ $locker }}</th>
                @endfor
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $index => $row)
                <tr>
                    <td class="text-center">{{ $row['no'] ?? ($index + 1) }}</td>
                    <td>{{ $row['label'] ?? '' }}</td>
                    @for($locker = 1; $locker <= 32; $locker++)
                        @php $status = $row['lockers'][(string) $locker] ?? ''; @endphp
                        <td class="text-center locker-status">
                            @if($status === 'yes') <span class="check-yes">&#10003;</span>
                            @elseif($status === 'no') <span class="check-no">&#10005;</span>
                            @endif
                        </td>
                    @endfor
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="form-panel">
        <div class="form-panel-title">Keterangan</div>
        <div class="textarea-box">{{ trim((string) ($form['note'] ?? '')) !== '' ? $form['note'] : ' ' }}</div>
    </div>

    <div class="section-title">Foto Inspeksi Loker ({{ count($photos) }} foto)</div>
    @if($photos)
        <table class="photo-grid">
            @foreach(array_chunk($photos, 2) as $photoRow)
                <tr>
                    @foreach($photoRow as $photo)
                        <td class="photo-card">
                            <img src="{{ $photo['src'] }}" alt="{{ $photo['name'] }}">
                            <div class="photo-name">{{ $photo['name'] }}</div>
                        </td>
                    @endforeach
                    @if(count($photoRow) === 1)<td class="photo-card"></td>@endif
                </tr>
            @endforeach
        </table>
    @else
        <div class="textarea-box">Tidak ada foto.</div>
    @endif
</div>

<style>
    @page { size: A4 landscape; }
    .locker-meta td { width: 50%; border: 1px solid #000; padding: 6px 8px; font-size: 9px; }
    .locker-matrix { table-layout: fixed; margin-top: 8px; }
    .locker-matrix th, .locker-matrix td { padding: 3px 2px; font-size: 6px; }
    .locker-matrix .locker-number { width: 4%; }
    .locker-matrix .locker-label { width: 23%; }
    .locker-matrix .locker-cell { width: 2.28%; }
    .locker-matrix .locker-status { height: 17px; }
</style>
