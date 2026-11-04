<?php

namespace App\Support;

/**
 * What a street address may contain: letters of any language, digits 0-9, spaces, comma and slash.
 * Nothing else (no symbols, markup or emoji). It must contain at least one letter, so "123" alone is not an address.
 * The browser filters the same way as you type; this is the copy that counts.
 */
final class Address
{
    public const REGEX = '/^(?=.*\p{L})[\p{L}\p{M}\x{200D}0-9 ,\/]+$/u';

    public const MESSAGE = 'Use letters, numbers, spaces, commas (,) and slashes (/) only.';

    /** Tidy before checking: squeeze spaces and line breaks into single spaces. */
    public static function clean(?string $input): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', (string) $input));
    }
}
