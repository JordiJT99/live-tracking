<?php

declare(strict_types=1);

namespace Simulator\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Simulator\Domain\Model\Coordinate;
use Simulator\Domain\Model\Route;
use Simulator\Domain\Model\Service;
use Simulator\Domain\Model\VehicleRun;
use Simulator\Domain\Service\PolylineCodec;

final class VehicleRunTest extends TestCase
{
    private Service $service;

    protected function setUp(): void
    {
        $route = new Route([
            new Coordinate(41.0, 2.0),
            new Coordinate(41.001, 2.0),
            new Coordinate(41.002, 2.0),
        ]);

        $codec = new PolylineCodec();

        $this->service = new Service(
            id:              1,
            name:            'Test Bus',
            startTime:       new \DateTimeImmutable('2026-07-15 08:00:00'),
            endTime:         new \DateTimeImmutable('2026-07-15 09:00:00'),
            route:           $route,
            encodedPolyline: $codec->encode($route->points()),
        );
    }

    public function test_advance_returns_coordinate(): void
    {
        $run      = new VehicleRun($this->service, 36.0);
        $position = $run->advance(5.0);

        self::assertInstanceOf(Coordinate::class, $position);
    }

    public function test_vehicle_starts_at_route_origin(): void
    {
        $run = new VehicleRun($this->service, 36.0);

        self::assertSame(0.0, $run->distanceCovered());
    }

    public function test_vehicle_loops_after_covering_full_route(): void
    {
        $run         = new VehicleRun($this->service, 36.0);
        $totalLength = $this->service->route->totalLength();

        // Advance far beyond the full route length in one call.
        $seconds  = $totalLength / (36.0 / 3.6) + 100.0;
        $position = $run->advance($seconds);

        // The bus wraps around instead of stopping: distance is back inside
        // the route and it still returns a valid coordinate.
        self::assertInstanceOf(Coordinate::class, $position);
        self::assertLessThan($totalLength, $run->distanceCovered());
    }

    public function test_distance_covered_increases(): void
    {
        $run = new VehicleRun($this->service, 36.0);

        $run->advance(5.0);
        $after5  = $run->distanceCovered();

        $run->advance(5.0);
        $after10 = $run->distanceCovered();

        self::assertGreaterThan($after5, $after10);
    }
}
