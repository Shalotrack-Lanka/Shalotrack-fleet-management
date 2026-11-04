<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Tests\Feature\Concerns\SignedInFleetUser;
use Tests\TestCase;

/**
 * The live link is a bearer secret: whoever holds the URL sees the vehicle. These tests pin the places where it
 * could leak outside the owner's hands: the scheme of the link we hand out, the web-server access log, and secrets
 * accidentally committed to the repository.
 */
class LiveLinkSecretsTest extends TestCase
{
    use SignedInFleetUser;

    private const TOKEN = 'sNxe6CrKgUlG8AF0cMkOHsZ5VtjqeZV0QQkjYj2pCO0';

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootFleetWriteTest();
    }

    public function test_the_link_is_always_https_even_when_the_request_reached_php_as_plain_http(): void
    {
        // Production: Cloudflare -> ALB over plain HTTP, so PHP sees http. AppServiceProvider forces https there.
        URL::forceScheme('https');
        $this->fakeApi(['LiveShare/vehicle/*' => $this->ok(['data' => ['linkId' => self::UUID2, 'token' => self::TOKEN, 'createdAt' => '2026-11-04T10:00:00Z', 'expiresAt' => '2026-11-04T11:00:00Z']])]);

        $res = $this->asCustomer()->postJson('/api/vehicles/' . self::UUID . '/live-links', ['durationHours' => 1], ['X-Forwarded-Proto' => 'http'])->assertStatus(201);

        $this->assertStringStartsWith('https://', $res->json('link.url'));
        $this->assertStringEndsWith('/live/' . self::TOKEN, $res->json('link.url'));
    }

    public function test_the_access_log_never_contains_a_live_link_token(): void
    {
        $conf = file_get_contents(base_path('docker/nginx.conf'));

        // the token path is replaced by a placeholder before it reaches the log...
        $this->assertMatchesRegularExpression('/map\s+\$request_uri\s+\$log_uri\s*\{.*?\[redacted\]/s', $conf);
        // ...and the log line is built from that safe value, not from the raw request line.
        $this->assertMatchesRegularExpression('/log_format\s+main\s+[^;]*\$log_uri/s', $conf);
        $this->assertDoesNotMatchRegularExpression('/log_format\s+main\s+[^;]*"\$request"/s', $conf, 'the raw "$request" line contains the token');
        $this->assertDoesNotMatchRegularExpression('/log_format\s+main\s+[^;]*\$request_uri/s', $conf, 'the log line must read the redacted value only');
    }

    public function test_the_application_logs_never_carry_the_token(): void
    {
        $this->assertSame('/live/{id}', \App\Support\ErrorLogger::safePath('/live/' . self::TOKEN));
        $this->assertSame('/live/{id}/data', \App\Support\ErrorLogger::safePath('/live/' . self::TOKEN . '/data'));
    }

    public function test_no_secret_looking_value_is_committed_in_the_source(): void
    {
        $patterns = [
            'Google API key'      => '/AIza[0-9A-Za-z_\-]{30,}/',
            'AWS access key id'   => '/\b(AKIA|ASIA)[0-9A-Z]{16}\b/',
            'private key block'   => '/-----BEGIN (RSA |EC |OPENSSH |DSA )?PRIVATE KEY-----/',
            'JWT / ID token'      => '/\beyJ[A-Za-z0-9_\-]{15,}\.[A-Za-z0-9_\-]{15,}\.[A-Za-z0-9_\-]{40,}/',   // a real signature is 43+ characters; test samples are shorter
            'GitHub token'        => '/\bgh[pousr]_[0-9A-Za-z]{30,}\b/',
            'Slack token'         => '/\bxox[baprs]-[0-9A-Za-z-]{10,}/',
            'live payment key'    => '/\bsk_live_[0-9A-Za-z]{10,}/',
        ];

        $hits = [];
        $roots = ['app', 'bootstrap', 'config', 'database', 'docker', 'resources', 'routes', 'tests', '.github'];
        foreach ($roots as $dir) {
            if (!is_dir(base_path($dir))) {
                continue;
            }
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(base_path($dir), \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $file) {
                $path = $file->getPathname();
                // This very file lists the patterns; vendored/built assets are not ours.
                if (!$file->isFile() || str_contains($path, 'LiveLinkSecretsTest.php') || str_contains($path, '/vendor/') || $file->getSize() > 2_000_000) {
                    continue;
                }
                $text = @file_get_contents($path);
                if ($text === false) {
                    continue;
                }
                foreach ($patterns as $label => $re) {
                    if (preg_match($re, $text)) {
                        $hits[] = "$label in " . str_replace(base_path() . '/', '', $path);
                    }
                }
            }
        }
        foreach (['.env.example'] as $f) {
            $text = (string) @file_get_contents(base_path($f));
            foreach ($patterns as $label => $re) {
                if (preg_match($re, $text)) {
                    $hits[] = "$label in $f";
                }
            }
        }

        $this->assertSame([], $hits, "Possible secret committed:\n" . implode("\n", $hits));
    }

    public function test_the_env_file_is_ignored_by_git(): void
    {
        $this->assertStringContainsString('.env', (string) file_get_contents(base_path('.gitignore')));
    }
}
