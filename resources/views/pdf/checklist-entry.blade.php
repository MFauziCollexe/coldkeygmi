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
        'site_visit_maintenance',
        'genset_running',
        'running_genset',
        'generic',
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
    @include('pdf.checklist-templates.generic', ['entry' => $entry, 'form' => $form, 'title' => $label])
@endif

@unless($usesFormLayout)
    <div class="footer">
        Dicetak pada {{ now()->format('d/m/Y H:i') }} | {{ $label }}
    </div>
@endunless