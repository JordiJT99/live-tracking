<?php

declare(strict_types=1);

namespace Simulator\Domain\Model;

final class VehicleRun
{
    private float $distanceCovered = 0.0;

    /** Speed in m/s — set once at construction, with per-vehicle jitter. */
    private float $speedMps;

    public function __construct(
        public readonly Service $service,
        float $speedKph = 35.0,
    ) {
        $this->speedMps = $speedKph / 3.6;
    }

    public function distanceCovered(): float
    {
        return $this->distanceCovered;
    }

    /**
     * Advances the vehicle by $seconds and returns its new position.
     *
     * The bus loops: on reaching the end of the route it wraps back to the
     * start and keeps circulating. This keeps the live map alive indefinitely
     * (a monitoring dashboard should never go idle) instead of the fleet
     * freezing once every route is finished.
     */
    public function advance(float $seconds): Coordinate
    {
        $total = $this->service->route->totalLength();
        $this->distanceCovered += $this->speedMps * $seconds;

        if ($this->distanceCovered >= $total) {
            // Wrap around (modulo) so a single long advance can't overshoot.
            $this->distanceCovered = fmod($this->distanceCovered, $total);
        }

        return $this->service->route->pointAtDistance($this->distanceCovered);
    }
}
