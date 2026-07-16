<?php

declare(strict_types=1);

namespace Simulator\Infrastructure\Http;

use Psr\Http\Message\ServerRequestInterface;
use React\Http\Message\Response;
use Simulator\Application\GenerateServicesHandler;
use Simulator\Application\SimulationState;
use Simulator\Application\StartSimulationHandler;
use Simulator\Application\StopSimulationHandler;

final class Router
{
    public function __construct(
        private readonly GenerateServicesHandler $generateHandler,
        private readonly StartSimulationHandler  $startHandler,
        private readonly StopSimulationHandler   $stopHandler,
        private readonly SimulationState         $state,
    ) {}

    public function __invoke(ServerRequestInterface $request): Response
    {
        $method = $request->getMethod();
        $path   = $request->getUri()->getPath();

        return match (true) {
            $method === 'GET'  && $path === '/health'            => $this->health(),
            $method === 'POST' && $path === '/generate'          => $this->generate($request),
            $method === 'POST' && $path === '/simulation/start'  => $this->start(),
            $method === 'POST' && $path === '/simulation/stop'   => $this->stop(),
            $method === 'GET'  && $path === '/simulation/status' => $this->status(),
            default                                              => $this->notFound($path),
        };
    }

    private function health(): Response
    {
        return $this->json(['status' => 'ok']);
    }

    private function generate(ServerRequestInterface $request): Response
    {
        $body  = json_decode((string) $request->getBody(), true) ?? [];
        $count = isset($body['count']) ? (int) $body['count'] : 0;

        if ($count < 1 || $count > 50) {
            return $this->json(['error' => 'count must be between 1 and 50'], 422);
        }

        try {
            // Top-level array of ServiceSummary — matches OpenAPI /services/generate 201.
            $services = $this->generateHandler->handle($count);
            return $this->json($services, 201);
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    private function start(): Response
    {
        try {
            $result = $this->startHandler->handle();
            return $this->json($result);
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    private function stop(): Response
    {
        return $this->json($this->stopHandler->handle());
    }

    private function status(): Response
    {
        // SimulationStatus per OpenAPI: {running, service_count?}
        return $this->json([
            'running'       => $this->state->isRunning(),
            'service_count' => $this->state->isRunning() ? $this->state->activeCount() : null,
        ]);
    }

    private function notFound(string $path): Response
    {
        return $this->json(['error' => "Not found: {$path}"], 404);
    }

    private function json(array $data, int $status = 200): Response
    {
        return new Response(
            $status,
            ['Content-Type' => 'application/json'],
            json_encode($data),
        );
    }
}
