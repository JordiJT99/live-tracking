<?php

declare(strict_types=1);

namespace Simulator\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Simulator\Domain\Service\PolylineCodec;
use Simulator\Domain\Service\ServiceFactory;

final class ServiceFactoryTest extends TestCase
{
    private ServiceFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new ServiceFactory(new PolylineCodec());
    }

    public function test_creates_requested_count(): void
    {
        $services = $this->factory->createMany(5, new \DateTimeImmutable());

        self::assertCount(5, $services);
    }

    public function test_all_services_have_valid_routes(): void
    {
        $services = $this->factory->createMany(3, new \DateTimeImmutable());

        foreach ($services as $service) {
            self::assertGreaterThan(0.0, $service->route->totalLength());
            self::assertNotEmpty($service->encodedPolyline);
        }
    }

    public function test_all_services_have_non_empty_names(): void
    {
        $services = $this->factory->createMany(3, new \DateTimeImmutable());

        foreach ($services as $service) {
            self::assertNotEmpty($service->name);
        }
    }

    public function test_start_time_is_before_end_time(): void
    {
        $services = $this->factory->createMany(5, new \DateTimeImmutable());

        foreach ($services as $service) {
            self::assertLessThan($service->endTime, $service->startTime);
        }
    }

    public function test_throws_on_count_below_1(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->factory->createMany(0, new \DateTimeImmutable());
    }

    public function test_throws_on_count_above_50(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->factory->createMany(51, new \DateTimeImmutable());
    }

    public function test_cycles_routes_when_count_exceeds_catalog(): void
    {
        // 17 services with 16 routes in catalog → must cycle without error
        $services = $this->factory->createMany(17, new \DateTimeImmutable());

        self::assertCount(17, $services);
    }

    public function test_offset_continues_numbering(): void
    {
        // offset 16 → next services are BUS-017, BUS-018 (not restarting at BUS-001)
        $services = $this->factory->createMany(2, new \DateTimeImmutable(), 16);

        self::assertStringContainsString('BUS-017', $services[0]->name);
        self::assertStringContainsString('BUS-018', $services[1]->name);
    }

    public function test_second_cycle_produces_reversed_variant(): void
    {
        // index 16 = cycle 1 → route reversed, labelled "(inv)"
        $services = $this->factory->createMany(1, new \DateTimeImmutable(), 16);

        self::assertStringContainsString('(inv)', $services[0]->name);
    }
}
