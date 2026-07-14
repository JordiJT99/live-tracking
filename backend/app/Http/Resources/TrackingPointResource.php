<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrackingPointResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'service_id' => $this->service_id,
            'latitude'   => (float) $this->latitude,
            'longitude'  => (float) $this->longitude,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
