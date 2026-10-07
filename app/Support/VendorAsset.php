<?php

namespace App\Support;

/**
 * Renders a <script> tag for a third-party bundle that `npm run build` copied
 * into public/build/vendor (see scripts/copy-vendor.mjs). Same-origin, version
 * pinned by package-lock.json, and carries a Subresource-Integrity hash that
 * the build computes from the exact bytes it shipped.
 *
 * Usage in Blade:  @vendorScript('signalr')   @vendorScript('chart')
 */
final class VendorAsset
{
    /** @var array<string, array{file: string, integrity: string}>|null */
    private static ?array $manifest = null;

    public static function script(string $key): string
    {
        $entry = self::manifest()[$key] ?? null;

        if ($entry === null) {
            // Don't fail the whole page (and don't silently fall back to a CDN):
            // say loudly in the HTML and the log that the build step was skipped.
            logger()->error('VendorAsset: bundle missing — run "npm run build"', ['key' => $key]);
            return '<!-- vendor bundle "' . e($key) . '" missing: run npm run build -->';
        }

        return sprintf(
            '<script src="%s" integrity="%s" crossorigin="anonymous"></script>',
            e('/' . ltrim($entry['file'], '/')),
            e($entry['integrity'])
        );
    }

    private static function manifest(): array
    {
        if (self::$manifest === null) {
            $path = public_path('build/vendor/manifest.json');
            $json = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;
            self::$manifest = is_array($json) ? $json : [];
        }

        return self::$manifest;
    }
}