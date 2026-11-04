<?php

namespace App\Support;

/**
 * What a person's full name may contain: letters of any language (Sinhala and Tamil included, with their
 * combining marks and the joiner Sinhala conjuncts need), spaces, full stop, apostrophe and hyphen.
 * No digits, symbols, markup or emoji; it must start with a letter and hold at least two letters.
 * The browser filters the same way as you type; this is the copy that counts.
 */
final class PersonName
{
    /** Laravel `regex:` rule body and the PHP pattern are the same string. */
    public const REGEX = '/^(?=(?:\P{L}*\p{L}){2})\p{L}[\p{L}\p{M}\x{200D} .\'’\-]*$/u';

    public const MESSAGE = 'Enter a real name: letters only (spaces, . \' and - are fine), no numbers or symbols.';

    /** Tidy before checking: trim and squeeze runs of spaces. */
    public static function clean(?string $input): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', (string) $input));
    }

    public static function isValid(string $name): bool
    {
        return mb_strlen($name) <= 150 && preg_match(self::REGEX, $name) === 1;
    }
}
