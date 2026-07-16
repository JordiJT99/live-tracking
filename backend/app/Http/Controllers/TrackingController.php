<?php

namespace App\Http\Controllers;

use App\Http\Resources\TrackingPointResource;
use App\Models\Service;
use App\Models\TrackingPoint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    /**
     * GET /api/v1/tracking/latest
     * One TrackingPoint per service — the most recent one.
     * Uses MAX(id) GROUP BY service_id to avoid a full scan.
     * Subquery index scan on (service_id, id); upgrade to covering index if table grows.
     */
    public function latest(): JsonResponse
    {
        $points = TrackingPoint::whereIn('id', function ($query) {
            $query->selectRaw('MAX(id)')
                ->from('tracking')
                ->groupBy('service_id');
        })->get();

        return response()->json(
            $points->map(fn ($p) => (new TrackingPointResource($p))->toArray(request()))
        );
    }

    /**
     * GET /api/v1/services/{service}/tracking
     * Full history for one service, optionally incremental via after_id.
     */
    public function history(Request $request, Service $service): JsonResponse
    {
        $query = $service->trackingPoints()->orderBy('id');

        if ($request->filled('after_id')) {
            $query->where('id', '>', $request->integer('after_id'));
        }

        return response()->json(
            $query->get()->map(fn ($p) => (new TrackingPointResource($p))->toArray(request()))
        );
    }
}
