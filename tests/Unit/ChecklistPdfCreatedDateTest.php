<?php

namespace Tests\Unit;

use App\Http\Controllers\ChecklistEntryController;
use App\Models\ChecklistHeader;
use Carbon\Carbon;
use ReflectionMethod;
use Tests\TestCase;

class ChecklistPdfCreatedDateTest extends TestCase
{
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