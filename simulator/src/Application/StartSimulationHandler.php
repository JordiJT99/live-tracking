<?php

declare(strict_types=1);

namespace Simulator\Application;

use Simulator\Domain\Model\VehicleRun;
use Simulator\Domain\Repository\ServiceRepositoryInterface;

final class StartSimulationHandler
{
    public function __construct(
        private readonly ServiceRepositoryInterface $serviceRepo,
        private readonly SimulationState            $state,
    ) {}

    /** @return array{running: bool, service_count: int|null, message: string} */
    public function handle(): array
    {
        if ($this->state->isRunning()) {
            return [
                'running'       => true,
                'service_count' => $this->state->activeCount(),
                'message'       => 'Simulation already running',
            ];
        }

        $services = $this->serviceRepo->findAll();

        if (empty($services)) {
            return [
                'running'       => false,
                'service_count' => null,
                'message'       => 'No services found — generate services first',
            ];
        }

        $runs = array_map(
            fn($svc) => new VehicleRun($svc, 30.0 + (float) ($svc->id % 16)),
            $services,
        );

        $this->state->start($runs);

        return [
            'running'       => true,
            'service_count' => count($runs),
            'message'       => 'Simulation started',
        ];
    }
}
