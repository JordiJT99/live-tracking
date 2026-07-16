<?php

declare(strict_types=1);

namespace Simulator\Infrastructure\Persistence;

use Simulator\Domain\Model\Coordinate;
use Simulator\Domain\Model\Route;
use Simulator\Domain\Model\Service;
use Simulator\Domain\Repository\ServiceRepositoryInterface;
use Simulator\Domain\Service\PolylineCodec;

final class PdoServiceRepository implements ServiceRepositoryInterface
{
    public function __construct(
        private readonly \PDO          $pdo,
        private readonly PolylineCodec $codec,
    ) {}

    public function save(Service $service): Service
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO services (name, start_time, end_time, polyline, created_at, updated_at)
             VALUES (:name, :start_time, :end_time, :polyline, NOW(), NOW())'
        );

        $stmt->execute([
            ':name'       => $service->name,
            ':start_time' => $service->startTime->format('Y-m-d H:i:s'),
            ':end_time'   => $service->endTime->format('Y-m-d H:i:s'),
            ':polyline'   => $service->encodedPolyline,
        ]);

        $id = (int) $this->pdo->lastInsertId();

        return new Service(
            id:              $id,
            name:            $service->name,
            startTime:       $service->startTime,
            endTime:         $service->endTime,
            route:           $service->route,
            encodedPolyline: $service->encodedPolyline,
        );
    }

    public function count(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM services')->fetchColumn();
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query('SELECT id, name, start_time, end_time, polyline FROM services ORDER BY id');
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return array_map(function (array $row): Service {
            $coords = $this->codec->decode($row['polyline']);
            $route  = new Route($coords);

            return new Service(
                id:              (int) $row['id'],
                name:            $row['name'],
                startTime:       new \DateTimeImmutable($row['start_time']),
                endTime:         new \DateTimeImmutable($row['end_time']),
                route:           $route,
                encodedPolyline: $row['polyline'],
            );
        }, $rows);
    }
}
