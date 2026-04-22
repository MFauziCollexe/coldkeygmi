<?php

namespace App\Services;

use App\Models\CompressPdfJob;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class PdfCompressionService
{
    private const STORAGE_DISK = 'local';
    private const COMPRESSED_PATH = 'compressed-pdfs';
    private const TEMP_PATH = 'temp-pdf-compress';

    public function compress(CompressPdfJob $job): bool
    {
        try {
            $job->update([
                'status' => 'processing',
                'started_at' => now(),
            ]);

            // Get original file path
            $originalPath = storage_path('app/' . $job->original_path);
            
            if (!file_exists($originalPath)) {
                throw new RuntimeException('Original file not found: ' . $originalPath);
            }

            // Generate output filename
            $filename = pathinfo($job->original_filename, PATHINFO_FILENAME);
            $timestamp = now()->format('YmdHis');
            $compressedFilename = "{$filename}_compressed_{$timestamp}.pdf";
            
            // Create compressed PDF
            $compressedPath = $this->compressUsingGhostScript(
                $originalPath,
                $compressedFilename,
                $job->compression_level
            );

            if (!file_exists($compressedPath)) {
                throw new RuntimeException('Failed to compress PDF');
            }

            // Get file sizes
            $originalSize = filesize($originalPath);
            $compressedSize = filesize($compressedPath);
            
            // Calculate compression ratio
            $compressionRatio = $originalSize > 0 
                ? round(($originalSize - $compressedSize) / $originalSize * 100, 2)
                : 0;

            // Store compressed file in storage
            $compressedStoragePath = self::COMPRESSED_PATH . '/' . $compressedFilename;
            $compressedContent = file_get_contents($compressedPath);
            Storage::disk(self::STORAGE_DISK)->put($compressedStoragePath, $compressedContent);

            // Update job
            $job->update([
                'status' => 'completed',
                'compressed_filename' => $compressedFilename,
                'compressed_path' => $compressedStoragePath,
                'original_size' => $originalSize,
                'compressed_size' => $compressedSize,
                'compression_ratio' => $compressionRatio,
                'completed_at' => now(),
            ]);

            // Clean up temp file
            if (file_exists($compressedPath)) {
                unlink($compressedPath);
            }

            return true;
        } catch (\Exception $exception) {
            $job->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
                'completed_at' => now(),
            ]);

            return false;
        }
    }

    private function compressUsingGhostScript(string $inputPath, string $outputFilename, string $level = 'medium'): string
    {
        $tempDir = storage_path('app/' . self::TEMP_PATH);
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $outputPath = $tempDir . '/' . $outputFilename;

        // Set quality parameters based on compression level
        $quality = match($level) {
            'high' => 'ebook',      // Lowest quality, smallest size
            'medium' => 'screen',   // Medium quality
            'low' => 'prepress',    // Highest quality
            default => 'screen',
        };

        // Determine Ghostscript executable path
        $gsPath = $this->getGhostScriptPath();

        // Build Ghostscript command
        $command = sprintf(
            '%s -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/%s -dNOPAUSE -dQUIET -dBATCH -sOutputFile=%s %s',
            escapeshellarg($gsPath),
            escapeshellarg($quality),
            escapeshellarg($outputPath),
            escapeshellarg($inputPath)
        );

        // Execute compression
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
            // Delete compressed file from storage
            if ($job->compressed_path && Storage::disk(self::STORAGE_DISK)->exists($job->compressed_path)) {
                Storage::disk(self::STORAGE_DISK)->delete($job->compressed_path);
            }

            // Delete original file from storage
            if ($job->original_path && Storage::disk(self::STORAGE_DISK)->exists($job->original_path)) {
                Storage::disk(self::STORAGE_DISK)->delete($job->original_path);
            }

            // Delete record
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
        // Windows paths
        $windowsPaths = [
            'C:\\Program Files\\gs\\gs10.07.0\\bin\\gswin64c.exe',
            'C:\\Program Files\\gs\\gs10.02.0\\bin\\gswin64c.exe',
            'C:\\Program Files\\gs\\gs10.01.0\\bin\\gswin64c.exe',
            'C:\\Program Files (x86)\\gs\\gs10.07.0\\bin\\gswin32c.exe',
            'C:\\Program Files (x86)\\gs\\gs10.02.0\\bin\\gswin32c.exe',
        ];

        // Check Windows paths first
        foreach ($windowsPaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // Check if gs is in PATH (Linux/Mac)
        $gsPath = shell_exec('which gs 2>/dev/null');
        if ($gsPath) {
            return trim($gsPath);
        }

        // Fallback to common Linux/Mac paths
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
