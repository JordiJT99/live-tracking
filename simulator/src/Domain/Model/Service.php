<?php

declare(strict_types=1);

namespace Simulator\Domain\Model;

final class Service
{
    public function __construct(
        public readonly int    $id,
        public readonly string $name,
        public readonly \DateTimeImmutable $startTime,
        public readonly \DateTimeImmutable $endTime,
        public readonly Route  $route,
        public readonly string $encodedPolyline,
    ) {}
}
