@php
    $photoPaths = is_array($form['area_photo_paths'] ?? null) ? $form['area_photo_paths'] : [];
    $photoUrls = is_array($form['area_photo_urls'] ?? null) ? $form['area_photo_urls'] : [];
    $photoNames = is_array($form['area_photo_names'] ?? null) ? $form['area_photo_names'] : [];
    $activeAreaKey = ($form['visit_type'] ?? '') === 'maintenance_mingguan' ? 'lantai_1_area_belakang' : ($form['selected_area'] ?? '');

    $bucket = function ($value) {
        if (is_array($value)) {
            return array_values(array_filter($value, fn ($item) => trim((string) $item) !== ''));
        }
        $single = trim((string) $value);
        return $single !== '' ? [$single] : [];
    };

    $photoSrc = function ($path, $url) {
        $candidate = trim((string) ($path ?: $url));
        if ($candidate === '') {
            return null;
        }
        $candidate = preg_replace('/\?.*$/', '', $candidate);
        $candidate = preg_replace('#^https?://[^/]+/#', '/', $candidate);
        $candidate = ltrim($candidate, '/');
        $candidate = preg_replace('#^storage/#', '', $candidate);
        $possiblePaths = [
            public_path($candidate),
            public_path('storage/' . $candidate),
            storage_path('app/public/' . $candidate),
        ];
        foreach ($possiblePaths as $filePath) {
            if (is_file($filePath)) {
                $mime = mime_content_type($filePath) ?: 'image/jpeg';
                return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($filePath));
            }
        }
        return null;
    };

    $paths = $bucket($photoPaths[$activeAreaKey] ?? []);
    $urls = $bucket($photoUrls[$activeAreaKey] ?? []);
    $names = $bucket($photoNames[$activeAreaKey] ?? []);
    $photoCount = max(count($paths), count($urls), count($names));
    $photos = [];
    for ($i = 0; $i < $photoCount; $i++) {
        $src = $photoSrc($paths[$i] ?? '', $urls[$i] ?? '');
        if ($src) {
            $photos[] = [
                'src' => $src,
                'name' => $names[$i] ?? ('Foto ' . ($i + 1)),
            ];
        }
    }
@endphp

<div class="form-page">
    @include('pdf.checklist-templates.partials.form_header', [
        'form' => $form,
        'title' => 'SITE VISIT MAINTENANCE',
        'pageText' => '1 dari 1',
    ])

    <div class="control-row">
        <span class="control-label">Tipe:</span>
        <span class="fake-input">{{ $form['visit_type'] ?? '-' }}</span>
        <span style="display:inline-block; width:18px;"></span>
        <span class="control-label">Area:</span>
        <span class="fake-select">{{ $form['selected_area'] ?? '-' }}</span>
        <span style="display:inline-block; width:18px;"></span>
        <span class="control-label">PIC:</span>
        <span class="fake-input">{{ $form['pic'] ?? '-' }}</span>
    </div>

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
                                <span class="check-yes">&#10003;</span>
                            @elseif($status === 'no')
                                <span class="check-no">&#10005;</span>
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

@php
    $note = trim((string) ($form['area_notes'][$activeAreaKey] ?? $form['note'] ?? ''));
@endphp
@if($note !== '')
    <div class="note-box">
        <div class="note-label">Catatan</div>
        <div>{{ $note }}</div>
    </div>
@endif

@if(count($photos))
    <div class="form-panel">
        <div class="form-panel-title">Foto Area <span style="float:right; font-weight:400;">{{ count($photos) }} foto</span></div>
        <table class="photo-grid">
            <tbody>
                @foreach(array_chunk($photos, 2) as $row)
                    <tr>
                        @foreach($row as $photo)
                            <td class="photo-card">
                                <img src="{{ $photo['src'] }}" alt="{{ $photo['name'] }}">
                                <div class="photo-name">{{ $photo['name'] }}</div>
                            </td>
                        @endforeach
                        @if(count($row) === 1)
                            <td class="photo-card">&nbsp;</td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

</div>
