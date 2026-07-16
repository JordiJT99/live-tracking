<?php

declare(strict_types=1);

namespace Simulator\Application;

use Simulator\Domain\Repository\TrackingRepositoryInterface;
use Simulator\Domain\Service\GpsNoise;

final class TickHandler
{
    private const TICK_SECONDS = 5.0;

    public function __construct(
        private readonly SimulationState         $state,
        private readonly TrackingRepositoryInterface $trackingRepo,
        private readonly GpsNoise               $noise,
    ) {}

    public function tick(): void
    {
        if (!$this->state->isRunning()) {
            return;
        }

        foreach ($this->state->activeRuns() as $run) {
            $position = $run->advance(self::TICK_SECONDS);
            $noisy    = $this->noise->apply($position);
            $this->trackingRepo->append($run->service->id, $noisy);
        }
    }
}
