<?php

declare(strict_types=1);

namespace Simulator\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Simulator\Domain\Model\Coordinate;
use Simulator\Domain\Service\GpsNoise;

final class GpsNoiseTest extends TestCase
{
    public function test_output_is_within_25_metres_of_input(): void
    {
        $noise  = new GpsNoise();
        $origin = new Coordinate(41.386, 2.175);

        for ($i = 0; $i < 200; $i++) {
            $noisy    = $noise->apply($origin);
            $distance = $origin->distanceTo($noisy);

            self::assertLessThanOrEqual(
                25.0,
                $distance,
                "Sample {$i}: noise exceeded 25 m cap (got {$distance} m)",
            );
        }
    }

    public function test_output_coordinate_is_valid(): void
    {
        $noise  = new GpsNoise();
        $origin = new Coordinate(41.386, 2.175);

        for ($i = 0; $i < 50; $i++) {
            $noisy = $noise->apply($origin);

            self::assertGreaterThanOrEqual(-90.0, $noisy->lat);
            self::assertLessThanOrEqual(90.0, $noisy->lat);
            self::assertGreaterThanOrEqual(-180.0, $noisy->lng);
            self::assertLessThanOrEqual(180.0, $noisy->lng);
        }
    }

    public function test_noise_produces_variation(): void
    {
        $noise  = new GpsNoise();
        $origin = new Coordinate(41.386, 2.175);
        $lats   = [];

        for ($i = 0; $i < 20; $i++) {
            $lats[] = $noise->apply($origin)->lat;
        }

        // At least some variation — not all identical
        self::assertGreaterThan(1, count(array_unique($lats)));
    }
}
