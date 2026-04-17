<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Attendance Security PDF</title>
    <style>
        @page {
            margin: 10px 14px 10px 14px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #111;
            margin: 0;
        }

        .page {
            page-break-after: always;
        }

        .page:last-child {
            page-break-after: auto;
        }

        .top {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }

        .top td {
            vertical-align: top;
        }

        .spacer {
            width: 43%;
        }

        .logo-wrap {
            width: 30%;
            text-align: center;
            padding-top: 4px;
        }

        .logo-wrap img {
            width: 128px;
            height: auto;
        }

        .logo-fallback {
            font-size: 28px;
            font-weight: 700;
            line-height: 1.1;
            padding-top: 30px;
        }

        .company {
            width: 27%;
            padding-left: 4px;
        }

        .company-title {
            font-size: 12px;
            font-weight: 700;
            margin: 0 0 2px 0;
            line-height: 1.05;
        }

        .company-line {
            font-size: 9px;
            line-height: 1.05;
            margin: 0 0 1px 0;
        }

        .title {
            text-align: center;
            font-size: 14px;
            font-weight: 700;
            margin: 6px 0 5px 0;
        }

        .meta {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }

        .meta td {
            padding: 0 2px;
            font-size: 10px;
            vertical-align: top;
            line-height: 1.1;
        }

        .meta .label {
            width: 78px;
        }

        .meta .colon {
            width: 8px;
        }

        .meta .value {
            width: 280px;
        }

        .meta .month-label {
            width: 60px;
        }

        .attendance {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .attendance th,
        .attendance td {
            border: 1px solid #111;
            padding: 3px 4px;
            font-size: 9.5px;
            line-height: 1.12;
        }

        .attendance th {
            text-align: center;
            font-weight: 700;
        }

        .attendance td {
            text-align: center;
        }

        .attendance td.keterangan {
            text-align: left;
        }

        .sign-label {
            width: 100%;
            text-align: right;
            margin-top: 6px;
            margin-bottom: 0;
            font-size: 10px;
        }

        .sign-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-top: 16px;
        }

        .sign-table .heading td {
            height: 16px;
            padding: 0 6px;
            font-size: 10px;
            vertical-align: middle;
            text-align: center;
        }

        .sign-table td {
            border: 1px solid #111;
            height: 62px;
            vertical-align: bottom;
            text-align: center;
            padding: 4px 4px;
            font-size: 9px;
        }

        .sign-table .box td {
            vertical-align: bottom;
            padding-bottom: 8px;
        }

        .sign-table .role-row td {
            height: 16px;
            padding: 0 4px;
            font-size: 9px;
            vertical-align: middle;
        }

        .sign-name {
            display: block;
            margin-bottom: 2px;
        }

        .sign-role {
            display: block;
            font-size: 8px;
        }
    </style>
</head>
<body>
@foreach ($groups as $group)
    <div class="page">
        <table class="top">
            <tr>
                <td class="spacer"></td>
                <td class="logo-wrap">
                    @if ($logoDataUri)
                        <img src="{{ $logoDataUri }}" alt="T2P Logo">
                    @else
                        <div class="logo-fallback">T2P</div>
                    @endif
                </td>
                <td class="company">
                    @foreach ($companyLines as $index => $line)
                        @if ($index === 0)
                            <div class="company-title">{{ $line }}</div>
                        @else
                            <div class="company-line">{{ $line }}</div>
                        @endif
                    @endforeach
                </td>
            </tr>
        </table>

        <div class="title">DAFTAR HADIR DAN LEMBUR PERORANGAN</div>

        <table class="meta">
            <tr>
                <td class="label">Nama</td>
                <td class="colon">:</td>
                <td class="value">{{ $group['name'] }}</td>
                <td class="month-label">Bulan</td>
                <td class="colon">:</td>
                <td>{{ $monthLabel }}</td>
            </tr>
            <tr>
                <td class="label">NIK</td>
                <td class="colon">:</td>
                <td class="value">{{ $group['pin'] }}</td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td class="label">Lokasi Kerja</td>
                <td class="colon">:</td>
                <td class="value">PT. Golden Multi Indotama</td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        </table>

        <table class="attendance">
            <thead>
                <tr>
                    <th style="width: 7%;">Tgl</th>
                    <th style="width: 13%;">Hari</th>
                    <th style="width: 10%;">Masuk</th>
                    <th style="width: 10%;">Pulang</th>
                    <th style="width: 20%;">Status</th>
                    <th style="width: 40%;">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($dateColumns as $logDate)
                    @php
                        $row = $group['rows']->get($logDate);
                        $day = \Illuminate\Support\Carbon::parse($logDate)->locale('id')->translatedFormat('l');
                        $firstScan = !is_array($row) || empty($row['first_scan']) ? '' : \Illuminate\Support\Carbon::parse($row['first_scan'])->format('H:i');
                        $lastScan = !is_array($row) || empty($row['last_scan']) ? '' : \Illuminate\Support\Carbon::parse($row['last_scan'])->format('H:i');
                        $status = is_array($row) ? trim((string) ($row['expected'] ?? '')) : '';
                    @endphp
                    <tr>
                        <td>{{ \Illuminate\Support\Carbon::parse($logDate)->format('j') }}</td>
                        <td>{{ ucfirst($day) }}</td>
                        <td>{{ $firstScan }}</td>
                        <td>{{ $lastScan }}</td>
                        <td>{{ $status }}</td>
                        <td class="keterangan"></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="sign-table">
            <tr class="heading">
                <td></td>
                <td>Mengetahui</td>
            </tr>
            <tr class="box">
                <td>
                    <span class="sign-name">{{ $group['name'] }}</span>
                </td>
                <td>
                    <span class="sign-name">&nbsp;</span>
                </td>
            </tr>
            <tr class="role-row">
                <td>
                    <span class="sign-role">ANGGOTA SATPAM</span>
                </td>
                <td>
                    <span class="sign-role">KOMANDAN REGU</span>
                </td>
            </tr>
        </table>
    </div>
@endforeach
</body>
</html>
