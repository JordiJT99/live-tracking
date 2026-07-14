<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrackingPoint extends Model
{
    use HasFactory;

    protected $table = 'tracking';

    // Append-only — no updates, only inserts (written by the microservice)
    public $timestamps = false;

    protected $fillable = ['service_id', 'latitude', 'longitude', 'created_at'];

    protected function casts(): array
    {
        return [
            'latitude'   => 'float',
            'longitude'  => 'float',
            'created_at' => 'datetime',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
