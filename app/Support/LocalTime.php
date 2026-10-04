<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Single place for ShaloTrack's time rules.
 *
 *  - The C# API stores and returns instants in UTC (strings usually end in "Z";
 *    when they don't, they are still UTC).
 *  - Customers live in Sri Lanka (Asia/Colombo, UTC+05:30, no daylight saving).
 *  - So: everything the customer SEES is converted to Colombo here, and every
 *    date range the customer PICKS is converted from Colombo to UTC before it is
 *    sent to the API. The API's own "today / week / month" logic already works
 *    the same way (Sri Lanka local day boundaries), so reports line up with it.
 *
 * config/app.php stays on UTC on purpose: Laravel's cache, sessions, logs and
 * queue timestamps are all happier that way. Only presentation uses Colombo.
 */
final class LocalTime
{
    public const TZ = 'Asia/Colombo';

    /** Parse an API timestamp (UTC unless it carries its own offset) into Colombo time. */
    public static function parse(mixed $value): ?Carbon
    {
        if ($value instanceof CarbonInterface) {
            return Carbon::instance($value)->setTimezone(self::TZ);
        }
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->setTimezone(self::TZ);
        }
        if (!is_string($value) || trim($value) === '') {
            return null;
        }
        try {
            return Carbon::parse($value, 'UTC')->setTimezone(self::TZ);
        } catch (\Throwable) {
            return null;
        }
    }

    /** Format an API timestamp in Colombo time; $fallback when empty/unparseable. */
    public static function format(mixed $value, string $format = 'd M Y, H:i', string $fallback = '—'): string
    {
        $c = self::parse($value);
        return $c ? $c->format($format) : $fallback;
    }

    /** "5 minutes ago" — an instant difference, so independent of time zone. */
    public static function ago(mixed $value, string $fallback = '—'): string
    {
        $c = self::parse($value);
        return $c ? $c->diffForHumans() : $fallback;
    }

    /** "Now" in Sri Lanka. */
    public static function now(): Carbon
    {
        return Carbon::now(self::TZ);
    }

    /**
     * Normalise a user/browser-supplied date-time to the API's expected UTC ISO form.
     * A value with an explicit offset or "Z" is honoured; a bare "2026-10-04T00:00"
     * is taken to be Colombo wall-clock time (that is what the customer typed).
     */
    public static function toApiUtc(string $value): string
    {
        $hasZone = (bool) preg_match('/(Z|[+\-]\d{2}:?\d{2})$/i', trim($value));
        $c = Carbon::parse($value, $hasZone ? null : self::TZ);
        return $c->utc()->format('Y-m-d\TH:i:s\Z');
    }

    /**
     * Colombo calendar dates (Y-m-d) -> [fromUtc, toUtc] covering whole days,
     * from 00:00:00 of $fromDate to 23:59:59 of $toDate (Colombo time).
     *
     * @return array{0:string,1:string}|null null when either date is invalid
     */
    public static function dayRangeUtc(string $fromDate, string $toDate): ?array
    {
        $f = \DateTime::createFromFormat('!Y-m-d', $fromDate, new \DateTimeZone(self::TZ));
        $t = \DateTime::createFromFormat('!Y-m-d', $toDate,   new \DateTimeZone(self::TZ));
        if (!$f || !$t || $f->format('Y-m-d') !== $fromDate || $t->format('Y-m-d') !== $toDate) {
            return null;
        }
        $from = Carbon::instance($f)->startOfDay()->utc();
        $to   = Carbon::instance($t)->endOfDay()->utc()->startOfSecond();
        return [$from->format('Y-m-d\TH:i:s\Z'), $to->format('Y-m-d\TH:i:s\Z')];
    }

    /** Minutes -> "2h 05m" / "45 min" / "0 min". */
    public static function duration(float|int|string|null $minutes): string
    {
        $m = (int) round((float) $minutes);
        if ($m <= 0) return '0 min';
        if ($m < 60) return "{$m} min";
        $h = intdiv($m, 60);
        $r = $m % 60;
        return $r > 0 ? sprintf('%dh %02dm', $h, $r) : "{$h}h";
    }
}