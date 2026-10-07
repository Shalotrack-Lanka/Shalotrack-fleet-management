<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds a Content-Security-Policy and a Permissions-Policy to every web response.
 *
 * The policy is deliberately honest about today's code: the pages use inline
 * <script>/<style> blocks and ~165 inline onclick handlers, so 'unsafe-inline'
 * is still needed for script-src. What the policy DOES buy us right now:
 *   - no third-party script CDNs (only Google Maps + Firebase/reCAPTCHA hosts),
 *   - no <object>/<embed>, no <base> hijack, forms can only post to ourselves,
 *   - nobody can frame the portal (clickjacking), connections only to our own
 *     API + Google auth/maps hosts (limits where injected script can send data).
 *
 * Rollout: it ships as Report-Only (violations are logged by /csp-report, nothing
 * is blocked). After a week of clean logs set CSP_ENFORCE=true to enforce it.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $header = config('security.csp_enforce') ? 'Content-Security-Policy' : 'Content-Security-Policy-Report-Only';
        if (! $response->headers->has($header)) {
            $response->headers->set($header, $this->policy());
        }

        if (! $response->headers->has('Permissions-Policy')) {
            // geolocation only for ourselves; camera/microphone/payment never needed.
            $response->headers->set('Permissions-Policy', 'geolocation=(self), camera=(), microphone=(), payment=(), usb=()');
        }

        return $response;
    }

    private function policy(): string
    {
        $api = rtrim((string) config('shalotrack.api_base_url'), '/');
        $apiWs = preg_replace('#^http#', 'ws', $api); // https:// → wss://

        $maps = 'https://maps.googleapis.com https://maps.gstatic.com';
        $firebase = 'https://www.gstatic.com https://www.google.com https://www.googleapis.com '
                  . 'https://identitytoolkit.googleapis.com https://securetoken.googleapis.com';

        $directives = [
            "default-src 'self'",
            // 'unsafe-inline' is the known gap (inline handlers); see class docblock.
            "script-src 'self' 'unsafe-inline' {$maps} https://www.gstatic.com https://www.google.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com",
            "img-src 'self' data: blob: https://maps.googleapis.com https://maps.gstatic.com https://*.ggpht.com https://*.googleusercontent.com",
            "connect-src 'self' {$api} {$apiWs} {$maps} {$firebase}",
            "frame-src https://www.google.com https://*.firebaseapp.com",
            "worker-src 'self' blob:",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
            'report-uri /csp-report',
        ];

        return implode('; ', $directives);
    }
}