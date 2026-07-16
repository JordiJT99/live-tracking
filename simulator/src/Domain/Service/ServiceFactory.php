<?php

declare(strict_types=1);

namespace Simulator\Domain\Service;

use Simulator\Domain\Model\Route;
use Simulator\Domain\Model\Service;
use Simulator\Domain\RouteCatalog;

/**
 * Generates fictional services from the pre-baked route catalog.
 *
 * The caller passes an $offset (how many services already exist) so the
 * sequence continues across calls instead of restarting at route 0 every time.
 * Beyond the catalog size the route is reversed on odd cycles, so repeated
 * routes are not visually identical.
 */
final class ServiceFactory
{
    private static array $BUS_MODELS = [
        'Iveco Urbanway 12',
        'Mercedes-Benz Citaro',
        'Solaris Urbino 12',
        'Volvo 7900',
        'MAN Lion\'s City',
        'VDL Citea SLE',
        'Scania Citywide',
        'Heuliez GX 337',
    ];

    public function __construct(private readonly PolylineCodec $codec) {}

    /**
     * @param int $offset  number of services already stored, so numbering and
     *                     route selection continue instead of restarting at 0
     * @return Service[]   without persisted IDs (id = 0, assigned by repo after save)
     */
    public function createMany(int $count, \DateTimeImmutable $now, int $offset = 0): array
    {
        if ($count < 1 || $count > 50) {
            throw new \InvalidArgumentException('count must be between 1 and 50');
        }

        $catalog  = RouteCatalog::all();
        $total    = count($catalog);
        $services = [];

        for ($i = 0; $i < $count; $i++) {
            $index = $offset + $i;                 // global index, continues across calls
            $entry = $catalog[$index % $total];
            $cycle = intdiv($index, $total);

            $route = $entry['route'];
            $label = $entry['name'];

            // Beyond the catalog, reverse the route on odd cycles so repeats differ.
            if ($cycle % 2 === 1) {
                $route = new Route(array_reverse($route->points()));
                $label .= ' (inv)';
            }

            $model     = self::$BUS_MODELS[$index % count(self::$BUS_MODELS)];
            $startMins = 6 * 60 + ($index * 17) % (14 * 60); // spread from 06:00 over 14 h
            $durationMins = 45 + ($index * 7) % 75;           // 45–120 min windows

            $startTime = $now->setTime(
                (int) ($startMins / 60),
                $startMins % 60,
                0,
            );
            $endTime = $startTime->modify("+{$durationMins} minutes");

            $name    = sprintf('BUS-%03d %s (%s)', $index + 1, $label, $model);
            $encoded = $this->codec->encode($route->points());

            $services[] = new Service(
                id:              0,
                name:            $name,
                startTime:       $startTime,
                endTime:         $endTime,
                route:           $route,
                encodedPolyline: $encoded,
            );
        }

        return $services;
    }
}
