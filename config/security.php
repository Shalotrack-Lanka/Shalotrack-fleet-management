<?php

return [
    /*
    | Content-Security-Policy mode (see App\Http\Middleware\SecurityHeaders).
    | false → Report-Only: violations are logged at /csp-report, nothing is blocked.
    | true  → enforced. Flip only after a week of clean "CSP violation" log lines.
    */
    'csp_enforce' => (bool) env('CSP_ENFORCE', false),
];