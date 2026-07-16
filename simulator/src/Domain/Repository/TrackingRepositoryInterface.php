<?php

declare(strict_types=1);

namespace Simulator\Domain\Repository;

use Simulator\Domain\Model\Coordinate;

interface TrackingRepositoryInterface
{
    /** Appends a tracking point — never updates. */
    public function append(int $serviceId, Coordinate $position): void;
}
