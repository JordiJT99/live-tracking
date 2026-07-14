<?php

use App\Models\Service;
use App\Models\TrackingPoint;
use App\Models\User;

describe('GET /api/v1/tracking/latest', function () {
    it('returns 401 without token', function () {
        $this->getJson('/api/v1/tracking/latest')->assertUnauthorized();
    });

    it('returns empty array when no tracking data exists', function () {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/tracking/latest')
            ->assertOk()
            ->assertExactJson([]);
    });

    it('returns one TrackingPoint per service — the most recent', function () {
        $user    = User::factory()->create();
        $service = Service::factory()->create();

        // Create 3 points for the same service; only the last (highest id) must be returned
        TrackingPoint::factory()->create(['service_id' => $service->id, 'latitude' => 41.38]);
        TrackingPoint::factory()->create(['service_id' => $service->id, 'latitude' => 41.39]);
        $latest = TrackingPoint::factory()->create(['service_id' => $service->id, 'latitude' => 41.40]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/tracking/latest')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.service_id', $service->id)
            ->assertJsonPath('0.latitude', 41.40);
    });

    it('returns one point per service when multiple services exist', function () {
        $user     = User::factory()->create();
        $service1 = Service::factory()->create();
        $service2 = Service::factory()->create();

        TrackingPoint::factory()->create(['service_id' => $service1->id]);
        TrackingPoint::factory()->create(['service_id' => $service2->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/tracking/latest')
            ->assertOk()
            ->assertJsonCount(2);

        $serviceIds = collect($response->json())->pluck('service_id')->sort()->values()->all();
        expect($serviceIds)->toEqual([$service1->id, $service2->id]);
    });

    it('returns TrackingPoint schema matching the OpenAPI spec', function () {
        $user    = User::factory()->create();
        $service = Service::factory()->create();
        TrackingPoint::factory()->create(['service_id' => $service->id]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/tracking/latest')
            ->assertOk()
            ->assertJsonStructure([['service_id', 'latitude', 'longitude', 'created_at']]);
    });
});

describe('GET /api/v1/services/{id}/tracking', function () {
    it('returns 401 without token', function () {
        $this->getJson('/api/v1/services/1/tracking')->assertUnauthorized();
    });

    it('returns full history for a service', function () {
        $user    = User::factory()->create();
        $service = Service::factory()->create();
        TrackingPoint::factory()->count(5)->create(['service_id' => $service->id]);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/services/{$service->id}/tracking")
            ->assertOk()
            ->assertJsonCount(5);
    });

    it('supports after_id for incremental polling', function () {
        $user    = User::factory()->create();
        $service = Service::factory()->create();

        $points = TrackingPoint::factory()->count(5)->create(['service_id' => $service->id]);
        $pivot  = $points->get(2)->id; // after the 3rd point

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/services/{$service->id}/tracking?after_id={$pivot}")
            ->assertOk()
            ->assertJsonCount(2); // only the last 2 points
    });

    it('returns 404 for non-existent service', function () {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/services/9999/tracking')
            ->assertNotFound();
    });
});
