<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class BeritaAcaraLetterhead
{
    public static function getDataUri(): ?string
    {
        $docxPath = (string) config('berita_acara.letterhead_docx_path');
        $sourceSignature = self::sourceSignature($docxPath);

        $existing = self::findExistingImageInPublicDisk();
        if ($existing) {
            if ($sourceSignature === null) {
                return self::toDataUriFromPublicDisk($existing['path'], $existing['mime']);
            }

            $meta = self::readMeta();
            if (is_array($meta) && ($meta['source_signature'] ?? null) === $sourceSignature) {
                return self::toDataUriFromPublicDisk($existing['path'], $existing['mime']);
            }
        }

        if ($sourceSignature === null) {
            return null;
        }

        if (!class_exists(ZipArchive::class)) {
            Log::warning('Berita acara letterhead skipped because ZipArchive is not available.');
            return null;
        }

        $extracted = self::extractBestImageFromDocx($docxPath);
        if (!$extracted) {
            return null;
        }

        self::deleteExistingImages();
        $targetPath = 'letterhead/ba_kop.' . $extracted['ext'];
        Storage::disk('public')->put($targetPath, $extracted['bytes']);
        self::writeMeta([
            'source_signature' => $sourceSignature,
            'ext' => $extracted['ext'],
            'updated_at' => now()->toDateTimeString(),
        ]);

        return self::toDataUri($extracted['mime'], $extracted['bytes']);
    }

    private static function sourceSignature(string $docxPath): ?string
    {
        if ($docxPath === '' || !is_file($docxPath)) {
            return null;
        }

        $mtime = @filemtime($docxPath);
        $size = @filesize($docxPath);

        if ($mtime === false || $size === false) {
            return null;
        }

        return (string) $mtime . '|' . (string) $size;
    }

    private static function findExistingImageInPublicDisk(): ?array
    {
        $disk = Storage::disk('public');
        foreach (['png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg'] as $ext => $mime) {
            $path = 'letterhead/ba_kop.' . $ext;
            if ($disk->exists($path)) {
                return ['path' => $path, 'mime' => $mime];
            }
        }
        return null;
    }

    private static function deleteExistingImages(): void
    {
        $disk = Storage::disk('public');
        foreach (['png', 'jpg', 'jpeg'] as $ext) {
            $path = 'letterhead/ba_kop.' . $ext;
            if ($disk->exists($path)) {
                $disk->delete($path);
            }
        }
    }

    private static function readMeta(): ?array
    {
        $disk = Storage::disk('public');
        $path = 'letterhead/ba_kop.meta.json';
        if (!$disk->exists($path)) {
            return null;
        }

        try {
            $json = $disk->get($path);
            $data = json_decode((string) $json, true);
            return is_array($data) ? $data : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private static function writeMeta(array $payload): void
    {
        Storage::disk('public')->put('letterhead/ba_kop.meta.json', json_encode($payload, JSON_UNESCAPED_SLASHES));
    }

    private static function toDataUriFromPublicDisk(string $path, string $mime): ?string
    {
        try {
            $bytes = Storage::disk('public')->get($path);
            return self::toDataUri($mime, $bytes);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private static function toDataUri(string $mime, string $bytes): string
    {
        return 'data:' . $mime . ';base64,' . base64_encode($bytes);
    }

    /**
     * Extract the most likely "kop surat" image from a .docx.
     * Heuristic: pick the largest image in word/media.
     */
    private static function extractBestImageFromDocx(string $docxPath): ?array
    {
        if (!class_exists(ZipArchive::class)) {
            return null;
        }

        $zip = new ZipArchive();
        if ($zip->open($docxPath) !== true) {
            return null;
        }

        $best = null;
        $count = $zip->numFiles;
        for ($i = 0; $i < $count; $i++) {
            $stat = $zip->statIndex($i);
            if (!$stat || empty($stat['name'])) {
                continue;
            }
            $name = (string) $stat['name'];
            if (!str_starts_with($name, 'word/media/')) {
                continue;
            }
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($ext, ['png', 'jpg', 'jpeg'], true)) {
                continue;
            }
            $size = (int) ($stat['size'] ?? 0);
            if ($best === null || $size > $best['size']) {
                $best = ['name' => $name, 'ext' => $ext, 'size' => $size];
            }
        }

        if (!$best) {
            $zip->close();
            return null;
        }

        $bytes = $zip->getFromName($best['name']);
        $zip->close();

        if (!is_string($bytes) || $bytes === '') {
            return null;
        }

        $mime = $best['ext'] === 'png' ? 'image/png' : 'image/jpeg';
        return [
            'ext' => $best['ext'],
            'mime' => $mime,
            'bytes' => $bytes,
        ];
    }
}
