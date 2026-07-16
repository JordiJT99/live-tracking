<?php

declare(strict_types=1);

namespace Simulator\Application;

use Simulator\Domain\Model\VehicleRun;

/**
 * In-memory state of the current simulation run.
 * Lives in the Application layer — owned by the ReactPHP process.
 * Resets on container restart (documented limitation).
 */
final class SimulationState
{
    private bool  $running = false;
    /** @var VehicleRun[] */
    private array $runs    = [];

    public function isRunning(): bool
    {
        return $this->running;
    }

    public function start(array $runs): void
    {
        $this->runs    = $runs;
        $this->running = true;
    }

    public function stop(): void
    {
        $this->running = false;
        $this->runs    = [];
    }

    /** @return VehicleRun[] */
    public function activeRuns(): array
    {
        return $this->runs;
    }

    public function activeCount(): int
    {
        return count($this->runs);
    }
}
