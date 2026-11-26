<?php

namespace App\Console\Commands;

use App\Models\ChecklistHeader;
use App\Models\ChecklistMedia;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class MigrateChecklistMedia extends Command
{
    protected $signature = 'checklist:migrate-media';

    protected $description = 'Copy checklist files to configured storage and migrate JSON media references';

    public function handle(): int
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('checklist_media')) {
            $this->error('Run database migrations before migrating checklist media.');

            return self::FAILURE;
        }

        $copiedFiles = $this->copyLegacyChecklistFiles();
        $migrated = 0;
        ChecklistHeader::query()
            ->with(['states' => fn ($query) => $query->orderByDesc('version_no')])
            ->chunkById(100, function ($headers) use (&$migrated) {
                foreach ($headers as $header) {
                    $entry = $header->payload_summary_json;
                    if (!is_array($entry) || $entry === []) {
                        $entry = $header->states->first()?->state_json;
                    }
                    if (!is_array($entry) || $entry === []) {
                        continue;
                    }

                    $media = $this->extractMedia($entry);
                    if ($media === []) {
                        continue;
                    }

                    DB::transaction(function () use ($header, $entry, $media) {
                        foreach ($media as $item) {
                            [$path, $url] = $this->resolveStoredImage($item);
                            if ($path === '') {
                                continue;
                            }
                            ChecklistMedia::query()->updateOrCreate(
                                [
                                    'checklist_header_id' => $header->id,
                                    'media_type' => $item['media_type'],
                                    'scope_key' => $item['scope_key'],
                                    'sort_order' => $item['sort_order'],
                                ],
                                [
                                    'path' => $path,
                                    'url' => $url,
                                    'original_name' => Str::limit($item['original_name'], 255, ''),
                                ],
                            );
                        }

                        $header->payload_summary_json = $this->stripMedia($entry);
                        $header->save();
                        foreach ($header->states as $state) {
                            $stateEntry = $state->state_json;
                            if (is_array($stateEntry)) {
                                $state->state_json = $this->stripMedia($stateEntry);
                                $state->save();
                            }
                        }
                    });
                    $migrated++;
                }
            });

        ChecklistMedia::query()->chunkById(100, function ($items) {
            foreach ($items as $item) {
                $this->ensureStoredOnConfiguredDisk($item->path);
                $item->url = Storage::disk('checklist_media')->url($item->path);
                $item->save();
            }
        });

        $this->info("Copied {$copiedFiles} checklist files and migrated media references for {$migrated} checklist entries.");

        return self::SUCCESS;
    }

    private function copyLegacyChecklistFiles(): int
    {
        $paths = Storage::disk('public')->allFiles('checklist');
        $legacyPublicRoot = public_path('storage/checklist');

        if (File::isDirectory($legacyPublicRoot)) {
            foreach (File::allFiles($legacyPublicRoot) as $file) {
                $relativePath = str_replace('\\', '/', $file->getRelativePathname());
                $paths[] = 'checklist/' . $relativePath;
            }
        }

        $paths = array_values(array_unique($paths));
        $disk = Storage::disk('checklist_media');
        $copied = 0;

        foreach ($paths as $path) {
            if ($disk->exists($path)) {
                continue;
            }

            $this->ensureStoredOnConfiguredDisk($path);
            $copied++;
        }

        return $copied;
    }

    private function extractMedia(array $entry): array
    {
        $form = is_array($entry['form'] ?? null) ? $entry['form'] : [];
        $items = [];
        $paths = (array) ($form['area_photo_paths'] ?? []);
        $urls = (array) ($form['area_photo_urls'] ?? []);
        $names = (array) ($form['area_photo_names'] ?? []);

        foreach (array_unique(array_merge(array_keys($paths), array_keys($urls), array_keys($names))) as $scope) {
            $pathBucket = $this->bucket($paths[$scope] ?? []);
            $urlBucket = $this->bucket($urls[$scope] ?? []);
            $nameBucket = $this->bucket($names[$scope] ?? []);
            $count = max(count($pathBucket), count($urlBucket), count($nameBucket));
            for ($index = 0; $index < $count; $index++) {
                $items[] = [
                    'media_type' => 'area',
                    'scope_key' => (string) $scope,
                    'path' => (string) ($pathBucket[$index] ?? ''),
                    'url' => (string) ($urlBucket[$index] ?? ''),
                    'original_name' => (string) ($nameBucket[$index] ?? ''),
                    'sort_order' => $index,
                ];
            }
        }

        foreach ((array) ($form['rows'] ?? []) as $row) {
            if (!is_array($row) || !isset($row['day'])) {
                continue;
            }
            $path = (string) ($row['collector_photo_path'] ?? '');
            $url = (string) ($row['collector_photo_url'] ?? $row['collector_photo_preview'] ?? '');
            $name = (string) ($row['collector_photo_name'] ?? '');
            if ($path !== '' || $url !== '' || $name !== '') {
                $items[] = [
                    'media_type' => 'waste_transport',
                    'scope_key' => (string) ((int) $row['day']),
                    'path' => $path,
                    'url' => $url,
                    'original_name' => $name,
                    'sort_order' => 0,
                ];
            }
        }

        return $items;
    }

    private function bucket(mixed $value): array
    {
        if (is_array($value)) {
            return array_values($value);
        }

        return trim((string) $value) === '' ? [] : [(string) $value];
    }

    private function resolveStoredImage(array $item): array
    {
        $path = trim($item['path']);
        $url = trim($item['url']);
        $dataUri = Str::startsWith($path, 'data:image/')
            ? $path
            : (Str::startsWith($url, 'data:image/') ? $url : '');

        if ($dataUri !== '') {
            if (!preg_match('/^data:image\/(jpeg|jpg|png|gif|webp);base64,([A-Za-z0-9+\/=\r\n]+)$/', $dataUri, $matches)) {
                throw new RuntimeException('Encountered an invalid checklist image data URI.');
            }
            $contents = base64_decode($matches[2], true);
            if ($contents === false || strlen($contents) > 8 * 1024 * 1024) {
                throw new RuntimeException('A legacy checklist image exceeds the 8 MB migration limit.');
            }
            $extension = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
            $directory = 'checklist/legacy/' . now()->format('Y/m');
            $path = $directory . '/' . Str::uuid() . '.' . $extension;
            if (!Storage::disk('checklist_media')->put($path, $contents)) {
                throw new RuntimeException('Failed to write a legacy checklist image to public storage.');
            }
            $url = Storage::disk('checklist_media')->url($path);
        } elseif ($path === '') {
            $urlPath = parse_url($url, PHP_URL_PATH);
            foreach (['/gmiic/checklist/media/', '/storage/'] as $prefix) {
                if (is_string($urlPath) && Str::startsWith($urlPath, $prefix)) {
                    $path = substr($urlPath, strlen($prefix));
                    break;
                }
            }
        }

        if ($path !== '') {
            $this->ensureStoredOnConfiguredDisk($path);
            $url = Storage::disk('checklist_media')->url($path);
        }

        return [$path, $url];
    }

    private function ensureStoredOnConfiguredDisk(string $path): void
    {
        if (
            !Str::startsWith($path, 'checklist/')
            || Str::contains($path, '..')
        ) {
            throw new RuntimeException("Invalid checklist image path: {$path}");
        }

        $disk = Storage::disk('checklist_media');
        if ($disk->exists($path)) {
            return;
        }

        $legacyDisk = Storage::disk('public');
        $legacyPublicPath = public_path('storage/' . $path);
        $legacyExistsOnDisk = $legacyDisk->exists($path);
        if (!$legacyExistsOnDisk && !File::isFile($legacyPublicPath)) {
            throw new RuntimeException("Checklist image file is missing: {$path}");
        }

        $stream = $legacyExistsOnDisk
            ? $legacyDisk->readStream($path)
            : fopen($legacyPublicPath, 'rb');
        if (!is_resource($stream)) {
            throw new RuntimeException("Failed to read checklist image file: {$path}");
        }

        try {
            if (!$disk->writeStream($path, $stream)) {
                throw new RuntimeException("Failed to copy checklist image file: {$path}");
            }
        } finally {
            fclose($stream);
        }
    }

    private function stripMedia(array $entry): array
    {
        if (!is_array($entry['form'] ?? null)) {
            return $entry;
        }

        unset(
            $entry['form']['area_photo_paths'],
            $entry['form']['area_photo_urls'],
            $entry['form']['area_photo_names']
        );

        foreach ((array) ($entry['form']['rows'] ?? []) as $index => $row) {
            if (!is_array($row)) {
                continue;
            }
            unset(
                $entry['form']['rows'][$index]['collector_photo_path'],
                $entry['form']['rows'][$index]['collector_photo_url'],
                $entry['form']['rows'][$index]['collector_photo_name'],
                $entry['form']['rows'][$index]['collector_photo_preview']
            );
        }

        return $entry;
    }
}
