<?php

namespace App\Support;

/**
 * Maps the free-text vehicleType from the API to one of the bundled icon sets
 * in public/vehicle-icons/{key}-{green|blue}.png.
 *
 * Unknown / empty / Bus / Other → null, and every caller falls back to the
 * generic marker. That is deliberate: a wrong icon is worse than a plain one.
 * The same map is handed to the browser (partials/vehicle-icons) so server and
 * client can never disagree.
 */
final class VehicleIcon
{
    /** normalised type text → icon key */
    public const MAP = [
        'car' => 'car', 'sedan' => 'car', 'hatchback' => 'car', 'saloon' => 'car',
        'suv' => 'suv', 'jeep' => 'suv', 'crossover' => 'suv',
        'van' => 'van', 'minivan' => 'van', 'mini-van' => 'van',
        'truck' => 'truck', 'lorry' => 'truck', 'pickup' => 'truck',
        'motorcycle' => 'motorcycle', 'motorbike' => 'motorcycle', 'bike' => 'motorcycle', 'scooter' => 'motorcycle',
        'three-wheeler' => 'three-wheeler', 'threewheeler' => 'three-wheeler',
        'tuk-tuk' => 'three-wheeler', 'tuktuk' => 'three-wheeler', 'tuk' => 'three-wheeler',
    ];

    public static function key(?string $type): ?string
    {
        if ($type === null) {
            return null;
        }
        $n = preg_replace('/[\s_]+/', '-', strtolower(trim($type)));

        return self::MAP[$n] ?? null;
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