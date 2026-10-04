<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Builds CSV downloads that open correctly in Excel and can't be abused.
 *
 *  - UTF-8 BOM so Excel doesn't garble non-ASCII text.
 *  - CRLF line ends and RFC-4180 quoting via fputcsv.
 *  - Formula-injection guard: a text cell that starts with = + - @ (or a tab/CR)
 *    is prefixed with an apostrophe so a spreadsheet will never execute it.
 *    Real numbers are left untouched, so sums and charts still work.
 */
final class CsvExport
{
    /** @param array<int, array<int, mixed>> $rows */
    public static function download(string $filename, array $rows): StreamedResponse
    {
        $filename = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename) ?: 'export.csv';

        return new StreamedResponse(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            foreach ($rows as $row) {
                fputcsv($out, array_map([self::class, 'cell'], $row), ',', '"', '\\', "\r\n");
            }
            fclose($out);
        }, 200, [
            'Content-Type'           => 'text/csv; charset=UTF-8',
            'Content-Disposition'    => 'attachment; filename="' . $filename . '"',
            'Cache-Control'          => 'no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public static function cell(mixed $v): string|int|float
    {
        if ($v === null) return '';
        if (is_bool($v)) return $v ? 'Yes' : 'No';
        if (is_int($v) || is_float($v)) return $v;

        $s = (string) $v;
        if ($s !== '' && preg_match('/^[=+\-@\t\r]/', $s)) {
            return "'" . $s;
        }
        return $s;
    }
}