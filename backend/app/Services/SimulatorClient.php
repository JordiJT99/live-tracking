<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * HTTP client for the DDD microservice (ReactPHP simulator).
 * In tests, use Http::fake() to mock responses without a running simulator.
 */
class SimulatorClient
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.simulator.url', 'http://simulator:8001'), '/');
    }

    /** Asks the simulator to create N services and persist them in the DB. */
    public function generateServices(int $count): array
    {
        $response = Http::post("{$this->baseUrl}/generate", ['count' => $count]);

        $response->throw();

        return $response->json();
    }

    /** Starts the GPS simulation loop in the microservice. */
    public function startSimulation(): array
    {
        $response = Http::post("{$this->baseUrl}/simulation/start");

        $response->throw();

        return $response->json();
    }

    /** Stops the GPS simulation loop. */
    public function stopSimulation(): array
    {
        $response = Http::post("{$this->baseUrl}/simulation/stop");

        $response->throw();

        return $response->json();
    }

    /** Returns the current simulation status from the microservice. */
    public function getStatus(): array
    {
        $response = Http::get("{$this->baseUrl}/simulation/status");

        $response->throw();

        return $response->json();
    }
}
