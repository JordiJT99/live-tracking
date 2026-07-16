<?php

declare(strict_types=1);

namespace Simulator\Application;

final class StopSimulationHandler
{
    public function __construct(private readonly SimulationState $state) {}

    /** @return array{running: bool, service_count: null, message: string} */
    public function handle(): array
    {
        $this->state->stop();

        return [
            'running'       => false,
            'service_count' => null,
            'message'       => 'Simulation stopped',
        ];
    }
}
