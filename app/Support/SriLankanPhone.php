<?php

namespace App\Support;

/**
 * Sri Lankan mobile numbers in one canonical form: +947XXXXXXXX.
 *
 * Accepts what people actually type or paste: 0771234567, 077 123 4567, +94 77 123 4567, 0094771234567,
 * 94771234567, (0)77-123-4567 and 771234567. Anything that is not a Sri Lankan mobile (7 + 8 digits) is null.
 * The browser does the same clean-up for comfort; this is the copy that counts.
 */
final class SriLankanPhone
{
    public static function normalise(?string $input): ?string
    {
        // Only what people really type: digits, spaces, + ( ) . -  (anything else - letters, markup, odd digits - is not a number).
        if (preg_match('/^[+0-9\s().\-]+$/D', (string) $input) !== 1) {
            return null;
        }

        $digits = preg_replace('/\D/', '', (string) $input);
        if ($digits === null || $digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0094')) {
            $digits = substr($digits, 4);
        } elseif (str_starts_with($digits, '94') && strlen($digits) > 9) {
            $digits = substr($digits, 2);
        }
        if (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        return preg_match('/^7\d{8}$/', $digits) === 1 ? '+94' . $digits : null;
    }

    /** "+94 71 234 5678", for messages shown to the customer. */
    public static function pretty(string $e164): string
    {
        return preg_match('/^\+94(\d{2})(\d{3})(\d{4})$/', $e164, $m) ? "+94 {$m[1]} {$m[2]} {$m[3]}" : $e164;
    }
}
