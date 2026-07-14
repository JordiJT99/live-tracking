<?php

use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\Http;

describe('GET /api/v1/services', function () {
    it('returns 401 without token', function () {
        $this->getJson('/api/v1/services')->assertUnauthorized();
    });

    it('returns empty array when no services exist', function () {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/services')
            ->assertOk()
            ->assertExactJson([]);
    });

    it('returns ServiceSummary list without polyline', function () {
        $user    = User::factory()->create();
        $service = Service::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/services')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonStructure([['id', 'name', 'start_time', 'end_time']]);

        // polyline must NOT be present in summary (spec: ServiceSummary omits it)
        expect($response->json('0'))->not->toHaveKey('polyline');
    });

    it('returns services ordered by id', function () {
        $user = User::factory()->create();
        Service::factory()->count(3)->create();

        $ids = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/services')
            ->assertOk()
            ->collect()
            ->pluck('id')
            ->all();

        expect($ids)->toBe(array_values($ids)); // already sorted
        expect($ids)->toEqual(collect($ids)->sort()->values()->all());
    });
});

describe('GET /api/v1/services/{id}', function () {
    it('returns 401 without token', function () {
        $this->getJson('/api/v1/services/1')->assertUnauthorized();
    });

    it('returns ServiceDetail with polyline', function () {
        $user    = User::factory()->create();
        $service = Service::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/services/{$service->id}")
            ->assertOk()
            ->assertJsonStructure(['id', 'name', 'start_time', 'end_time', 'polyline'])
            ->assertJsonPath('id', $service->id)
            ->assertJsonPath('polyline', $service->polyline);
    });

    it('returns 404 for non-existent service', function () {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/services/9999')
            ->assertNotFound();
    });
});

describe('POST /api/v1/services/generate', function () {
    it('returns 401 without token', function () {
        $this->postJson('/api/v1/services/generate', ['count' => 3])->assertUnauthorized();
    });

    it('creates services via the simulator and returns them', function () {
        $user = User::factory()->create();

        Http::fake([
            '*/generate' => Http::response([
                ['id' => 1, 'name' => 'Línea 1 – La Rambla', 'start_time' => '2025-01-13T07:00:00', 'end_time' => '2025-01-13T17:30:00'],
                ['id' => 2, 'name' => 'Línea 2 – Diagonal',   'start_time' => '2025-01-13T08:00:00', 'end_time' => '2025-01-13T18:30:00'],
            ], 201),
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/services/generate', ['count' => 2])
            ->assertCreated()
            ->assertJsonCount(2)
            ->assertJsonStructure([['id', 'name', 'start_time', 'end_time']]);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/generate')
            && $request->data()['count'] === 2);
    });

    it('validates count is required and integer', function () {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/services/generate', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['count']);
    });

    it('validates count minimum is 1', function () {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/services/generate', ['count' => 0])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['count']);
    });

    it('validates count maximum is 50', function () {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/services/generate', ['count' => 51])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['count']);
    });
});
