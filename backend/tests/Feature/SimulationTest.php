<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;

// SimulatorClient uses Http::fake() — no real microservice needed in tests.

describe('POST /api/v1/simulation/start', function () {
    it('returns 401 without token', function () {
        $this->postJson('/api/v1/simulation/start')->assertUnauthorized();
    });

    it('delegates to the simulator and returns SimulationStatus', function () {
        $user = User::factory()->create();

        Http::fake([
            '*/simulation/start' => Http::response(['running' => true, 'service_count' => 3], 200),
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/simulation/start')
            ->assertOk()
            ->assertJson(['running' => true])
            ->assertJsonStructure(['running']);

        Http::assertSent(fn ($r) => str_contains($r->url(), '/simulation/start'));
    });
});

describe('POST /api/v1/simulation/stop', function () {
    it('returns 401 without token', function () {
        $this->postJson('/api/v1/simulation/stop')->assertUnauthorized();
    });

    it('delegates to the simulator and returns running=false', function () {
        $user = User::factory()->create();

        Http::fake([
            '*/simulation/stop' => Http::response(['running' => false, 'service_count' => null], 200),
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/simulation/stop')
            ->assertOk()
            ->assertJson(['running' => false]);
    });
});

describe('GET /api/v1/simulation/status', function () {
    it('returns 401 without token', function () {
        $this->getJson('/api/v1/simulation/status')->assertUnauthorized();
    });

    it('returns current SimulationStatus', function () {
        $user = User::factory()->create();

        Http::fake([
            '*/simulation/status' => Http::response(['running' => false, 'service_count' => null], 200),
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/simulation/status')
            ->assertOk()
            ->assertJsonStructure(['running']);
    });
});
