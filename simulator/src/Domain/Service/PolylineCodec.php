<?php

declare(strict_types=1);

namespace Simulator\Domain\Service;

use Simulator\Domain\Model\Coordinate;

/**
 * Google Encoded Polyline Algorithm Format (precision 1e-5).
 * Spec: https://developers.google.com/maps/documentation/utilities/polylinealgorithm
 */
final class PolylineCodec
{
    /** @param Coordinate[] $coordinates */
    public function encode(array $coordinates): string
    {
        $output  = '';
        $prevLat = 0;
        $prevLng = 0;

        foreach ($coordinates as $coord) {
            $output .= $this->encodeValue((int) round($coord->lat * 1e5) - $prevLat);
            $output .= $this->encodeValue((int) round($coord->lng * 1e5) - $prevLng);

            $prevLat = (int) round($coord->lat * 1e5);
            $prevLng = (int) round($coord->lng * 1e5);
        }

        return $output;
    }

    /** @return Coordinate[] */
    public function decode(string $polyline): array
    {
        $coordinates = [];
        $index       = 0;
        $len         = strlen($polyline);
        $lat         = 0;
        $lng         = 0;

        while ($index < $len) {
            $lat += $this->decodeValue($polyline, $index);
            $lng += $this->decodeValue($polyline, $index);

            $coordinates[] = new Coordinate($lat / 1e5, $lng / 1e5);
        }

        return $coordinates;
    }

    private function encodeValue(int $value): string
    {
        $value = $value < 0 ? ~($value << 1) : ($value << 1);
        $chunk = '';

        while ($value >= 0x20) {
            $chunk .= chr((0x20 | ($value & 0x1f)) + 63);
            $value >>= 5;
        }
        $chunk .= chr($value + 63);

        return $chunk;
    }

    private function decodeValue(string $polyline, int &$index): int
    {
        $result = 0;
        $shift  = 0;

        do {
            $b      = ord($polyline[$index++]) - 63;
            $result |= ($b & 0x1f) << $shift;
            $shift  += 5;
        } while ($b >= 0x20);

        return ($result & 1) ? ~($result >> 1) : ($result >> 1);
    }
}
