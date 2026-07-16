<?php

declare(strict_types=1);

namespace Simulator\Domain\Model;

final class Coordinate
{
    public function __construct(
        public readonly float $lat,
        public readonly float $lng,
    ) {
        if ($lat < -90.0 || $lat > 90.0) {
            throw new \InvalidArgumentException("Latitude {$lat} out of range [-90, 90]");
        }
        if ($lng < -180.0 || $lng > 180.0) {
            throw new \InvalidArgumentException("Longitude {$lng} out of range [-180, 180]");
        }
    }

    /** Haversine distance in metres between two coordinates. */
    public function distanceTo(self $other): float
    {
        $R   = 6_371_000.0;
        $φ1  = deg2rad($this->lat);
        $φ2  = deg2rad($other->lat);
        $Δφ  = deg2rad($other->lat - $this->lat);
        $Δλ  = deg2rad($other->lng - $this->lng);

        $a = sin($Δφ / 2) ** 2 + cos($φ1) * cos($φ2) * sin($Δλ / 2) ** 2;

        return 2 * $R * asin(sqrt($a));
    }
}
