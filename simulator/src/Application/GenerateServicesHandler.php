<?php

declare(strict_types=1);

namespace Simulator\Application;

use Simulator\Domain\Repository\ServiceRepositoryInterface;
use Simulator\Domain\Repository\TrackingRepositoryInterface;
use Simulator\Domain\Service\ServiceFactory;

final class GenerateServicesHandler
{
    public function __construct(
        private readonly ServiceFactory              $factory,
        private readonly ServiceRepositoryInterface  $serviceRepo,
        private readonly TrackingRepositoryInterface $trackingRepo,
    ) {}

    /**
     * Returns a top-level array of ServiceSummary objects, matching the
     * OpenAPI contract (docs/openapi.yaml → /services/generate 201).
     * No polyline field: that belongs to ServiceDetail (GET /services/{id}).
     *
     * @return array<array{id: int, name: string, start_time: string, end_time: string}>
     */
    public function handle(int $count): array
    {
        $now      = new \DateTimeImmutable();
        $offset   = $this->serviceRepo->count();   // continue the sequence, don't restart
        $services = $this->factory->createMany($count, $now, $offset);
        $result   = [];

        foreach ($services as $service) {
            $saved = $this->serviceRepo->save($service);

            // Seed an initial position at the route start so the bus marker
            // shows on the map immediately, before the simulation is started.
            $this->trackingRepo->append($saved->id, $service->route->pointAtDistance(0.0));

            $result[] = [
                'id'         => $saved->id,
                'name'       => $saved->name,
                'start_time' => $saved->startTime->format('Y-m-d\TH:i:s'),
                'end_time'   => $saved->endTime->format('Y-m-d\TH:i:s'),
            ];
        }

        return $result;
    }
}
