<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

class SecuritySqlInjectionGuardTest extends TestCase
{
    public function test_raw_sql_calls_do_not_use_string_interpolation_or_concatenation(): void
    {
        $root = realpath(__DIR__ . '/../../app');
        $this->assertNotFalse($root, 'Unable to locate app directory.');

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
        $phpFiles = new RegexIterator($iterator, '/^.+\\.php$/i', RegexIterator::GET_MATCH);

        $patterns = [
            // Interpolated variables inside double-quoted SQL strings.
            '/\\b(whereRaw|orWhereRaw|havingRaw|selectRaw|orderByRaw)\\s*\\(\\s*\"[^\"]*\\$/',
            '/\\bDB::(select|statement|unprepared)\\s*\\(\\s*\"[^\"]*\\$/',

            // SQL string concatenation (very often used to inject identifiers/values).
            '/\\b(whereRaw|orWhereRaw|havingRaw|selectRaw|orderByRaw)\\s*\\(\\s*\\\'[^\\\']*\\\'\\s*\\./',
            '/\\bDB::(select|statement|unprepared)\\s*\\(\\s*\\\'[^\\\']*\\\'\\s*\\./',
        ];

        $violations = [];

        foreach ($phpFiles as $match) {
            $filePath = $match[0];
            $contents = file_get_contents($filePath);
            if ($contents === false) {
                continue;
            }

            $lines = preg_split("/\\r\\n|\\n|\\r/", $contents) ?: [];
            foreach ($lines as $index => $line) {
                foreach ($patterns as $pattern) {
                    if (preg_match($pattern, $line) === 1) {
                        $violations[] = $filePath . ':' . ($index + 1) . ': ' . trim($line);
                        break;
                    }
                }
            }
        }

        $this->assertSame([], $violations, "Potential SQL injection risk (raw SQL interpolation/concat) found:\n" . implode("\n", $violations));
    }
}

