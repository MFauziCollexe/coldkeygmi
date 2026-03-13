<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        @page { margin: 0mm; }
        body { margin: 0; padding: 0; font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        .kop-bg { position: fixed; top: 0; left: 0; width: 210mm; height: 297mm; z-index: -1; }
        .content { padding: 48mm 18mm 70mm 18mm; }
        .title { text-align: center; font-size: 16px; font-weight: 700; margin-bottom: 12px; }
        .meta { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .meta td { padding: 4px 0; vertical-align: top; }
        .meta .label { width: 28%; color: #374151; }
        .meta .value { width: 72%; font-weight: 600; }
        .divider { border-top: 1px solid #9ca3af; margin: 10px 0 12px; }
        .section-title { font-weight: 700; margin: 12px 0 6px; }
        .box { border: 1px solid #d1d5db; padding: 10px; border-radius: 6px; white-space: pre-wrap; }
        .signatures { position: fixed; left: 18mm; right: 18mm; bottom: 40mm; }
        .signatures table { width: 100%; border-collapse: collapse; }
    </style>
</head>
<body>
    @if(!empty($letterheadDataUri))
        <img class="kop-bg" src="{{ $letterheadDataUri }}" alt="Kop Surat">
    @endif

    <div class="content">
        <div class="title">BERITA ACARA</div>

        <table class="meta">
            <tr>
                <td class="label">No. Dokumen</td>
                <td class="value">{{ $item->document_number ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">No BA</td>
                <td class="value">{{ $item->number ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Tanggal dibuat</td>
                <td class="value">{{ optional($item->letter_date)->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <td class="label">Tanggal kejadian</td>
                <td class="value">{{ optional($item->event_date)->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <td class="label">Tempat kejadian</td>
                <td class="value">{{ $item->event_location ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Waktu kejadian</td>
                <td class="value">{{ $item->incident_time ? substr((string) $item->incident_time, 0, 5) : '-' }}</td>
            </tr>
            <tr>
                <td class="label">Customer</td>
                <td class="value">{{ optional($item->customer)->name ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Divisi</td>
                <td class="value">{{ optional($item->department)->name ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">No Mobil</td>
                <td class="value">{{ $item->vehicle_no ?? '-' }}</td>
            </tr>
        </table>

        <div class="divider"></div>

        <div class="section-title">Kronologis Kejadian</div>
        <div class="box">{{ $item->chronology ?? '-' }}</div>

        <div style="margin-top: 18px;">
            Demikian berita acara ini dibuat dengan sebenarnya.<br>
            Atas perhatian dan kerjasamanya kami ucapkan terima kasih.
        </div>
    </div>

    <div class="signatures">
        <table>
            <tr>
                <td style="text-align:center; width:25%; padding-bottom: 38px;">Dibuat oleh,</td>
                <td style="text-align:center; width:25%; padding-bottom: 38px;">Diketahui</td>
                <td style="text-align:center; width:25%; padding-bottom: 38px;">Diketahui</td>
                <td style="text-align:center; width:25%; padding-bottom: 38px;">Checker</td>
            </tr>
            <tr>
                <td style="text-align:center;"><div style="border-top:1px solid #111827; width:80%; margin:0 auto;"></div></td>
                <td style="text-align:center;"><div style="border-top:1px solid #111827; width:80%; margin:0 auto;"></div></td>
                <td style="text-align:center;"><div style="border-top:1px solid #111827; width:80%; margin:0 auto;"></div></td>
                <td style="text-align:center;"><div style="border-top:1px solid #111827; width:80%; margin:0 auto;"></div></td>
            </tr>
        </table>
    </div>
</body>
</html>
