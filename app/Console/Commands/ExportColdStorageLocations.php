<?php

namespace App\Console\Commands;

use App\Services\CrossOdoo\ColdStorageLocationExportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;

class ExportColdStorageLocations extends Command
{
    protected $signature = 'cold-storage:export-locations
        {--date= : Export date in YYYY-MM-DD format}
        {--directory= : Override the default export directory}';

    protected $description = 'Export filtered Odoo Cold Storage locations to Excel';

    private const DEFAULT_DIRECTORY = 'H:\\Customer\\LocalUser\\soh\\SOH';

    private const HEADERS = [
        'Location',
        'Product',
        'Package',
        'Lot/Serial Number',
        'Owner',
        'Plate Number',
        'Reference',
        'Source Document',
        'On Hand Quantity',
        'Reserved Quantity',
        'Available Quantity',
        'Qty Kilogram',
        'Unit',
    ];

    public function handle(ColdStorageLocationExportService $exportService): int
    {
        $dateOption = $this->option('date');
        $exportDate = $dateOption
            ? Validator::make(['date' => $dateOption], ['date' => 'required|date_format:Y-m-d'])->validate()['date']
            : now('Asia/Jakarta')->toDateString();
        $directory = $this->option('directory') ?: self::DEFAULT_DIRECTORY;
        $rows = $exportService->fetchRows();

        File::ensureDirectoryExists($directory);
        if (! is_dir($directory) || ! is_writable($directory)) {
            throw new RuntimeException("Folder export tidak dapat ditulis: {$directory}");
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Odoo Locations');
        $sheet->fromArray(self::HEADERS, null, 'A1');

        $values = array_map(
            fn (array $row): array => array_map(fn (string $header) => $row[$header] ?? '', self::HEADERS),
            $rows,
        );
        if ($values !== []) {
            $sheet->fromArray($values, null, 'A2');
        }

        foreach (range('A', 'M') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        $sheet->freezePane('A2');
        $filename = $directory.DIRECTORY_SEPARATOR.'Odoo_Locations_'.$exportDate.'.xlsx';
        (new Xlsx($spreadsheet))->save($filename);

        $this->info(sprintf('Exported %d Odoo location rows to %s', count($rows), $filename));

        return self::SUCCESS;
    }
}
