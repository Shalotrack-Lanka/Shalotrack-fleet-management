<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

/**
 * Regression for the 27 Oct 2026 audit (F1 stored XSS, F5 PHP hardening).
 *
 * THE BUG CLASS: an HTML-escaped value placed inside a JavaScript string inside an inline handler, e.g.
 *     onclick="confirmRevoke('${esc(name)}')"      or      onclick="f('{{ $name }}')"
 * The browser decodes HTML entities in the attribute BEFORE running the JavaScript, so the &#39; that esc()/{{ }}
 * produced turns back into a real quote and the rest of the name runs as code.
 *
 * THE RULES THAT PREVENT IT:
 *   - dynamic data reaches JS through data-* attributes read back via dataset (client-rendered markup), or
 *   - through @js(...) (server-rendered markup), which hex-escapes quotes, < > and &.
 *
 * NOTE: CI does not run phpunit today. Run this locally (php artisan test --filter=InlineHandlerSafetyTest)
 * until the pipeline owner approves a phpunit step or an equivalent grep gate.
 */
class InlineHandlerSafetyTest extends TestCase
{
    /**
     * Inline handlers that interpolate a value, reviewed and known safe. Add to this list ONLY after proving the
     * value can never contain user-controlled text (server-generated counters, array indexes, enums).
     */
    private const REVIEWED_SAFE = [
        "onclick=\"toggleGroup('\${gid}')\"",   // gid = 'dg' + loop counter (trips.blade.php)
        'onclick="selectTrip(${trip._i})"',     // _i = numeric array index (trips.blade.php)
    ];

    /** @return array<string,string> relative path => file contents, for every Blade view */
    private function views(): array
    {
        $root = resource_path('views');
        $files = [];
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS));
        foreach ($it as $f) {
            if ($f->isFile() && str_ends_with($f->getFilename(), '.blade.php')) {
                $files[str_replace($root . DIRECTORY_SEPARATOR, '', $f->getPathname())] = file_get_contents($f->getPathname());
            }
        }
        $this->assertNotEmpty($files, 'No Blade views found - the scan would silently pass.');
        return $files;
    }

    /** @return string[] "path:line  source" for every view line matching $pattern that is not on the reviewed list */
    private function offenders(string $pattern): array
    {
        $found = [];
        foreach ($this->views() as $path => $src) {
            foreach (explode("\n", $src) as $i => $line) {
                if (!preg_match($pattern, $line)) {
                    continue;
                }
                $reviewed = false;
                foreach (self::REVIEWED_SAFE as $safe) {
                    if (str_contains($line, $safe)) {
                        $reviewed = true;
                        break;
                    }
                }
                if (!$reviewed) {
                    $found[] = $path . ':' . ($i + 1) . '  ' . trim($line);
                }
            }
        }
        return $found;
    }

    public function test_no_inline_handler_interpolates_a_javascript_template_value(): void
    {
        // onclick="...${anything}..." - the client-side form of the F1 bug.
        $found = $this->offenders('/\bon[a-z]+\s*=\s*"[^"]*\$\{/');

        $this->assertSame([], $found,
            "Inline handler with a \${...} value. Use data-* attributes + a delegated listener instead:\n" . implode("\n", $found));
    }

    public function test_no_inline_handler_puts_a_blade_echo_inside_a_javascript_string(): void
    {
        // onclick="f('{{ $x }}')" - the server-side form of the F1 bug. {{ }} HTML-escapes the quote, the browser
        // decodes it back to a real quote. Use @js($x) instead.
        $found = $this->offenders('/\bon[a-z]+\s*=\s*"[^"]*[\'`]\s*(\{\{|\{!!)/');

        $this->assertSame([], $found,
            "Blade echo inside a quoted JS string in an inline handler. Use @js(...) instead:\n" . implode("\n", $found));
    }

    public function test_js_directive_neutralises_a_quote_breakout_payload(): void
    {
        // Contains both quote types: ' would close the JS string, " would close the HTML attribute.
        $payload = "x');window.__pwned=\"EXECUTED\";//";

        $html = Blade::render('<button onclick="confirmDel(@js($name))">x</button>', ['name' => $payload]);

        // The payload must not be able to close the JS string or the HTML attribute...
        $this->assertStringNotContainsString("');window", $html);
        $this->assertStringNotContainsString('"', substr($html, strlen('<button onclick="'), -strlen('">x</button>')));
        // ...because every quote was hex-escaped inside the string.
        $expected = substr(json_encode($payload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT), 1, -1);
        $this->assertStringContainsString($expected, $html);
        $this->assertStringContainsString('u0027', $html); // the apostrophe really was hex-escaped
    }

    public function test_php_fpm_pool_disables_process_spawning_functions(): void
    {
        $conf = file_get_contents(base_path('docker/php-fpm.conf'));

        $this->assertMatchesRegularExpression('/^php_admin_value\[disable_functions\]\s*=\s*(.+)$/m', $conf);
        preg_match('/^php_admin_value\[disable_functions\]\s*=\s*(.+)$/m', $conf, $m);
        $disabled = array_map('trim', explode(',', $m[1]));

        foreach (['exec', 'passthru', 'shell_exec', 'system', 'proc_open', 'popen', 'pcntl_exec'] as $fn) {
            $this->assertContains($fn, $disabled, "$fn must stay disabled for FPM workers.");
        }
    }

    public function test_session_upload_progress_stays_off(): void
    {
        $ini = file_get_contents(base_path('docker/php.ini'));

        $this->assertMatchesRegularExpression('/^session\.upload_progress\.enabled\s*=\s*0\s*$/m', $ini);
    }

    public function test_the_web_request_path_never_calls_a_disabled_function(): void
    {
        // If app code started to need one of these, disabling them in FPM would break production. Fail here first.
        $hits = [];
        foreach (['app', 'routes', 'bootstrap', 'config'] as $dir) {
            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(base_path($dir), RecursiveDirectoryIterator::SKIP_DOTS));
            foreach ($it as $f) {
                if (!$f->isFile() || $f->getExtension() !== 'php') {
                    continue;
                }
                $src = file_get_contents($f->getPathname());
                if (preg_match('/(?<![\w>:$])(exec|passthru|shell_exec|system|proc_open|popen|pcntl_exec)\s*\(/', $src, $mm)) {
                    $hits[] = $dir . '/' . $f->getFilename() . ' calls ' . $mm[1] . '()';
                }
            }
        }

        $this->assertSame([], $hits, implode("\n", $hits));
    }
}