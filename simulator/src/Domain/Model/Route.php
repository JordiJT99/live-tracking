<?php

declare(strict_types=1);

namespace Simulator\Domain\Model;

final class Route
{
    /** @var Coordinate[] */
    private array $points;
    /** @var float[] cumulative distances in metres, index-aligned with $points */
    private array $cumDist;
    private float $totalLength;

    /** @param Coordinate[] $points */
    public function __construct(array $points)
    {
        if (count($points) < 2) {
            throw new \InvalidArgumentException('A Route requires at least 2 points');
        }
        $this->points = array_values($points);

        $this->cumDist = [0.0];
        for ($i = 1, $n = count($this->points); $i < $n; $i++) {
            $this->cumDist[] = $this->cumDist[$i - 1]
                + $this->points[$i - 1]->distanceTo($this->points[$i]);
        }
        $this->totalLength = end($this->cumDist);
    }

    public function totalLength(): float
    {
        return $this->totalLength;
    }

    /** @return Coordinate[] */
    public function points(): array
    {
        return $this->points;
    }

    /**
     * Linear interpolation along the route at a given distance from the start.
     * Clamps to endpoints if $metres is outside [0, totalLength].
     */
    public function pointAtDistance(float $metres): Coordinate
    {
        $metres = max(0.0, min($metres, $this->totalLength));

        $n = count($this->points);

        for ($i = 1; $i < $n; $i++) {
            if ($this->cumDist[$i] >= $metres) {
                $segLen = $this->cumDist[$i] - $this->cumDist[$i - 1];
                $t      = $segLen > 0.0
                    ? ($metres - $this->cumDist[$i - 1]) / $segLen
                    : 0.0;

                $a = $this->points[$i - 1];
                $b = $this->points[$i];

                return new Coordinate(
                    $a->lat + $t * ($b->lat - $a->lat),
                    $a->lng + $t * ($b->lng - $a->lng),
                );
            }
        }

        return $this->points[$n - 1];
    }
}
