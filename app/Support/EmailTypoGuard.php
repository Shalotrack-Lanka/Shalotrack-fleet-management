<?php

namespace App\Support;

/**
 * Catches the classic typos in the domain part of an email (gamil.com, gmial.com, hotmial.com, yaho.com).
 *
 * The email is no longer verified (owner decision, 2 Nov 2026), so a typo would be stored as typed and the
 * customer's email + password login name would be wrong. This only SUGGESTS: the customer can keep what they
 * typed ("Keep what I typed"), because a real but unusual domain must never be blocked.
 */
class EmailTypoGuard
{
    /** Domains customers really use. A typo is only flagged when it is close to one of these. */
    private const COMMON = [
        'gmail.com', 'googlemail.com', 'yahoo.com', 'ymail.com', 'hotmail.com', 'outlook.com',
        'live.com', 'msn.com', 'icloud.com', 'protonmail.com', 'sltnet.lk',
    ];

    /** Real providers that sit close to a common one (mail.com is 1 edit from gmail.com): never "correct" these. */
    private const KNOWN_GOOD = [
        'mail.com', 'aol.com', 'gmx.com', 'gmx.net', 'zoho.com', 'proton.me', 'pm.me', 'yandex.com',
        'fastmail.com', 'hey.com', 'me.com', 'mac.com', 'slt.lk', 'dialog.lk', 'mobitel.lk',
    ];

    /** The corrected address ("a@gmail.com"), or null when the domain looks fine. */
    public static function suggest(string $email): ?string
    {
        $email = trim($email);
        $at    = strrpos($email, '@');
        if ($at === false) {
            return null;
        }

        $local  = substr($email, 0, $at);
        $domain = strtolower(substr($email, $at + 1));
        if ($local === '' || $domain === '' || in_array($domain, self::COMMON, true) || in_array($domain, self::KNOWN_GOOD, true)) {
            return null;
        }

        $best = null;
        $bestDistance = 3;
        foreach (self::COMMON as $candidate) {
            $d = levenshtein($domain, $candidate);
            if ($d < $bestDistance) {
                $bestDistance = $d;
                $best = $candidate;
            }
        }

        // 1 edit always counts; 2 edits only for longer domains (avoids flagging short unrelated names).
        if ($best === null || ($bestDistance === 2 && strlen($domain) < 9)) {
            return null;
        }

        return $local . '@' . $best;
    }
}