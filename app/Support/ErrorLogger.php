<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

/**
 * One place that turns a Throwable into (a) a log entry in the `app_errors` channel and
 * (b) the safe view data for the error page — both carry the same reference ID, so what a
 * customer reads out on the phone finds the exact log line in seconds.
 *
 * Nothing here may ever throw: it runs inside the exception handler, and an error while
 * handling an error would replace the friendly page with a blank 500 (this is exactly how the
 * pipeline broke once before, when the log channel was missing).
 */
final class ErrorLogger
{
    /** Exception text is logged/shown only after secrets and personal data are masked. */
    public static function scrub(string $text): string
    {
        $text = preg_replace('/Bearer\s+[A-Za-z0-9\-._~+\/]+=*/i', 'Bearer [redacted]', $text) ?? $text;
        $text = preg_replace('/eyJ[A-Za-z0-9_\-]+\.[A-Za-z0-9_\-]+\.[A-Za-z0-9_\-]*/', '[jwt]', $text) ?? $text;
        // Long opaque strings are tokens / share links / ids (Laravel's own 404 text echoes the URL).
        $text = preg_replace('/[A-Za-z0-9_\-]{24,}/', '[id]', $text) ?? $text;
        $text = preg_replace('/[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}/', '[email]', $text) ?? $text;
        $text = preg_replace('/\+?\d[\d\s\-]{8,}\d/', '[number]', $text) ?? $text;

        return mb_substr($text, 0, 500);
    }

    /** Share links and ids live in the path (/live/{token}); never write them to a log. */
    public static function safePath(?string $path): string
    {
        return '/' . ltrim((string) preg_replace('#/[A-Za-z0-9_\-]{20,}#', '/{id}', (string) $path), '/');
    }

    /** Classify the real cause (HTTP wrappers such as the 419 hide the original exception). */
    public static function classify(\Throwable $e): array
    {
        $own = ErrorClassifier::classify($e);

        if ($e->getPrevious() && str_starts_with($own['category'], 'HTTP')) {
            $inner = ErrorClassifier::classify($e->getPrevious());
            if ($inner['category'] !== 'Unclassified') {
                return $inner;
            }
        }

        return $own;
    }

    public static function status(\Throwable $e): int
    {
        if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
            return $e->getStatusCode();
        }

        return $e instanceof \Symfony\Component\Routing\Exception\RouteNotFoundException ? 404 : 500;
    }

    /** Writes the entry and returns the reference ID it was written under. */
    public static function log(\Throwable $e, string $level = 'error'): string
    {
        $ref = app('error.reference_id');

        try {
            $c       = self::classify($e);
            $request = request();

            Log::channel('app_errors')->{$level}("[{$c['category']}] " . self::scrub($e->getMessage()), [
                'reference_id' => $ref,
                'category'     => $c['category'],
                'summary'      => $c['summary'],
                'status'       => self::status($e),
                'exception'    => get_class($e),
                'file'         => $e->getFile() . ':' . $e->getLine(),
                'route'        => $request?->route()?->getName(),
                'method'       => $request?->method(),
                'path'         => self::safePath($request?->path()),
                'user'         => Pii::id($request && $request->hasSession() ? $request->session()->get('firebase_uid') : null),
                'ip'           => $request?->ip(),
                'user_agent'   => mb_substr((string) $request?->userAgent(), 0, 200),
            ]);
        } catch (\Throwable) {
            // Logging must never break the error page.
        }

        return $ref;
    }

    /**
     * Variables for resources/views/errors/*. The category is real and always shown; the
     * cause text, raw message and file:line are included ONLY when ERROR_PAGE_DETAILS=true
     * (staging / support sessions) — on a public customer site they can expose SQL, internal
     * URLs and code paths.
     */
    public static function viewData(\Throwable $e): array
    {
        $c    = self::classify($e);
        $data = [
            'referenceId'       => app('error.reference_id'),
            'statusCode'        => self::status($e),
            'technicalCategory' => $c['category'],
        ];

        if (config('app.error_details')) {
            $data += [
                'technicalSummary' => $c['summary'],
                'technicalMessage' => self::scrub($e->getMessage()),
                'technicalFile'    => $e->getFile() . ':' . $e->getLine(),
            ];
        }

        return $data;
    }
}