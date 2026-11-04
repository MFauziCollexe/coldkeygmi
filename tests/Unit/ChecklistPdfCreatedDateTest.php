<?php

namespace Tests\Unit;

use App\Http\Controllers\ChecklistEntryController;
use App\Models\ChecklistHeader;
use Carbon\Carbon;
use ReflectionMethod;
use Tests\TestCase;

class ChecklistPdfCreatedDateTest extends TestCase
{
    public function test_kotak_p3k_pdf_falls_back_to_legacy_root_data_when_selected_location_is_empty(): void
    {
        $html = view('pdf.checklist', ['entry' => [
            'template_id' => 'kotak_p3k',
            'created_date' => '09/10/2026',
            'form' => [
                'location' => 'ruang_kontrol',
                'active_month' => 'jul',
                'year' => '2026',
                'monthly_check_dates' => ['jul' => '30 Juli 2026'],
                'items' => [[
                    'name' => 'Kasa steril terbungkus',
                    'quantity' => 20,
                    'months' => ['jul' => ['a' => '1', 'r' => '0']],
                ]],
                'location_entries' => [
                    'ruang_admin' => ['items' => []],
                    'ruang_kontrol' => ['items' => [], 'monthly_check_dates' => []],
                    'pos_security' => ['items' => []],
                ],
            ],
        ]])->render();

        $this->assertStringContainsString('Lantai 1 Belakang (R.Kontrol)', $html);
        $this->assertStringContainsString('Kasa steril terbungkus', $html);
        $this->assertStringContainsString('30 Juli 2026', $html);
        $this->assertStringContainsString('>1</td>', $html);
    }

    public function test_listed_hse_pdf_views_match_their_saved_form_shapes(): void
    {
        $kotakHtml = view('pdf.checklist', ['entry' => [
            'template_id' => 'kotak_p3k',
            'created_date' => '09/10/2026',
            'form' => [
                'location' => 'ruang_admin', 'box_type' => 'A', 'pic' => 'HSE', 'year' => '2026',
                'document_no' => 'FRM.HSE.11.01', 'rev' => '00', 'date' => '09 Oktober 2026', 'page' => '1',
                'active_month' => 'may',
                'location_entries' => ['ruang_admin' => [
                    'items' => [['name' => 'Kasa steril terbungkus', 'quantity' => 20, 'months' => ['may' => ['a' => '20', 'r' => '0']]]],
                    'monthly_check_dates' => ['may' => '12 Mei 2026'], 'monthly_notes' => ['may' => 'Catatan Mei'],
                    'approved_months' => ['may'], 'submitted_months' => [],
                ], 'ruang_kontrol' => [
                    'items' => [['name' => 'Perban 5 cm', 'quantity' => 2, 'months' => ['may' => ['a' => '1', 'r' => '1']]]],
                    'monthly_check_dates' => ['may' => '15 Mei 2026'], 'monthly_notes' => ['may' => 'Catatan Kontrol'],
                    'approved_months' => [], 'submitted_months' => ['may'],
                ]],
            ],
        ]])->render();
        $this->assertStringContainsString('Lantai 1 Depan (R.Admin)', $kotakHtml);
        $this->assertStringContainsString('Kasa steril terbungkus', $kotakHtml);
        $this->assertStringContainsString('12 Mei 2026', $kotakHtml);
        $this->assertStringContainsString('Catatan Mei', $kotakHtml);
        $this->assertStringContainsString('>20</td>', $kotakHtml);
        $this->assertStringContainsString('colspan="2">Mei</th>', $kotakHtml);
        $this->assertStringContainsString('>A</th>', $kotakHtml);
        $this->assertStringContainsString('>R</th>', $kotakHtml);
        $this->assertStringContainsString('Lantai 1 Belakang (R.Kontrol)', $kotakHtml);
        $this->assertStringContainsString('Perban 5 cm', $kotakHtml);
        $this->assertStringContainsString('Catatan Kontrol', $kotakHtml);

        $fireHtml = view('pdf.checklist', ['entry' => [
            'template_id' => 'apar_smoke_detector_fire_alarm',
            'created_date' => '09/10/2026',
            'form' => [
                'card_type' => 'apar', 'location' => 'lantai_1_loading_dock_powder_6kg_003', 'year' => '2026',
                'active_month' => 'jul',
                'location_records' => ['apar::lantai_1_loading_dock_powder_6kg_003' => [
                    'rows' => [['id' => 'indikator_tekanan', 'name' => 'Indikator Tekanan', 'months' => ['jul' => 'no']]],
                    'monthly_check_dates' => ['jul' => '14 Juli 2026'], 'monthly_notes' => ['jul' => 'Tekanan rendah'],
                ], 'fire_alarm::lobby_lantai_1' => [
                    'rows' => [['id' => 'terlihat_jelas', 'name' => 'Terlihat Jelas', 'months' => ['jul' => 'yes']]],
                    'monthly_check_dates' => ['jul' => '30 Juli 2026'], 'monthly_notes' => ['jul' => 'Catatan Fire Alarm'],
                ]],
            ],
        ]])->render();
        $this->assertStringContainsString('KARTU PEMELIHARAAN APAR', $fireHtml);
        $this->assertStringContainsString('Lantai 1 - R. Loading Dock - Powder 6 Kg 003', $fireHtml);
        $this->assertStringContainsString('14 Juli 2026', $fireHtml);
        $this->assertStringContainsString('Tekanan rendah', $fireHtml);
        $this->assertStringContainsString('check-no', $fireHtml);
        $this->assertStringContainsString('Jenis Kartu: <strong>Fire Alarm</strong>', $fireHtml);
        $this->assertStringContainsString('Lantai 1 Dalam', $fireHtml);
        $this->assertStringContainsString('Catatan Fire Alarm', $fireHtml);

        $fireAlarmHtml = view('pdf.checklist', ['entry' => [
            'template_id' => 'apar_smoke_detector_fire_alarm',
            'created_date' => '09/10/2026',
            'form' => [
                'card_type' => 'fire_alarm', 'location' => 'lobby_lantai_1', 'year' => '2026', 'active_month' => 'jul',
                'location_records' => ['fire_alarm::lobby_lantai_1' => ['rows' => []]],
            ],
        ]])->render();
        $this->assertStringContainsString('Jenis Kartu: <strong>Fire Alarm</strong>', $fireAlarmHtml);
        $this->assertStringContainsString('Lokasi:</strong> Lantai 1 Dalam', $fireAlarmHtml);

        $personalHtml = view('pdf.checklist', ['entry' => [
            'template_id' => 'personal_hygiene_karyawan',
            'created_date' => '09/10/2026',
            'form' => [
                'year' => '2026', 'period' => '2026-07', 'employee_name' => 'Nathania', 'gender' => 'female', 'nik' => '123', 'bagian' => 'HSE',
                'rows' => [['id' => 'suhu_tubuh_tidak_panas', 'name' => 'Suhu tubuh tidak panas', 'days' => [31 => 'yes']]],
            ],
        ]])->render();
        $this->assertStringContainsString('FRM.HSE.09.01', $personalHtml);
        $this->assertStringContainsString('Nama Karyawan', $personalHtml);
        $this->assertStringContainsString('Laki-Laki', $personalHtml);
        $this->assertStringContainsString('>31<', $personalHtml);
        $this->assertStringContainsString('Suhu tubuh tidak panas', $personalHtml);

        $siteVisitHtml = view('pdf.checklist', ['entry' => [
            'template_id' => 'site_visit_hse',
            'created_date' => '09/10/2026',
            'form' => [
                'selected_area' => 'lantai_1_area_dalam', 'date_value' => '2026-10-09', 'document_no' => 'FRM.HSE.15.01',
                'sections' => [
                    ['id' => 'lantai_1_area_dalam', 'title' => 'C. LANTAI 1 - AREA DALAM', 'items' => [['no' => 1, 'name' => 'Area bersih', 'status' => 'yes']]],
                    ['id' => 'warehouse', 'title' => 'Warehouse', 'items' => [['no' => 1, 'name' => 'Area lain', 'status' => 'no']]],
                ],
            ],
        ]])->render();
        $this->assertStringContainsString('Area:', $siteVisitHtml);
        $this->assertStringContainsString('Lantai 1 Dalam', $siteVisitHtml);
        $this->assertStringContainsString('C. LANTAI 1 - AREA DALAM', $siteVisitHtml);
        $this->assertStringContainsString('Area bersih', $siteVisitHtml);
        $this->assertStringContainsString('Warehouse', $siteVisitHtml);
        $this->assertStringContainsString('Area lain', $siteVisitHtml);
    }

    public function test_kotak_p3k_pdf_includes_previous_month_answers_from_saved_entries(): void
    {
        $controller = new ChecklistEntryController();
        $method = new ReflectionMethod($controller, 'buildMonthlyChecklistPdfEntry');
        $method->setAccessible(true);

        $entry = $method->invoke($controller, [[
            'template_id' => 'kotak_p3k',
            'form' => [
                'location' => 'ruang_admin',
                'year' => '2026',
                'location_entries' => [
                    'ruang_admin' => [
                        'items' => [[
                            'id' => 'kasa',
                            'name' => 'Kasa steril terbungkus',
                            'quantity' => 20,
                            'months' => ['jun' => ['a' => '20', 'r' => '0'], 'dec' => ['a' => '99', 'r' => '0']],
                        ]],
                        'monthly_check_dates' => ['jun' => '28 Juni 2026'],
                    ],
                ],
            ],
        ], [
            'template_id' => 'kotak_p3k',
            'form' => [
                'location' => 'ruang_admin',
                'year' => '2026',
                'location_entries' => [
                    'ruang_admin' => [
                        'items' => [[
                            'id' => 'kasa',
                            'name' => 'Kasa steril terbungkus',
                            'quantity' => 20,
                            'months' => ['jan' => ['a' => '20', 'r' => '0']],
                        ]],
                        'monthly_check_dates' => ['jan' => '28 Januari 2026'],
                    ],
                ],
            ],
        ]], 'kotak_p3k', '2026-11');

        $this->assertSame('nov', $entry['form']['active_month']);
        $this->assertArrayNotHasKey(
            'dec',
            $entry['form']['location_entries']['ruang_admin']['items'][0]['months'],
        );
        $html = view('pdf.checklist', ['entry' => $entry])->render();

        $this->assertStringContainsString('<th colspan="2">Jan</th>', $html);
        $this->assertStringContainsString('<th colspan="2">Nov</th>', $html);
        $this->assertSame(0, substr_count($html, '<span class="check-yes">'));
        $this->assertSame(3, substr_count($html, '>20</td>'));
        $this->assertSame(2, substr_count($html, '>0</td>'));
        $this->assertStringNotContainsString('>99</td>', $html);
        $this->assertStringContainsString('28 Januari 2026', $html);
        $this->assertStringContainsString('28 Juni 2026', $html);
    }

    public function test_kotak_p3k_quantity_validation_accepts_non_negative_integers(): void
    {
        $controller = new ChecklistEntryController();
        $method = new ReflectionMethod($controller, 'normalizeKotakP3KQuantities');
        $method->setAccessible(true);

        $form = $method->invoke($controller, [
            'location_entries' => [
                'ruang_admin' => [
                    'items' => [[
                        'months' => [
                            'jan' => ['a' => '20', 'r' => '0'],
                            'feb' => ['a' => '', 'r' => ''],
                        ],
                    ]],
                ],
            ],
        ]);

        $this->assertSame('20', $form['location_entries']['ruang_admin']['items'][0]['months']['jan']['a']);
        $this->assertSame('0', $form['location_entries']['ruang_admin']['items'][0]['months']['jan']['r']);
    }

    public function test_kotak_p3k_quantity_validation_rejects_non_numeric_values(): void
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $controller = new ChecklistEntryController();
        $method = new ReflectionMethod($controller, 'normalizeKotakP3KQuantities');
        $method->setAccessible(true);
        $method->invoke($controller, [
            'items' => [[
                'months' => [
                    'jan' => ['a' => '-', 'r' => '1.5'],
                ],
            ]],
        ]);
    }

    public function test_fire_safety_pdf_combines_saved_months_into_one_year_to_date_entry(): void
    {
        $controller = new ChecklistEntryController();
        $method = new ReflectionMethod($controller, 'buildMonthlyChecklistPdfEntry');
        $method->setAccessible(true);

        $entry = $method->invoke($controller, [[
            'template_id' => 'apar_smoke_detector_fire_alarm',
            'name' => 'APAR Checklist',
            'form' => [
                'card_type' => 'apar',
                'location' => 'lantai_1_admin_powder_3kg_001',
                'year' => '2026',
                'location_records' => [
                    'apar::lantai_1_admin_powder_3kg_001' => [
                        'rows' => [[
                            'id' => 'tekanan',
                            'name' => 'Indikator Tekanan',
                            'months' => ['jun' => 'no'],
                        ]],
                        'monthly_check_dates' => ['jun' => '28 Juni 2026'],
                    ],
                ],
            ],
        ], [
            'template_id' => 'apar_smoke_detector_fire_alarm',
            'name' => 'APAR Checklist',
            'form' => [
                'card_type' => 'apar',
                'location' => 'lantai_1_admin_powder_3kg_001',
                'year' => '2026',
                'location_records' => [
                    'apar::lantai_1_admin_powder_3kg_001' => [
                        'rows' => [[
                            'id' => 'tekanan',
                            'name' => 'Indikator Tekanan',
                            'months' => ['jan' => 'yes'],
                        ]],
                        'monthly_check_dates' => ['jan' => '28 Januari 2026'],
                    ],
                ],
            ],
        ]], 'apar_smoke_detector_fire_alarm', '2026-11');

        $html = view('pdf.checklist', ['entry' => $entry])->render();

        $this->assertSame('nov', $entry['form']['active_month']);
        $this->assertSame(1, substr_count($html, '<span class="check-yes">'));
        $this->assertSame(1, substr_count($html, '<span class="check-no">'));
        $this->assertStringContainsString('28 Januari 2026', $html);
        $this->assertStringContainsString('28 Juni 2026', $html);
    }

    public function test_inspeksi_loker_pdf_uses_dedicated_matrix_layout(): void
    {
        $html = view('pdf.checklist', [
            'entry' => [
                'template_id' => 'inspeksi_loker',
                'created_date' => '09/10/2026',
                'form' => [
                    'date_value' => '2026-10',
                    'page' => 'Page 1 dari 1',
                    'document_no' => 'FRM.HSE.16.01',
                    'rev' => '00',
                    'effective_date' => '09 Oktober 2026',
                    'rows' => [
                        [
                            'no' => 1,
                            'label' => 'Kunci dan kondisi loker',
                            'lockers' => [
                                '1' => 'yes',
                                '2' => '',
                            ],
                        ],
                    ],
                    'note' => 'Perlu pemeriksaan ulang',
                ],
                    ],
        ])->render();

        $this->assertStringContainsString('Parameter', $html);
        $this->assertStringContainsString('32', $html);
        $this->assertStringContainsString('check-yes', $html);
        $this->assertStringContainsString('Perlu pemeriksaan ulang', $html);
        $this->assertStringContainsString('Tanggal Dibuat', $html);
    }

    public function test_hse_checklist_templates_match_their_saved_date_shapes(): void
    {
        $controller = new ChecklistEntryController();
        $rangeMethod = new ReflectionMethod($controller, 'matchesChecklistRange');
        $rangeMethod->setAccessible(true);
        $previewDateMethod = new ReflectionMethod($controller, 'resolvePreviewDate');
        $previewDateMethod->setAccessible(true);

        $weekTwoStart = '2026-07-08';
        $weekTwoEnd = '2026-07-14';
        $weekFiveStart = '2026-07-29';
        $weekFiveEnd = '2026-07-31';

        $kotakEntry = [
            'template_id' => 'kotak_p3k',
            'form' => [
                'year' => '2026',
                'location' => 'ruang_kontrol',
                'date' => '15 Agustus 2026',
                'location_entries' => [
                    'ruang_kontrol' => [
                        'submitted_months' => ['jul'],
                        'monthly_check_dates' => ['jul' => '14 Juli 2026'],
                    ],
                    'ruang_admin' => [
                        'approved_months' => ['jul'],
                        'monthly_check_dates' => ['jul' => '30 Juli 2026'],
                    ],
                ],
            ],
        ];
        $this->assertTrue($rangeMethod->invoke($controller, $kotakEntry, $weekTwoStart, $weekTwoEnd));
        $this->assertTrue($rangeMethod->invoke($controller, $kotakEntry, $weekFiveStart, $weekFiveEnd));
        $this->assertSame('14 Juli 2026', $previewDateMethod->invoke($controller, $kotakEntry, '2026-07'));

        $unassignedWeekEntry = [
            'template_id' => 'kotak_p3k',
            'form' => ['year' => '2026', 'location' => 'ruang_kontrol', 'location_entries' => [
                'ruang_kontrol' => ['approved_months' => ['jul']],
            ]],
        ];
        $this->assertFalse($rangeMethod->invoke($controller, $unassignedWeekEntry, $weekFiveStart, $weekFiveEnd));

        $aparEntry = [
            'template_id' => 'apar_smoke_detector_fire_alarm',
            'form' => [
                'year' => '2026',
                'card_type' => 'fire_alarm',
                'location' => 'loading_dock',
                'location_records' => [
                    'fire_alarm::loading_dock' => [
                        'approved_months' => ['jul'],
                        'monthly_check_dates' => ['jul' => '10 Juli 2026'],
                    ],
                    'apar::loading_dock_2' => [
                        'approved_months' => ['jul'],
                        'monthly_check_dates' => ['jul' => '30 Juli 2026'],
                    ],
                ],
            ],
        ];
        $this->assertTrue($rangeMethod->invoke($controller, $aparEntry, $weekTwoStart, $weekTwoEnd));
        $this->assertTrue($rangeMethod->invoke($controller, $aparEntry, $weekFiveStart, $weekFiveEnd));

        $personalHygieneEntry = [
            'template_id' => 'personal_hygiene_karyawan',
            'form' => ['period' => '2026-07', 'generated_at' => '2026-07-03'],
        ];
        $this->assertTrue($rangeMethod->invoke($controller, $personalHygieneEntry, $weekFiveStart, $weekFiveEnd));
        $this->assertFalse($rangeMethod->invoke($controller, $personalHygieneEntry, '2026-08-01', '2026-08-07'));

        $siteVisitHseEntry = [
            'template_id' => 'site_visit_hse',
            'form' => ['date_value' => '2026-07-30'],
        ];
        $this->assertTrue($rangeMethod->invoke($controller, $siteVisitHseEntry, $weekFiveStart, $weekFiveEnd));
        $this->assertFalse($rangeMethod->invoke($controller, $siteVisitHseEntry, $weekTwoStart, $weekTwoEnd));

        $lockerEntry = [
            'template_id' => 'inspeksi_loker',
            'form' => ['date_value' => '2026-07'],
        ];
        $this->assertTrue($rangeMethod->invoke($controller, $lockerEntry, $weekFiveStart, $weekFiveEnd));
        $this->assertFalse($rangeMethod->invoke($controller, $lockerEntry, '2026-08-01', '2026-08-07'));
        $this->assertSame('Juli 2026', $previewDateMethod->invoke($controller, $lockerEntry, '2026-07'));
    }

    public function test_week_preview_excludes_dates_outside_the_selected_week(): void
    {
        $controller = new ChecklistEntryController();
        $rangeMethod = new ReflectionMethod($controller, 'parseWeekRange');
        $rangeMethod->setAccessible(true);
        $range = $rangeMethod->invoke($controller, '2026-07', 5);

        $filterMethod = new ReflectionMethod($controller, 'withinDateRange');
        $filterMethod->setAccessible(true);

        $this->assertSame('2026-07-29', $range['start']);
        $this->assertSame('2026-07-31', $range['end']);
        $this->assertTrue($filterMethod->invoke($controller, [
            'form' => ['date_value' => '2026-07-29'],
        ], $range['start'], $range['end']));
        $this->assertFalse($filterMethod->invoke($controller, [
            'form' => ['date_value' => '2026-07-14'],
        ], $range['start'], $range['end']));
    }

    public function test_month_preview_range_covers_the_entire_selected_month(): void
    {
        $controller = new ChecklistEntryController();
        $method = new ReflectionMethod($controller, 'parseMonthRange');
        $method->setAccessible(true);

        $range = $method->invoke($controller, '2026-10');

        $this->assertSame('2026-10-01', $range['start']);
        $this->assertSame('2026-10-31', $range['end']);
    }

    public function test_kotak_p3k_reference_includes_all_saved_months_by_location(): void
    {
        $controller = new ChecklistEntryController();
        $method = new ReflectionMethod($controller, 'buildPreviousKotakP3KMonthReference');
        $method->setAccessible(true);

        $reference = $method->invoke($controller, [[
            'form' => [
                'year' => '2026',
                'location_entries' => [
                    'ruang_kontrol' => [
                        'items' => [
                            ['id' => 'kasa', 'months' => ['jan' => ['a' => '20', 'r' => '0'], 'mar' => ['a' => '20', 'r' => '0'], 'oct' => ['a' => '20', 'r' => '0']]],
                            ['id' => 'perban', 'months' => ['jan' => ['a' => '1', 'r' => '1'], 'mar' => ['a' => '0', 'r' => '2'], 'oct' => ['a' => '0', 'r' => '2']]],
                        ],
                        'monthly_check_dates' => [
                            'jan' => '28 Januari 2026',
                            'mar' => '28 Maret 2026',
                            'oct' => '28 Oktober 2026',
                        ],
                        'monthly_notes' => [
                            'mar' => 'Catatan bulan Maret',
                            'oct' => 'Perban perlu diganti',
                        ],
                        'submitted_months' => ['mar', 'oct'],
                        'approved_months' => ['jan'],
                    ],
                    'ruang_admin' => [
                        'items' => [
                            ['id' => 'kasa', 'months' => ['oct' => 'yes']],
                        ],
                        'monthly_check_dates' => ['oct' => '28 Oktober 2026'],
                        'monthly_notes' => ['oct' => ''],
                        'submitted_months' => [],
                        'approved_months' => [],
                    ],
                ],
            ],
        ]], \Illuminate\Support\Carbon::parse('2026-09-01'));

        $this->assertSame('2026', $reference['year']);
        $this->assertSame('Januari', $reference['months']['jan']['label']);
        $this->assertSame('September', $reference['months']['sep']['label']);
        $this->assertSame(['a' => '20', 'r' => '0'], $reference['locations']['ruang_kontrol']['months']['jan']['items']['kasa']);
        $this->assertSame(['a' => '0', 'r' => '2'], $reference['locations']['ruang_kontrol']['months']['mar']['items']['perban']);
        $this->assertSame('Catatan bulan Maret', $reference['locations']['ruang_kontrol']['months']['mar']['note']);
        $this->assertSame('Waiting HSE', $reference['locations']['ruang_kontrol']['months']['mar']['status']);
        $this->assertSame('Approved', $reference['locations']['ruang_kontrol']['months']['jan']['status']);
        $this->assertArrayNotHasKey('oct', $reference['months']);
        $this->assertArrayNotHasKey('oct', $reference['locations']['ruang_kontrol']['months']);
    }

    public function test_fire_safety_reference_includes_months_by_card_type_and_location(): void
    {
        $controller = new ChecklistEntryController();
        $method = new ReflectionMethod($controller, 'buildPreviousFireSafetyMonthReference');
        $method->setAccessible(true);

        $reference = $method->invoke($controller, [[
            'form' => [
                'year' => '2026',
                'location_records' => [
                    'fire_alarm::warehouse' => [
                        'rows' => [
                            ['id' => 'alarm', 'months' => ['jan' => 'yes', 'mar' => 'no', 'oct' => 'yes']],
                        ],
                        'monthly_check_dates' => ['jan' => '28 Januari 2026', 'mar' => '28 Maret 2026'],
                        'monthly_notes' => ['mar' => 'Alarm perlu diperiksa'],
                        'submitted_months' => ['mar'],
                        'approved_months' => ['jan'],
                    ],
                    'apar::warehouse' => [
                        'rows' => [
                            ['id' => 'apar', 'months' => ['mar' => 'yes']],
                        ],
                        'monthly_check_dates' => [],
                        'monthly_notes' => [],
                        'submitted_months' => [],
                        'approved_months' => [],
                    ],
                ],
            ],
        ]], \Illuminate\Support\Carbon::parse('2026-09-01'));

        $this->assertSame('2026', $reference['year']);
        $this->assertSame('September', $reference['months']['sep']['label']);
        $this->assertSame('yes', $reference['records']['fire_alarm::warehouse']['months']['jan']['items']['alarm']);
        $this->assertSame('no', $reference['records']['fire_alarm::warehouse']['months']['mar']['items']['alarm']);
        $this->assertSame('Approved', $reference['records']['fire_alarm::warehouse']['months']['jan']['status']);
        $this->assertSame('Alarm perlu diperiksa', $reference['records']['fire_alarm::warehouse']['months']['mar']['note']);
        $this->assertSame('yes', $reference['records']['apar::warehouse']['months']['mar']['items']['apar']);
        $this->assertArrayNotHasKey('oct', $reference['months']);
    }

    public function test_pdf_templates_show_the_checklist_creation_date(): void
    {
        $header = new ChecklistHeader([
            'entry_code' => 'CHK-001',
            'payload_summary_json' => [
                'id' => 'CHK-001',
                'template_id' => 'site_visit_maintenance',
                'name' => 'Site Visit Maintenance',
                'form' => [
                    'effective_date' => '01 Januari 2020',
                ],
            ],
        ]);
        $header->setAttribute('created_at', Carbon::parse('2026-10-09 14:35:00'));

        $controller = new ChecklistEntryController();
        $method = new ReflectionMethod($controller, 'extractEntryFromHeader');
        $method->setAccessible(true);
        $entry = $method->invoke($controller, $header);

        $this->assertSame('14.35', $entry['created_at']);
        $this->assertSame('09/10/2026', $entry['created_date']);

        $formHeaderHtml = view('pdf.checklist-templates.partials.form_header', [
            'entry' => $entry,
            'form' => $entry['form'],
            'title' => 'SITE VISIT MAINTENANCE',
            'pageText' => '1 dari 1',
        ])->render();

        $this->assertStringContainsString('Tanggal Dibuat', $formHeaderHtml);
        $this->assertStringContainsString('09/10/2026', $formHeaderHtml);
        $this->assertStringContainsString('01 Januari 2020', $formHeaderHtml);

        $legacyHtml = view('pdf.checklist', [
            'entry' => array_merge($entry, [
                'template_id' => 'non_warehouse_sanitation',
                'form' => [],
            ]),
        ])->render();

        $this->assertStringContainsString('Tanggal Dibuat: 09/10/2026', $legacyHtml);
    }
}
