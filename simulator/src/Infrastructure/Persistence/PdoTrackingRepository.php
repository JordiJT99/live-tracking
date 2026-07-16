<?php

declare(strict_types=1);

namespace Simulator\Infrastructure\Persistence;

use Simulator\Domain\Model\Coordinate;
use Simulator\Domain\Repository\TrackingRepositoryInterface;

final class PdoTrackingRepository implements TrackingRepositoryInterface
{
    public function __construct(private readonly \PDO $pdo) {}

    public function append(int $serviceId, Coordinate $position): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO tracking (service_id, latitude, longitude, created_at)
             VALUES (:service_id, :lat, :lng, NOW())'
        );

        $stmt->execute([
            ':service_id' => $serviceId,
            ':lat'        => round($position->lat, 7),
            ':lng'        => round($position->lng, 7),
        ]);
    }
}
