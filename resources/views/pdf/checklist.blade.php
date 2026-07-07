<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Checklist - {{ $entry['name'] ?? '' }}</title>
    <style>
        @page { margin: 9mm; }
        body { margin: 0; padding: 0; font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111827; }
        .header { text-align: center; margin-bottom: 16px; }
        .header h1 { font-size: 18px; margin: 0 0 4px; }
        .header h2 { font-size: 14px; margin: 0 0 4px; }
        .header .sub { font-size: 10px; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th, td { border: 1px solid #000; padding: 4px 6px; text-align: left; font-size: 9px; }
        th { background-color: #e5e7eb; font-weight: 700; text-align: center; }
        .info-table td { padding: 3px 6px; }
        .info-table .label { width: 25%; font-weight: 600; }
        .info-table .value { width: 25%; }
        .info-table .status-cell { text-align: center; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: 700; }
        .check-yes { font-size: 14px; color: #059669; }
        .check-no { font-size: 14px; color: #dc2626; }
        .note-box { border: 1px solid #9ca3af; padding: 8px; margin-top: 8px; font-size: 9px; min-height: 40px; }
        .note-label { font-weight: 700; margin-bottom: 4px; }
        .section-title { font-weight: 700; font-size: 10px; margin: 12px 0 6px; }
        .footer { margin-top: 24px; text-align: center; font-size: 8px; color: #888; }
        .logo { text-align: left; margin-bottom: 8px; }
        .logo img { height: 50px; }
        .page-break { page-break-after: always; }
        .signature-table td { height: 50px; vertical-align: bottom; text-align: center; font-size: 9px; }
        .signature-line { border-top: 1px solid #000; width: 80%; margin: 0 auto; padding-top: 4px; }
        .form-page { page-break-after: always; }
        .form-page:last-child { page-break-after: auto; }
        .form-header { table-layout: fixed; margin-bottom: 7px; border: 2px solid #000; }
        .form-header td { border: 1px solid #000; color: #000; }
        .form-logo { text-align: center; vertical-align: middle; padding: 5px; height: 58px; }
        .form-logo img { width: 38px; height: 38px; object-fit: contain; }
        .company-name { height: 19px; text-align: center; vertical-align: middle; font-size: 11px; font-weight: 700; padding: 3px 6px; }
        .document-title { text-align: center; vertical-align: middle; font-size: 10.5px; line-height: 1.2; font-weight: 700; padding: 5px 6px; }
        .document-meta-label, .document-meta-value { height: 9px; vertical-align: middle; font-size: 6.8px; padding: 2px 5px; }
        .document-meta-label { text-align: left; }
        .document-meta-value { text-align: left; }
        .control-row { margin-bottom: 8px; font-size: 8px; font-weight: 700; }
        .control-label { display: inline-block; margin-right: 6px; }
        .fake-input { display: inline-block; min-width: 90px; border: 1px solid #9ca3af; border-radius: 3px; padding: 4px 7px; font-weight: 400; background: #fff; }
        .fake-select { display: inline-block; min-width: 215px; border: 1px solid #9ca3af; border-radius: 3px; padding: 4px 7px; font-weight: 400; background: #fff; }
        .status-button { display: inline-block; min-width: 58px; border-radius: 3px; padding: 5px 9px; text-align: center; font-size: 8px; font-weight: 700; background: #e5e7eb; color: #64748b; }
        .form-table { table-layout: fixed; margin-bottom: 0; }
        .form-table th, .form-table td { font-size: 8px; padding: 4px 5px; }
        .form-table .section-row td { background: #f8fafc; font-weight: 700; font-size: 9px; }
        .condition-cell { text-align: center; font-size: 13px; font-weight: 700; }
        .form-panel { border: 1px solid #cbd5e1; background: #f8fafc; border-radius: 4px; padding: 8px; margin-top: 10px; }
        .form-panel-title { font-size: 9px; font-weight: 700; margin-bottom: 6px; }
        .textarea-box { min-height: 42px; border: 1px solid #cbd5e1; border-radius: 3px; background: #eef2f7; padding: 7px; font-size: 8px; }
        .hint-text { margin-top: 5px; font-size: 7px; color: #64748b; }
        .photo-grid { width: 100%; border-collapse: separate; border-spacing: 6px; margin: 0; }
        .photo-card { width: 50%; border: 1px solid #cbd5e1; background: #fff; padding: 5px; vertical-align: top; }
        .photo-card img { width: 100%; height: 92px; object-fit: cover; display: block; }
        .photo-name { margin-top: 4px; font-size: 7px; color: #64748b; }
    </style>
</head>
<body>

@php
    $form = $entry['form'] ?? [];
    $tid = $entry['template_id'] ?? '';
    $labelMap = [
        'kotak_p3k' => 'Kotak P3K',
        'non_warehouse_sanitation' => 'Kebersihan dan Sanitasi (Non-Warehouse Area)',
        'apar_smoke_detector_fire_alarm' => 'APAR, Smoke Detector, Fire Alarm',
        'pengangkutan_sampah_pt_sier' => 'Pengangkutan Sampah PT SIER',
        'warehouse_sanitation_1' => 'Kebersihan dan Sanitasi (Warehouse Area)',
        'personal_hygiene_karyawan' => 'Personal Hygiene Karyawan',
        'sarana_dan_prasarana' => 'Sarana dan Prasarana',
        'patroli_security' => 'Patroli Security',
        'site_visit_hse' => 'Site Visit HSE',
        'site_visit_maintenance' => 'Site Visit Maintenance',
        'genset_running' => 'Pemanasan (Running) Genset',
        'running_genset' => 'Running Genset',
        'kompresor_harian' => 'Kompresor',
        'charger_baterai' => 'Charger Baterai',
        'checklist_baterai' => 'Checklist Baterai',
        'jadwal_cleaning_ob' => 'Jadwal Cleaning OB',
    ];
    $label = $labelMap[$tid] ?? $entry['name'] ?? 'Checklist';
    $formLayoutTemplates = [
        'patroli_security',
        'site_visit_hse',
        'sarana_dan_prasarana',
        'jadwal_cleaning_ob',
    ];
    $usesFormLayout = in_array($tid, $formLayoutTemplates, true);
@endphp

@unless($usesFormLayout)
    <div class="header">
        <h1>PT. GOLDEN MULTI INDOTAMA</h1>
        <h2>{{ strtoupper($label) }}</h2>
        @if(!empty($form['document_no']))
            <div class="sub">Doc. No: {{ $form['document_no'] }} | Rev: {{ $form['rev'] ?? '-' }} | Page: {{ $form['page'] ?? '-' }}</div>
        @endif
    </div>
@endunless

@if($tid === 'kotak_p3k')
    @include('pdf.checklist-templates.kotak_p3k', ['entry' => $entry, 'form' => $form])
@elseif($tid === 'non_warehouse_sanitation')
    @include('pdf.checklist-templates.non_warehouse_sanitation', ['entry' => $entry, 'form' => $form])
@elseif($tid === 'apar_smoke_detector_fire_alarm')
    @include('pdf.checklist-templates.fire_safety', ['entry' => $entry, 'form' => $form])
@elseif($tid === 'pengangkutan_sampah_pt_sier')
    @include('pdf.checklist-templates.waste_transport', ['entry' => $entry, 'form' => $form])
@elseif($tid === 'warehouse_sanitation_1')
    @include('pdf.checklist-templates.warehouse_sanitation', ['entry' => $entry, 'form' => $form])
@elseif($tid === 'personal_hygiene_karyawan')
    @include('pdf.checklist-templates.personal_hygiene', ['entry' => $entry, 'form' => $form])
@elseif($tid === 'sarana_dan_prasarana')
    @include('pdf.checklist-templates.sarana_prasarana', ['entry' => $entry, 'form' => $form])
@elseif($tid === 'patroli_security')
    @include('pdf.checklist-templates.patroli_security', ['entry' => $entry, 'form' => $form])
@elseif($tid === 'site_visit_hse')
    @include('pdf.checklist-templates.site_visit_hse', ['entry' => $entry, 'form' => $form])
@elseif($tid === 'site_visit_maintenance')
    @include('pdf.checklist-templates.site_visit_maintenance', ['entry' => $entry, 'form' => $form])
@elseif($tid === 'genset_running')
    @include('pdf.checklist-templates.genset_running', ['entry' => $entry, 'form' => $form])
@elseif($tid === 'running_genset')
    @include('pdf.checklist-templates.running_genset', ['entry' => $entry, 'form' => $form])
@elseif(in_array($tid, ['kompresor_harian', 'charger_baterai', 'checklist_baterai']))
    @include('pdf.checklist-templates.daily_machine', ['entry' => $entry, 'form' => $form])
@elseif($tid === 'jadwal_cleaning_ob')
    @include('pdf.checklist-templates.cleaning_ob', ['entry' => $entry, 'form' => $form])
@else
    @include('pdf.checklist-templates.generic', ['entry' => $entry, 'form' => $form])
@endif

@unless($usesFormLayout)
    <div class="footer">
        Dicetak pada {{ now()->format('d/m/Y H:i') }} | {{ $label }}
    </div>
@endunless

</body>
</html>
