<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * "Can this email's domain receive mail?" (MX, or A/AAAA as the SMTP fallback).
 *
 * The email is not verified (owner decision, 2 Nov 2026); this at least rejects dead and made-up domains.
 *
 * FAILS OPEN when DNS itself is unavailable: PHP cannot tell "no such domain" from "resolver timed out",
 * and a resolver hiccup must never block every sign-up. So a failed lookup only counts as "dead domain"
 * when a control lookup (a domain that certainly exists) succeeds in the same moment.
 */
class EmailDomainCheck
{
    private const CONTROL_DOMAIN = 'gmail.com';

    /** Replaced in tests: fn (string $domain): bool, true when the domain has MX/A/AAAA records. */
    public static $resolver = null;

    public static function canReceiveMail(string $email): bool
    {
        $at = strrpos($email, '@');
        $domain = $at === false ? '' : strtolower(rtrim(trim(substr($email, $at + 1)), '.'));
        if ($domain === '') {
            return false;
        }

        // Only positive answers are cached: a mistyped domain gets re-checked, a good one is not looked up twice.
        if (Cache::get('email-domain-ok:' . $domain)) {
            return true;
        }

        if (self::lookup($domain)) {
            Cache::put('email-domain-ok:' . $domain, true, now()->addHours(6));
            return true;
        }

        if (!self::lookup(self::CONTROL_DOMAIN)) {
            Log::warning('EmailDomainCheck: DNS looks unavailable, allowing the address through', ['domain' => $domain]);
            return true;   // resolver down: do not punish the customer
        }

        return false;
    }

    private static function lookup(string $domain): bool
    {
        if (self::$resolver !== null) {
            return (bool) (self::$resolver)($domain);
        }

        $fqdn = $domain . '.';
        return @checkdnsrr($fqdn, 'MX') || @checkdnsrr($fqdn, 'A') || @checkdnsrr($fqdn, 'AAAA');
    }
}