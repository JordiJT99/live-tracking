<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
{
    /**
     * Whether to include the polyline field.
     * False for list endpoints (ServiceSummary), true for detail (ServiceDetail).
     */
    public bool $withPolyline = false;

    public static function summary($resource): self
    {
        $instance = new self($resource);
        $instance->withPolyline = false;
        return $instance;
    }

    public static function detail($resource): self
    {
        $instance = new self($resource);
        $instance->withPolyline = true;
        return $instance;
    }

    public function toArray(Request $request): array
    {
        $data = [
            'id'         => $this->id,
            'name'       => $this->name,
            'start_time' => $this->start_time?->toIso8601String(),
            'end_time'   => $this->end_time?->toIso8601String(),
        ];

        if ($this->withPolyline) {
            $data['polyline'] = $this->polyline;
        }

        return $data;
    }
}
