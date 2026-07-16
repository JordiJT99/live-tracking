<?php

declare(strict_types=1);

namespace Simulator\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Simulator\Domain\Model\Coordinate;
use Simulator\Domain\Model\Route;

final class RouteTest extends TestCase
{
    private Route $route;

    protected function setUp(): void
    {
        // Three collinear points ~111 m apart (1e-3 degrees lat ≈ 111 m)
        $this->route = new Route([
            new Coordinate(41.0, 2.0),
            new Coordinate(41.001, 2.0),
            new Coordinate(41.002, 2.0),
        ]);
    }

    public function test_requires_at_least_two_points(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Route([new Coordinate(41.0, 2.0)]);
    }

    public function test_point_at_zero_returns_first_point(): void
    {
        $p = $this->route->pointAtDistance(0.0);

        self::assertEqualsWithDelta(41.0, $p->lat, 1e-6);
        self::assertEqualsWithDelta(2.0,  $p->lng, 1e-6);
    }

    public function test_point_at_total_length_returns_last_point(): void
    {
        $p = $this->route->pointAtDistance($this->route->totalLength());

        self::assertEqualsWithDelta(41.002, $p->lat, 1e-5);
        self::assertEqualsWithDelta(2.0,    $p->lng, 1e-6);
    }

    public function test_point_at_midpoint(): void
    {
        $mid = $this->route->pointAtDistance($this->route->totalLength() / 2.0);

        self::assertEqualsWithDelta(41.001, $mid->lat, 1e-4);
    }

    public function test_clamps_beyond_total_length(): void
    {
        $p = $this->route->pointAtDistance($this->route->totalLength() * 10);

        self::assertEqualsWithDelta(41.002, $p->lat, 1e-5);
    }

    public function test_clamps_negative_distance(): void
    {
        $p = $this->route->pointAtDistance(-100.0);

        self::assertEqualsWithDelta(41.0, $p->lat, 1e-6);
    }

    public function test_total_length_is_positive(): void
    {
        self::assertGreaterThan(0.0, $this->route->totalLength());
    }
}
