<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Append-only table — the microservice inserts, the backend reads.
        // Composite index on (service_id, id) supports MAX(id) GROUP BY service_id
        // as an index-only scan without touching the data pages.
        Schema::create('tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 11, 7);
            $table->timestamp('created_at')->useCurrent();

            // composite index for MAX(id) GROUP BY service_id;
            // if the table grows to millions of rows, add a covering index on (service_id, id, lat, lon).
            $table->index(['service_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracking');
    }
};
