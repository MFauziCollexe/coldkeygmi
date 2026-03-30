<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class LogModuleAction
{
    private const MAX_LOG_ITEMS = 20;
    private const MAX_LOG_STRING = 500;
    private const MAX_LOG_JSON_BYTES = 60000;

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (!$this->shouldLog($request, $response)) {
            return $response;
        }

        [$action, $buttonLabel] = $this->resolveAction($request);
        if ($action === null) {
            return $response;
        }

        $route = $request->route();
        $tableName = $this->resolveTableName($request);
        $recordId = $this->resolveRecordId($route?->parameters() ?? []);

        $payload = $request->except([
            '_token',
            '_method',
            'password',
            'password_confirmation',
            'current_password',
            'new_password',
            'new_password_confirmation',
        ]);

        if ($request->boolean('export')) {
            $payload = array_merge($payload, ['export' => true]);
        }

        $payload = $this->stripHeavyPayloadKeys($payload);
        $normalizedPayload = $this->normalizePayload($payload);
        $safePayload = $this->shrinkPayloadForLog($normalizedPayload);

        try {
            ActivityLog::create([
                'table_name' => $tableName,
                'record_id' => $recordId,
                'action' => $action,
                'old_values' => null,
                'new_values' => $safePayload,
                'user_id' => (string) optional($request->user())->id,
                'user_email' => optional($request->user())->email,
                'ip_address' => $request->ip(),
                'created_date' => now(),
                'description' => sprintf(
                    '%s action on %s (%s) [%s]',
                    ucfirst($buttonLabel),
                    $request->path(),
                    $request->method(),
                    (string) ($route?->getName() ?? 'unnamed')
                ),
            ]);
        } catch (\Throwable $e) {
            // Activity logging must never break the primary request flow.
            Log::warning('Skip activity log due to logging error', [
                'path' => $request->path(),
                'method' => $request->method(),
                'error' => $e->getMessage(),
            ]);
        }

        return $response;
    }

    private function shouldLog(Request $request, $response): bool
    {
        if (!$request->user()) {
            return false;
        }

        if ((string) ($request->route()?->getName() ?? '') === 'control.logs.clear') {
            return false;
        }

        if ((int) $response->getStatusCode() >= 400) {
            return false;
        }

        if ($request->boolean('export')) {
            return true;
        }

        return in_array(strtoupper($request->method()), ['POST', 'PUT', 'PATCH', 'DELETE'], true);
    }

    private function resolveAction(Request $request): array
    {
        $method = strtoupper($request->method());
        $routeName = strtolower((string) ($request->route()?->getName() ?? ''));
        $path = strtolower((string) $request->path());

        $haystack = trim($routeName . ' ' . $path);

        if ($request->boolean('export') || $this->containsAny($haystack, ['export'])) {
            return ['export', 'export'];
        }
        if ($this->containsAny($haystack, ['approve'])) {
            return ['approve', 'approve'];
        }
        if ($this->containsAny($haystack, ['reject'])) {
            return ['reject', 'reject'];
        }
        if ($this->containsAny($haystack, ['correction', 'koreksi'])) {
            return ['koreksi', 'koreksi'];
        }
        if ($this->containsAny($haystack, ['insert'])) {
            return ['insert', 'insert'];
        }

        if ($method === 'DELETE' || $this->containsAny($haystack, ['destroy', 'delete', 'clear'])) {
            return ['delete', 'delete'];
        }
        if (in_array($method, ['PUT', 'PATCH'], true) || $this->containsAny($haystack, ['update'])) {
            return ['update', 'update'];
        }

        if ($method === 'POST') {
            if ($this->containsAny($haystack, ['store', 'create'])) {
                return ['create', 'create'];
            }
            if ($this->containsAny($haystack, ['upload', 'confirm-save'])) {
                return ['insert', 'insert'];
            }
            return ['submit', 'submit'];
        }

        return [null, null];
    }

    private function resolveTableName(Request $request): string
    {
        $routeName = (string) ($request->route()?->getName() ?? '');
        if ($routeName !== '') {
            $parts = explode('.', $routeName);
            $first = $parts[0] ?? '';
            $second = $parts[1] ?? '';

            // For GMIVP routes (gmi-visitor-permit.*), use module segment (visitor-form / exit-permit)
            // so logs are more specific than the parent namespace.
            if (in_array($first, ['gmi-visitor-permit', 'gmi_visitor_permit'], true) && $second !== '') {
                return str_replace('-', '_', $second);
            }

            return str_replace(['.', '-'], '_', $first);
        }

        $firstSegment = (string) Arr::first(explode('/', trim($request->path(), '/')));
        if ($firstSegment !== '') {
            return str_replace('-', '_', $firstSegment);
        }

        return 'module_action';
    }

    private function resolveRecordId(array $parameters): ?int
    {
        foreach ($parameters as $value) {
            if (is_object($value) && method_exists($value, 'getKey')) {
                return (int) $value->getKey();
            }
            if (is_numeric($value)) {
                return (int) $value;
            }
        }

        return null;
    }

    private function normalizePayload(array $payload): array
    {
        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $payload[$key] = $this->normalizePayload($value);
                continue;
            }

            if (is_object($value)) {
                if ($value instanceof \Illuminate\Http\UploadedFile) {
                    $payload[$key] = [
                        'filename' => $value->getClientOriginalName(),
                        'size' => $value->getSize(),
                    ];
                } else {
                    $payload[$key] = (string) $value;
                }
                continue;
            }

            if (is_string($value) && mb_strlen($value) > self::MAX_LOG_STRING) {
                $payload[$key] = mb_substr($value, 0, self::MAX_LOG_STRING) . '...[truncated]';
            }
        }

        return $payload;
    }

    private function shrinkPayloadForLog(array $payload): array
    {
        $reduced = $this->reduceLargeArrays($payload);

        $encoded = json_encode($reduced);
        if ($encoded !== false && strlen($encoded) <= self::MAX_LOG_JSON_BYTES) {
            return $reduced;
        }

        return [
            '_truncated' => true,
            '_reason' => 'payload_too_large',
            '_keys' => array_keys($payload),
            '_meta' => [
                'max_bytes' => self::MAX_LOG_JSON_BYTES,
                'approx_original_bytes' => $encoded !== false ? strlen($encoded) : null,
            ],
        ];
    }

    private function reduceLargeArrays(array $payload): array
    {
        foreach ($payload as $key => $value) {
            if (!is_array($value)) {
                continue;
            }

            $count = count($value);
            if ($count > self::MAX_LOG_ITEMS) {
                $payload[$key] = [
                    '_summary' => 'array_reduced',
                    '_count' => $count,
                    '_sample' => array_slice($value, 0, 3),
                ];
                continue;
            }

            $payload[$key] = $this->reduceLargeArrays($value);
        }

        return $payload;
    }

    private function stripHeavyPayloadKeys(array $payload): array
    {
        $heavyKeys = ['rows', 'edited_rows', 'preview_rows', 'matrix', 'data'];

        foreach ($heavyKeys as $key) {
            if (!array_key_exists($key, $payload)) {
                continue;
            }

            $value = $payload[$key];
            if (is_array($value)) {
                $payload[$key] = [
                    '_summary' => 'omitted_large_payload',
                    '_count' => count($value),
                ];
            } else {
                $payload[$key] = [
                    '_summary' => 'omitted_large_payload',
                    '_type' => gettype($value),
                ];
            }
        }

        return $payload;
    }

    private function containsAny(string $text, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($text, strtolower($needle))) {
                return true;
            }
        }

        return false;
    }
}
