<?php

declare(strict_types=1);

namespace Simulator\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Simulator\Domain\Model\Coordinate;
use Simulator\Domain\Service\PolylineCodec;

final class PolylineCodecTest extends TestCase
{
    private PolylineCodec $codec;

    protected function setUp(): void
    {
        $this->codec = new PolylineCodec();
    }

    /** Official example from Google's documentation. */
    public function test_official_google_example(): void
    {
        $coords = [
            new Coordinate(38.5, -120.2),
            new Coordinate(40.7, -120.95),
            new Coordinate(43.252, -126.453),
        ];

        self::assertSame('_p~iF~ps|U_ulLnnqC_mqNvxq`@', $this->codec->encode($coords));
    }

    public function test_decode_official_google_example(): void
    {
        $coords = $this->codec->decode('_p~iF~ps|U_ulLnnqC_mqNvxq`@');

        self::assertCount(3, $coords);
        self::assertEqualsWithDelta(38.5,     $coords[0]->lat, 1e-4);
        self::assertEqualsWithDelta(-120.2,   $coords[0]->lng, 1e-4);
        self::assertEqualsWithDelta(40.7,     $coords[1]->lat, 1e-4);
        self::assertEqualsWithDelta(-120.95,  $coords[1]->lng, 1e-4);
        self::assertEqualsWithDelta(43.252,   $coords[2]->lat, 1e-4);
        self::assertEqualsWithDelta(-126.453, $coords[2]->lng, 1e-4);
    }

    public function test_round_trip_preserves_barcelona_coords(): void
    {
        $original = [
            new Coordinate(41.38658, 2.173511),
            new Coordinate(41.386788, 2.17414),
            new Coordinate(41.378923, 2.177454),
        ];

        $decoded = $this->codec->decode($this->codec->encode($original));

        foreach ($original as $i => $coord) {
            self::assertEqualsWithDelta($coord->lat, $decoded[$i]->lat, 1e-5);
            self::assertEqualsWithDelta($coord->lng, $decoded[$i]->lng, 1e-5);
        }
    }

    public function test_single_point_round_trip(): void
    {
        $coords  = [new Coordinate(41.0, 2.0)];
        $decoded = $this->codec->decode($this->codec->encode($coords));

        self::assertCount(1, $decoded);
        self::assertEqualsWithDelta(41.0, $decoded[0]->lat, 1e-5);
        self::assertEqualsWithDelta(2.0,  $decoded[0]->lng, 1e-5);
    }

    public function test_negative_coordinates_round_trip(): void
    {
        $coords  = [new Coordinate(-33.8688, 151.2093)]; // Sydney
        $decoded = $this->codec->decode($this->codec->encode($coords));

        self::assertEqualsWithDelta(-33.8688, $decoded[0]->lat, 1e-4);
        self::assertEqualsWithDelta(151.2093, $decoded[0]->lng, 1e-4);
    }
}
