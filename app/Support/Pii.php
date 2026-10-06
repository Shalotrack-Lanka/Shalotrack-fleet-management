<?php

namespace App\Support;

/**
 * Keeps personal data out of logs.
 *
 * Logs are copied to CloudWatch, read by support staff and kept for months — a much
 * wider audience than the customer expected. Anything that could identify a person
 * (phone, e-mail, national ID, free text echoed back by the API) is masked before it
 * is written.
 */
final class Pii
{
    /** "+94712345678" -> "+94*****678" */
    public static function phone(?string $phone): string
    {
        $d = (string) $phone;
        if ($d === '') {
            return '';
        }
        $keep = strlen($d) > 6 ? substr($d, -3) : '';
        return substr($d, 0, min(3, strlen($d))) . str_repeat('*', max(0, strlen($d) - 3 - strlen($keep))) . $keep;
    }

    /** Stable, non-reversible short id for correlating log lines without storing the raw uid. */
    public static function id(?string $id): string
    {
        return $id ? substr(hash('sha256', $id), 0, 10) : '';
    }

    /**
     * Reduce an API error body to what is needed to debug it: status-like fields only,
     * truncated. Validation errors often echo the submitted values (e-mail, NIC, address),
     * so the full body must never be logged.
     *
     * @param mixed $body decoded JSON (or null)
     */
    public static function apiBody(mixed $body): array
    {
        if (!is_array($body)) {
            return [];
        }
        $out = [];
        foreach (['code', 'errorCode', 'error', 'message', 'title', 'status'] as $k) {
            if (isset($body[$k]) && is_scalar($body[$k])) {
                $out[$k] = mb_substr((string) $body[$k], 0, 200);
            }
        }
        return $out;
    }
}