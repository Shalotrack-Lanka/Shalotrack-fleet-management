<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * Turns the C# API's raw device-status record into the small, customer-safe
 * "vehicle health" summary the portal shows.
 *
 * Why this exists as its own class:
 *  - DATA MINIMISATION: the API record carries the IMEI, device id and internal
 *    timestamps. The browser never needs them, so they are dropped here and only
 *    the summary leaves the server.
 *  - ONE place for the thresholds. Battery 20 % mirrors the API's own low-battery
 *    push alert (LocationNotificationListener), so the portal and the phone
 *    never disagree about when a battery is "low".
 *  - Pure function of (record, now): trivially unit-testable, no I/O.
 *
 * Levels: ok < warn < bad < offline (offline wins: nothing else can be trusted
 * when the device has stopped reporting).
 */
final class DeviceHealth
{
    /** Same threshold as the API's low-battery alert. */
    public const BATTERY_WARN = 20;
    public const BATTERY_BAD  = 10;

    /** A device that has not reported for this long is treated as offline even if the flag says online. */
    public const STALE_MINUTES = 30;

    private const RANK = ['ok' => 0, 'warn' => 1, 'bad' => 2, 'offline' => 3];

    /**
     * @param array<string,mixed> $s  one DeviceStatusResponseDto (already unwrapped from {data: …})
     */
    public static function summarize(array $s, ?CarbonInterface $now = null): array
    {
        $now ??= LocalTime::now();

        $contact = self::latest(LocalTime::parse($s['lastSeen'] ?? null), LocalTime::parse($s['lastHeartbeat'] ?? null));
        $stale   = !$contact || $contact->diffInMinutes($now, true) > self::STALE_MINUTES;
        $online  = (bool) ($s['isOnline'] ?? false) && !$stale;

        $battery  = self::intOrNull($s['batteryLevel'] ?? null);
        $gps      = self::intOrNull($s['gpsSignal'] ?? null);
        $unplugged = self::isDisconnected($s['powerStatus'] ?? null);
        $ignition = (bool) ($s['ignitionStatus'] ?? false);
        $moving   = (bool) ($s['movementStatus'] ?? false);

        $items = [];
        $level = 'ok';
        $raise = function (string $to) use (&$level) {
            if (self::RANK[$to] > self::RANK[$level]) {
                $level = $to;
            }
        };

        // Connection
        if (!$online) {
            $raise('offline');
        }
        $items[] = [
            'key'   => 'connection',
            'label' => 'Connection',
            'value' => $online ? 'Online' : 'Offline',
            'state' => $online ? 'ok' : 'offline',
        ];

        // Vehicle power (device unplugged = cut wires, battery disconnect or tampering)
        $items[] = [
            'key'   => 'power',
            'label' => 'Vehicle power',
            'value' => $unplugged ? 'Disconnected' : 'Connected',
            'state' => $unplugged ? 'bad' : 'ok',
        ];
        if ($unplugged) {
            $raise('bad');
        }

        // Backup battery
        if ($battery !== null) {
            $bState = $battery <= self::BATTERY_BAD ? 'bad' : ($battery <= self::BATTERY_WARN ? 'warn' : 'ok');
            $items[] = ['key' => 'battery', 'label' => 'Device battery', 'value' => max(0, min(100, $battery)) . '%', 'state' => $bState];
            $raise($bState);
        }

        // GPS fix. The gateway's signal scale is not fixed, so only "none vs some" is claimed.
        if ($gps !== null) {
            $gState = $gps <= 0 ? 'warn' : 'ok';
            $items[] = ['key' => 'gps', 'label' => 'GPS signal', 'value' => $gps <= 0 ? 'No fix' : 'Good', 'state' => $gState];
            $raise($gState);
        }

        // Engine / movement are facts, not faults.
        $items[] = ['key' => 'ignition', 'label' => 'Ignition', 'value' => $ignition ? 'On' : 'Off', 'state' => 'info'];
        $items[] = ['key' => 'movement', 'label' => 'Movement', 'value' => $moving ? 'Moving' : 'Stopped', 'state' => 'info'];

        return [
            'available'    => true,
            'level'        => $level,
            'headline'     => self::headline($level, $contact, $now, $unplugged, $battery, $gps),
            'lastContact'  => $contact?->format('d M Y, H:i'),
            'lastContactAgo' => $contact ? self::ago($contact, $now) : null,
            'items'        => $items,
        ];
    }

    /** What the card shows when there is no device / no permission to see its status. */
    public static function unavailable(): array
    {
        return ['available' => false];
    }

    private static function headline(string $level, ?CarbonInterface $contact, CarbonInterface $now, bool $unplugged, ?int $battery, ?int $gps): string
    {
        if ($level === 'offline') {
            return $contact
                ? 'Not reporting — last contact ' . self::ago($contact, $now)
                : 'Not reporting yet';
        }
        if ($unplugged) {
            return 'Tracker is disconnected from the vehicle’s power';
        }
        if ($battery !== null && $battery <= self::BATTERY_BAD) {
            return 'Tracker battery is critically low';
        }
        if ($battery !== null && $battery <= self::BATTERY_WARN) {
            return 'Tracker battery is low';
        }
        if ($gps !== null && $gps <= 0) {
            return 'Waiting for a GPS fix';
        }
        return 'All systems normal';
    }

    /** "just now", "5 min ago", "3 h ago", "2 d ago" — deterministic (no locale/Carbon-version surprises). */
    private static function ago(CarbonInterface $t, CarbonInterface $now): string
    {
        $m = (int) max(0, floor($t->diffInSeconds($now, false) / 60));
        return match (true) {
            $m < 1    => 'just now',
            $m < 60   => $m . ' min ago',
            $m < 1440 => intdiv($m, 60) . ' h ago',
            default   => intdiv($m, 1440) . ' d ago',
        };
    }

    private static function latest(?CarbonInterface $a, ?CarbonInterface $b): ?CarbonInterface
    {
        if (!$a) return $b;
        if (!$b) return $a;
        return $a->greaterThan($b) ? $a : $b;
    }

    private static function intOrNull(mixed $v): ?int
    {
        return is_numeric($v) ? (int) $v : null;
    }

    /** The API enum PowerStatus { Connected = 0, Disconnected = 1 } arrives as a number, or a name if the API is ever switched to string enums. */
    private static function isDisconnected(mixed $v): bool
    {
        if (is_string($v) && !is_numeric($v)) {
            return strcasecmp($v, 'Disconnected') === 0;
        }
        return is_numeric($v) && (int) $v === 1;
    }
}