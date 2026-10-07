<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Receives CSP violation reports (report-uri) and writes ONE compact log line each.
 *
 * Public + stateless + throttled, so it must be conservative:
 *  - body capped at 4 KB, anything unparsable is dropped silently,
 *  - only a fixed list of fields is logged, each truncated,
 *  - secrets never reach the log: the public live-link token is part of the page
 *    URL, so paths are scrubbed, and blocked URLs are reduced to scheme+host.
 */
class CspReportController extends Controller
{
    public function store(Request $request): Response
    {
        $raw = substr((string) $request->getContent(), 0, 4096);
        $data = json_decode($raw, true);
        $r = is_array($data) ? ($data['csp-report'] ?? null) : null;

        if (is_array($r)) {
            Log::warning('CSP violation', [
                'directive' => $this->clip($r['effective-directive'] ?? $r['violated-directive'] ?? ''),
                'blocked'   => $this->origin((string) ($r['blocked-uri'] ?? '')),
                'page'      => $this->path((string) ($r['document-uri'] ?? '')),
                'source'    => $this->path((string) ($r['source-file'] ?? '')),
                'line'      => (int) ($r['line-number'] ?? 0),
            ]);
        }

        return response('', 204);
    }

    private function clip(string $v): string
    {
        return substr(preg_replace('/[^\x20-\x7E]/', '', $v), 0, 80);
    }

    /** scheme://host only; keywords like "inline" / "eval" pass through. */
    private function origin(string $uri): string
    {
        $parts = parse_url($uri);
        if (! empty($parts['scheme']) && ! empty($parts['host'])) {
            return $this->clip($parts['scheme'] . '://' . $parts['host']);
        }

        return $this->clip($uri);
    }

    /** Path only (no query/fragment), with the live-link token removed. */
    private function path(string $uri): string
    {
        $path = (string) parse_url($uri, PHP_URL_PATH);
        $path = preg_replace('#/live/[^/]+#', '/live/{token}', $path);

        return $this->clip($path);
    }
}