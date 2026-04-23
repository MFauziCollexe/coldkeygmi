<?php

namespace App\Services;

use App\Models\MergePdfJob;
use App\Models\SplitPdfJob;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class PdfToolkitService
{
    private const STORAGE_DISK = 'local';
    private const MERGE_UPLOADS_PATH = 'pdf-toolkit/merge/uploads';
    private const MERGE_OUTPUTS_PATH = 'pdf-toolkit/merge/outputs';
    private const SPLIT_UPLOADS_PATH = 'pdf-toolkit/split/uploads';
    private const SPLIT_OUTPUTS_PATH = 'pdf-toolkit/split/outputs';
    private const TEMP_PATH = 'pdf-toolkit/temp';

    public function merge(MergePdfJob $job): bool
    {
        $disk = Storage::disk(self::STORAGE_DISK);
        $tempOutputPath = null;

        try {
            $this->ensureDirectoriesExist();

            $job->update([
                'status' => 'processing',
                'error_message' => null,
                'started_at' => now(),
                'completed_at' => null,
            ]);

            $inputPaths = collect($job->input_paths ?? [])
                ->map(fn (string $path) => $disk->path($path))
                ->all();

            if (count($inputPaths) < 2) {
                throw new RuntimeException('Minimal 2 file PDF diperlukan untuk merge.');
            }

            foreach ($inputPaths as $path) {
                if (!is_file($path)) {
                    throw new RuntimeException('Input file tidak ditemukan: ' . $path);
                }
            }

            $safeName = 'merged_pdf_' . now()->format('YmdHis');
            $outputFilename = $safeName . '.pdf';
            $tempOutputPath = $this->tempPath($outputFilename);

            $command = $this->buildMergeCommand($inputPaths, $tempOutputPath);
            exec($command, $output, $returnCode);

            if ($returnCode !== 0 || !is_file($tempOutputPath)) {
                throw new RuntimeException('Merge PDF gagal diproses oleh GhostScript.');
            }

            $outputPath = self::MERGE_OUTPUTS_PATH . '/' . $outputFilename;
            $disk->put($outputPath, file_get_contents($tempOutputPath));

            $job->update([
                'status' => 'completed',
                'output_filename' => $outputFilename,
                'output_path' => $outputPath,
                'output_size' => filesize($tempOutputPath),
                'completed_at' => now(),
            ]);

            return true;
        } catch (\Throwable $exception) {
            $job->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
                'completed_at' => now(),
            ]);

            return false;
        } finally {
            if ($tempOutputPath && is_file($tempOutputPath)) {
                @unlink($tempOutputPath);
            }
        }
    }

    public function split(SplitPdfJob $job): bool
    {
        $disk = Storage::disk(self::STORAGE_DISK);
        $tempDir = null;

        try {
            $this->ensureDirectoriesExist();

            $job->update([
                'status' => 'processing',
                'error_message' => null,
                'started_at' => now(),
                'completed_at' => null,
            ]);

            $originalPath = $disk->path($job->original_path);
            if (!is_file($originalPath)) {
                throw new RuntimeException('Original file tidak ditemukan: ' . $originalPath);
            }

            $pageCount = $this->getPdfPageCount($originalPath);
            if ($pageCount < 1) {
                throw new RuntimeException('Jumlah halaman PDF tidak dapat dibaca.');
            }

            $ranges = $job->split_mode === 'all_pages'
                ? $this->buildSinglePageRanges($pageCount)
                : $this->normalizeRanges($job->page_ranges ?? [], $pageCount);

            if (empty($ranges)) {
                throw new RuntimeException('Range split tidak valid.');
            }

            $tempDir = $this->tempDirectory('split_' . $job->id . '_' . Str::random(8));
            $baseName = Str::slug(pathinfo($job->original_filename, PATHINFO_FILENAME), '_') ?: 'split_pdf';
            $outputs = [];

            foreach ($ranges as $index => $range) {
                $partName = sprintf('%s_part_%02d_p%s-%s.pdf', $baseName, $index + 1, $range['start'], $range['end']);
                $partPath = $tempDir . DIRECTORY_SEPARATOR . $partName;
                $command = $this->buildSplitCommand($originalPath, $partPath, $range['start'], $range['end']);

                exec($command, $output, $returnCode);

                if ($returnCode !== 0 || !is_file($partPath)) {
                    throw new RuntimeException('Split PDF gagal pada halaman ' . $range['start'] . '-' . $range['end'] . '.');
                }

                $outputs[] = [
                    'filename' => $partName,
                    'path' => $partPath,
                ];
            }

            $finalFile = $this->buildSplitOutput($outputs, $baseName, $disk);

            $job->update([
                'status' => 'completed',
                'output_filename' => $finalFile['filename'],
                'output_path' => $finalFile['storage_path'],
                'output_type' => $finalFile['type'],
                'output_count' => count($outputs),
                'output_size' => $finalFile['size'],
                'completed_at' => now(),
            ]);

            return true;
        } catch (\Throwable $exception) {
            $job->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
                'completed_at' => now(),
            ]);

            return false;
        } finally {
            if ($tempDir && is_dir($tempDir)) {
                File::deleteDirectory($tempDir);
            }
        }
    }

    public function downloadMerge(MergePdfJob $job): mixed
    {
        if (!$job->output_path || !$job->output_filename) {
            throw new RuntimeException('File merge belum tersedia.');
        }

        return Storage::disk(self::STORAGE_DISK)->download($job->output_path, $job->output_filename);
    }

    public function downloadSplit(SplitPdfJob $job): mixed
    {
        if (!$job->output_path || !$job->output_filename) {
            throw new RuntimeException('File split belum tersedia.');
        }

        return Storage::disk(self::STORAGE_DISK)->download($job->output_path, $job->output_filename);
    }

    public function deleteMergeJob(MergePdfJob $job): bool
    {
        $disk = Storage::disk(self::STORAGE_DISK);

        foreach (($job->input_paths ?? []) as $path) {
            if ($path && $disk->exists($path)) {
                $disk->delete($path);
            }
        }

        if ($job->output_path && $disk->exists($job->output_path)) {
            $disk->delete($job->output_path);
        }

        $job->delete();

        return true;
    }

    public function deleteSplitJob(SplitPdfJob $job): bool
    {
        $disk = Storage::disk(self::STORAGE_DISK);

        if ($job->original_path && $disk->exists($job->original_path)) {
            $disk->delete($job->original_path);
        }

        if ($job->output_path && $disk->exists($job->output_path)) {
            $disk->delete($job->output_path);
        }

        $job->delete();

        return true;
    }

    public function sanitizePdfFilename(string $filename, string $fallback = 'document'): string
    {
        $baseName = pathinfo($filename, PATHINFO_FILENAME);
        $safeBaseName = Str::slug($baseName, '_');

        if ($safeBaseName === '') {
            $safeBaseName = $fallback;
        }

        return Str::limit($safeBaseName, 120, '') . '.pdf';
    }

    public function storeUploadedFile($file, string $directory, string $fallback = 'document'): array
    {
        $safeFilename = $this->sanitizePdfFilename($file->getClientOriginalName(), $fallback);
        $storedFilename = Str::uuid()->toString() . '_' . $safeFilename;
        $path = $directory . '/' . $storedFilename;

        Storage::disk(self::STORAGE_DISK)->putFileAs($directory, $file, $storedFilename);

        return [
            'original_filename' => $safeFilename,
            'path' => $path,
        ];
    }

    public function mergeUploadDirectory(): string
    {
        return self::MERGE_UPLOADS_PATH;
    }

    public function splitUploadDirectory(): string
    {
        return self::SPLIT_UPLOADS_PATH;
    }

    private function buildMergeCommand(array $inputPaths, string $outputPath): string
    {
        $arguments = [
            $this->escapeShellPath($this->getGhostScriptPath()),
            '-sDEVICE=pdfwrite',
            '-dCompatibilityLevel=1.4',
            '-dNOPAUSE',
            '-dQUIET',
            '-dBATCH',
            '-dDetectDuplicateImages=true',
            '-dCompressFonts=true',
            '-dSubsetFonts=true',
            '-sOutputFile=' . $this->escapeShellPath($outputPath),
        ];

        foreach ($inputPaths as $path) {
            $arguments[] = $this->escapeShellPath($path);
        }

        return implode(' ', $arguments);
    }

    private function buildSplitCommand(string $inputPath, string $outputPath, int $firstPage, int $lastPage): string
    {
        return implode(' ', [
            $this->escapeShellPath($this->getGhostScriptPath()),
            '-sDEVICE=pdfwrite',
            '-dCompatibilityLevel=1.4',
            '-dNOPAUSE',
            '-dQUIET',
            '-dBATCH',
            '-dFirstPage=' . $firstPage,
            '-dLastPage=' . $lastPage,
            '-sOutputFile=' . $this->escapeShellPath($outputPath),
            $this->escapeShellPath($inputPath),
        ]);
    }

    private function buildSplitOutput(array $outputs, string $baseName, $disk): array
    {
        if (count($outputs) === 1) {
            $filename = $outputs[0]['filename'];
            $storagePath = self::SPLIT_OUTPUTS_PATH . '/' . $filename;
            $disk->put($storagePath, file_get_contents($outputs[0]['path']));

            return [
                'filename' => $filename,
                'storage_path' => $storagePath,
                'type' => 'pdf',
                'size' => filesize($outputs[0]['path']),
            ];
        }

        $zipFilename = $baseName . '_split_' . now()->format('YmdHis') . '.zip';
        $zipTempPath = $this->tempPath($zipFilename);
        $zip = new ZipArchive();

        if ($zip->open($zipTempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Gagal membuat file ZIP hasil split.');
        }

        foreach ($outputs as $output) {
            $zip->addFile($output['path'], $output['filename']);
        }

        $zip->close();

        $storagePath = self::SPLIT_OUTPUTS_PATH . '/' . $zipFilename;
        $disk->put($storagePath, file_get_contents($zipTempPath));
        $size = filesize($zipTempPath);
        @unlink($zipTempPath);

        return [
            'filename' => $zipFilename,
            'storage_path' => $storagePath,
            'type' => 'zip',
            'size' => $size,
        ];
    }

    private function normalizeRanges(array $ranges, int $pageCount): array
    {
        $normalized = [];

        foreach ($ranges as $range) {
            $start = (int) ($range['start'] ?? 0);
            $end = (int) ($range['end'] ?? 0);

            if ($start < 1 || $end < 1 || $start > $end || $end > $pageCount) {
                throw new RuntimeException('Range split tidak valid. Pastikan halaman berada di antara 1 sampai ' . $pageCount . '.');
            }

            $normalized[] = [
                'start' => $start,
                'end' => $end,
            ];
        }

        return $normalized;
    }

    private function buildSinglePageRanges(int $pageCount): array
    {
        $ranges = [];

        for ($page = 1; $page <= $pageCount; $page++) {
            $ranges[] = ['start' => $page, 'end' => $page];
        }

        return $ranges;
    }

    private function getPdfPageCount(string $inputPath): int
    {
        $ghostScriptPath = $this->getGhostScriptPath();
        $pageCountScript = $this->escapeShellArgument(sprintf(
            '(%s) (r) file runpdfbegin pdfpagecount = quit',
            str_replace('\\', '/', $inputPath)
        ));

        $attempts = [
            ['-q', '-dNOSAFER', '-dNODISPLAY'],
            ['-q', '-dNODISPLAY'],
        ];

        foreach ($attempts as $arguments) {
            $command = implode(' ', [
                $this->escapeShellPath($ghostScriptPath),
                ...$arguments,
                '-c',
                $pageCountScript,
            ]) . ' 2>&1';

            $output = [];
            $returnCode = 0;
            exec($command, $output, $returnCode);

            $pageCount = collect($output)
                ->map(fn ($line) => trim((string) $line))
                ->reverse()
                ->first(fn ($line) => preg_match('/^\d+$/', $line) === 1);

            if ($returnCode === 0 && $pageCount !== null) {
                return max(0, (int) $pageCount);
            }
        }

        throw new RuntimeException('Tidak dapat membaca jumlah halaman PDF.');
    }

    private function ensureDirectoriesExist(): void
    {
        $disk = Storage::disk(self::STORAGE_DISK);

        File::ensureDirectoryExists($disk->path(self::MERGE_UPLOADS_PATH));
        File::ensureDirectoryExists($disk->path(self::MERGE_OUTPUTS_PATH));
        File::ensureDirectoryExists($disk->path(self::SPLIT_UPLOADS_PATH));
        File::ensureDirectoryExists($disk->path(self::SPLIT_OUTPUTS_PATH));
        File::ensureDirectoryExists($disk->path(self::TEMP_PATH));
    }

    private function tempPath(string $filename): string
    {
        return Storage::disk(self::STORAGE_DISK)->path(self::TEMP_PATH . '/' . $filename);
    }

    private function tempDirectory(string $directory): string
    {
        $path = Storage::disk(self::STORAGE_DISK)->path(self::TEMP_PATH . '/' . $directory);
        File::ensureDirectoryExists($path);

        return $path;
    }

    private function getGhostScriptPath(): string
    {
        $windowsPaths = [
            'C:\\Program Files\\gs\\gs10.07.0\\bin\\gswin64c.exe',
            'C:\\Program Files\\gs\\gs10.02.0\\bin\\gswin64c.exe',
            'C:\\Program Files\\gs\\gs10.01.0\\bin\\gswin64c.exe',
            'C:\\Program Files (x86)\\gs\\gs10.07.0\\bin\\gswin32c.exe',
            'C:\\Program Files (x86)\\gs\\gs10.02.0\\bin\\gswin32c.exe',
        ];

        foreach ($windowsPaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        if (PHP_OS_FAMILY === 'Windows') {
            foreach (['gswin64c', 'gswin32c'] as $binary) {
                $gsPath = shell_exec('where.exe ' . $binary . ' 2>NUL');
                if ($gsPath) {
                    $paths = preg_split('/\r\n|\r|\n/', trim($gsPath)) ?: [];
                    if (!empty($paths[0])) {
                        return trim($paths[0]);
                    }
                }
            }
        }

        $gsPath = shell_exec('which gs 2>/dev/null');
        if ($gsPath) {
            return trim($gsPath);
        }

        throw new RuntimeException('GhostScript tidak ditemukan di sistem.');
    }

    private function escapeShellPath(string $path): string
    {
        return escapeshellarg($path);
    }

    private function escapeShellArgument(string $value): string
    {
        return escapeshellarg($value);
    }
}
