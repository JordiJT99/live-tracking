<?php

declare(strict_types=1);

namespace Simulator\Domain\Service;

use Simulator\Domain\Model\Coordinate;

/**
 * Adds bounded Gaussian noise to a coordinate (~±10 m, hard capped at 25 m).
 * Uses the Box-Muller transform to approximate a normal distribution.
 */
final class GpsNoise
{
    private const SIGMA_METRES = 5.0;
    private const MAX_METRES   = 25.0;

    /** 1 degree of latitude ≈ 111 320 m */
    private const METRES_PER_LAT_DEG = 111_320.0;

    public function apply(Coordinate $coord): Coordinate
    {
        $offsetLat = $this->gaussianMetres();
        $offsetLng = $this->gaussianMetres();

        $metresPerLngDeg = self::METRES_PER_LAT_DEG * cos(deg2rad($coord->lat));

        $newLat = $coord->lat + $offsetLat / self::METRES_PER_LAT_DEG;
        $newLng = $coord->lng + ($metresPerLngDeg > 0 ? $offsetLng / $metresPerLngDeg : 0.0);

        // Clamp to valid ranges
        $newLat = max(-90.0, min(90.0, $newLat));
        $newLng = max(-180.0, min(180.0, $newLng));

        return new Coordinate($newLat, $newLng);
    }

    private function gaussianMetres(): float
    {
        // Box-Muller transform ($u in (0, 1], so log($u) is always defined)
        $u = mt_rand(1, mt_getrandmax()) / mt_getrandmax();
        $v = mt_rand(0, mt_getrandmax()) / mt_getrandmax();
        $z = sqrt(-2.0 * log($u)) * cos(2.0 * M_PI * $v);

        return max(-self::MAX_METRES, min(self::MAX_METRES, $z * self::SIGMA_METRES));
    }
}
