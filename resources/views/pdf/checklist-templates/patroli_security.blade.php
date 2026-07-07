@php
    $sections = array_values(array_filter((array) ($form['sections'] ?? []), 'is_array'));
    $areaNotes = is_array($form['area_notes'] ?? null) ? $form['area_notes'] : [];
    $photoPaths = is_array($form['area_photo_paths'] ?? null) ? $form['area_photo_paths'] : [];
    $photoUrls = is_array($form['area_photo_urls'] ?? null) ? $form['area_photo_urls'] : [];
    $photoNames = is_array($form['area_photo_names'] ?? null) ? $form['area_photo_names'] : [];
    $totalPages = max(count($sections), 1);

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
@endphp

@forelse($sections as $pageIndex => $section)
    @php
        $areaId = (string) ($section['id'] ?? '');
        $items = array_values(array_filter((array) ($section['items'] ?? []), 'is_array'));
        $note = trim((string) ($areaNotes[$areaId] ?? ''));
        $paths = $bucket($photoPaths[$areaId] ?? []);
        $urls = $bucket($photoUrls[$areaId] ?? []);
        $names = $bucket($photoNames[$areaId] ?? []);
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
            'title' => 'CHECKLIST PATROLI SECURITY',
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

        <div class="form-panel">
            <div class="form-panel-title">Foto Area <span style="float:right; font-weight:400;">{{ count($photos) }} foto</span></div>
            @if(count($photos))
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
            @else
                <div class="hint-text">Belum ada foto area.</div>
            @endif
        </div>
    </div>
@empty
    <div class="form-page">
        @include('pdf.checklist-templates.partials.form_header', [
            'form' => $form,
            'title' => 'CHECKLIST PATROLI SECURITY',
            'pageText' => '1 dari 1',
        ])
        <div class="note-box">Belum ada data area.</div>
    </div>
@endforelse
