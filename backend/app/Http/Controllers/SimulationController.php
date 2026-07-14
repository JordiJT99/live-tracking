<?php

namespace App\Http\Controllers;

use App\Services\SimulatorClient;
use Illuminate\Http\JsonResponse;

class SimulationController extends Controller
{
    public function __construct(private readonly SimulatorClient $simulator) {}

    /** POST /api/v1/simulation/start */
    public function start(): JsonResponse
    {
        $status = $this->simulator->startSimulation();

        return response()->json($status);
    }

    /** POST /api/v1/simulation/stop */
    public function stop(): JsonResponse
    {
        $status = $this->simulator->stopSimulation();

        return response()->json($status);
    }

    /** GET /api/v1/simulation/status */
    public function status(): JsonResponse
    {
        $status = $this->simulator->getStatus();

        return response()->json($status);
    }
}
