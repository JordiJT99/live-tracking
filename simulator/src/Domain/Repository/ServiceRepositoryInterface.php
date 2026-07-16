<?php

declare(strict_types=1);

namespace Simulator\Domain\Repository;

use Simulator\Domain\Model\Service;

interface ServiceRepositoryInterface
{
    /** Persists a service and returns it with its assigned id. */
    public function save(Service $service): Service;

    /** @return Service[] */
    public function findAll(): array;

    /** Number of services already stored — used as the generation offset. */
    public function count(): int;
}
