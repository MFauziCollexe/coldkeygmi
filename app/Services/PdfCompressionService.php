<?php

namespace App\Services;

use App\Models\CompressPdfJob;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class PdfCompressionService
{
    private const STORAGE_DISK = 'local';
    private const COMPRESSED_PATH = 'compressed-pdfs';
    private const TEMP_PATH = 'temp-pdf-compress';

    public function compress(CompressPdfJob $job): bool
    {
        $compressedPath = null;
        $disk = Storage::disk(self::STORAGE_DISK);

        try {
            $this->ensureStorageDirectoriesExist();

            $job->update([
                'status' => 'processing',
                'compressed_filename' => null,
                'compressed_path' => null,
                'compressed_size' => null,
                'compression_ratio' => 0,
                'error_message' => null,
                'started_at' => now(),
                'completed_at' => null,
            ]);

            $originalPath = $disk->path($job->original_path);

            if (!is_file($originalPath)) {
                throw new RuntimeException('Original file not found: ' . $originalPath);
            }

            $filename = Str::slug(pathinfo($job->original_filename, PATHINFO_FILENAME), '_');
            $filename = $filename !== '' ? $filename : 'compressed_pdf';
            $timestamp = now()->format('YmdHis');
            $compressedFilename = "{$filename}_compressed_{$timestamp}.pdf";

            $compressedPath = $this->compressUsingGhostScript(
                $originalPath,
                $compressedFilename,
                $job->compression_level
            );

            if (!is_file($compressedPath)) {
                throw new RuntimeException('Failed to compress PDF');
            }

            $originalSize = filesize($originalPath);
            $compressedSize = filesize($compressedPath);
            $compressionRatio = $originalSize > 0
                ? round(($originalSize - $compressedSize) / $originalSize * 100, 2)
                : 0;

            $usedOriginalAsBestResult = false;

            if ($compressedSize >= $originalSize) {
                $usedOriginalAsBestResult = true;
                $compressedSize = $originalSize;
                $compressionRatio = 0;
            }

            $compressedStoragePath = self::COMPRESSED_PATH . '/' . $compressedFilename;
            $disk->put(
                $compressedStoragePath,
                file_get_contents($usedOriginalAsBestResult ? $originalPath : $compressedPath)
            );

            $job->update([
                'status' => 'completed',
                'compressed_filename' => $compressedFilename,
                'compressed_path' => $compressedStoragePath,
                'original_size' => $originalSize,
                'compressed_size' => $compressedSize,
                'compression_ratio' => $compressionRatio,
                'error_message' => $usedOriginalAsBestResult
                    ? 'Ukuran file asli sudah lebih optimal. Sistem menyimpan versi asli sebagai hasil terbaik.'
                    : null,
                'completed_at' => now(),
            ]);

            return true;
        } catch (\Exception $exception) {
            $job->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
                'completed_at' => now(),
            ]);

            return false;
        } finally {
            if ($compressedPath && is_file($compressedPath)) {
                @unlink($compressedPath);
            }
        }
    }

    private function compressUsingGhostScript(string $inputPath, string $outputFilename, string $level = 'medium'): string
    {
        $tempDir = Storage::disk(self::STORAGE_DISK)->path(self::TEMP_PATH);
        File::ensureDirectoryExists($tempDir);

        $outputPath = $tempDir . '/' . $outputFilename;

        $quality = match($level) {
            'high' => 'ebook',      // Lowest quality, smallest size
            'medium' => 'screen',   // Medium quality
            'low' => 'prepress',    // Highest quality
            default => 'screen',
        };

        $gsPath = $this->getGhostScriptPath();

        $command = sprintf(
            '%s -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/%s -dNOPAUSE -dQUIET -dBATCH -sOutputFile=%s %s',
            $this->escapeShellPath($gsPath),
            $quality,
            $this->escapeShellPath($outputPath),
            $this->escapeShellPath($inputPath)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new RuntimeException(
                'Ghostscript compression failed with code ' . $returnCode . ': ' . implode("\n", $output)
            );
        }

        return $outputPath;
    }

    public function downloadCompressed(CompressPdfJob $job): mixed
    {
        if (!$job->isCompleted() || !$job->compressed_path) {
            throw new RuntimeException('Compressed file is not available');
        }

        return Storage::disk(self::STORAGE_DISK)->download($job->compressed_path, $job->compressed_filename);
    }

    public function deleteJob(CompressPdfJob $job): bool
    {
        try {
            if ($job->compressed_path && Storage::disk(self::STORAGE_DISK)->exists($job->compressed_path)) {
                Storage::disk(self::STORAGE_DISK)->delete($job->compressed_path);
            }

            if ($job->original_path && Storage::disk(self::STORAGE_DISK)->exists($job->original_path)) {
                Storage::disk(self::STORAGE_DISK)->delete($job->original_path);
            }

            $job->delete();

            return true;
        } catch (\Exception $exception) {
            throw new RuntimeException('Failed to delete job: ' . $exception->getMessage());
        }
    }

    public function cleanupOldFiles(int $daysOld = 7): int
    {
        $cutoffDate = now()->subDays($daysOld);
        $jobs = CompressPdfJob::where('created_at', '<', $cutoffDate)->get();

        $deletedCount = 0;
        foreach ($jobs as $job) {
            if ($this->deleteJob($job)) {
                $deletedCount++;
            }
        }

        return $deletedCount;
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
            $gsPath = shell_exec('where.exe gswin64c 2>NUL');
            if ($gsPath) {
                $paths = preg_split('/\r\n|\r|\n/', trim($gsPath)) ?: [];
                if (!empty($paths[0])) {
                    return trim($paths[0]);
                }
            }

            $gsPath = shell_exec('where.exe gswin32c 2>NUL');
            if ($gsPath) {
                $paths = preg_split('/\r\n|\r|\n/', trim($gsPath)) ?: [];
                if (!empty($paths[0])) {
                    return trim($paths[0]);
                }
            }
        }

        $gsPath = shell_exec('which gs 2>/dev/null');
        if ($gsPath) {
            return trim($gsPath);
        }

        $unixPaths = [
            '/usr/bin/gs',
            '/usr/local/bin/gs',
            '/opt/local/bin/gs',
        ];

        foreach ($unixPaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        throw new RuntimeException(
            'GhostScript tidak ditemukan di sistem. ' .
            'Silakan install GhostScript dari https://www.ghostscriptplus.com/ atau gunakan package manager Anda.'
        );
    }

    private function ensureStorageDirectoriesExist(): void
    {
        $disk = Storage::disk(self::STORAGE_DISK);

        File::ensureDirectoryExists($disk->path(self::COMPRESSED_PATH));
        File::ensureDirectoryExists($disk->path(self::TEMP_PATH));
        File::ensureDirectoryExists($disk->path('pdf-uploads'));
    }

    private function escapeShellPath(string $path): string
    {
        return escapeshellarg($path);
    }
}
