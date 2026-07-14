<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateServicesRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use App\Services\SimulatorClient;
use Illuminate\Http\JsonResponse;

class ServiceController extends Controller
{
    public function __construct(private readonly SimulatorClient $simulator) {}

    /** GET /api/v1/services — ServiceSummary list (no polyline) */
    public function index(): JsonResponse
    {
        $services = Service::orderBy('id')->get();

        return response()->json(
            $services->map(fn ($s) => ServiceResource::summary($s)->toArray(request()))
        );
    }

    /** GET /api/v1/services/{service} — ServiceDetail (with polyline) */
    public function show(Service $service): JsonResponse
    {
        return response()->json(ServiceResource::detail($service)->toArray(request()));
    }

    /** POST /api/v1/services/generate — delegates to microservice */
    public function generate(GenerateServicesRequest $request): JsonResponse
    {
        $created = $this->simulator->generateServices($request->integer('count'));

        return response()->json($created, 201);
    }
}
