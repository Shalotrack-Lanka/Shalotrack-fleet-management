<?php

namespace App\Support;

/**
 * Maps the free-text vehicleType from the API to one of the bundled icon sets
 * in public/vehicle-icons/{key}-{green|blue}.png.
 *
 * The type is typed by people ("Motor Bike", "tuk tuk", "Three-Wheeler", "Lorry"), so we
 * compact it (lower-case, letters and digits only) and look for the first RULES needle in it.
 * Order matters: more specific words first.
 *
 * Unknown / empty / Bus / Other → null, and every caller falls back to the generic marker.
 * That is deliberate: a wrong icon is worse than a plain one. The same RULES are handed to the
 * browser (partials/vehicle-icons) so server and client can never disagree.
 */
final class VehicleIcon
{
    /** [needle in the compacted type, icon key] — first match wins */
    public const RULES = [
        ['suv', 'suv'], ['jeep', 'suv'], ['crossover', 'suv'],
        ['minibus', null], ['bus', null],
        ['threewheel', 'three-wheeler'], ['3wheel', 'three-wheeler'], ['tuk', 'three-wheeler'], ['rickshaw', 'three-wheeler'],
        ['motorcycle', 'motorcycle'], ['motorbike', 'motorcycle'], ['bike', 'motorcycle'], ['scooter', 'motorcycle'], ['moped', 'motorcycle'],
        ['truck', 'truck'], ['lorry', 'truck'], ['pickup', 'truck'], ['tipper', 'truck'], ['cabin', 'truck'], ['cargo', 'truck'],
        ['van', 'van'], ['hiace', 'van'],
        ['car', 'car'], ['sedan', 'car'], ['saloon', 'car'], ['hatch', 'car'], ['wagon', 'car'], ['coupe', 'car'], ['taxi', 'car'],
    ];

    public static function key(?string $type): ?string
    {
        if ($type === null) {
            return null;
        }
        $n = preg_replace('/[^a-z0-9]/', '', strtolower($type));

        foreach (self::RULES as [$needle, $key]) {
            if ($n !== '' && str_contains($n, $needle)) {
                return $key;
            }
        }

        return null;
    }

    /** Public URL of a static icon, or null when the type has none. */
    public static function url(?string $type, string $colour = 'blue'): ?string
    {
        $key = self::key($type);
        if ($key === null) {
            return null;
        }

        return asset('vehicle-icons/' . $key . '-' . ($colour === 'green' ? 'green' : 'blue') . '.png') . '?v=1';
    }
}