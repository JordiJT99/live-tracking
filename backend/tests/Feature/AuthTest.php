<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

// All responses must match the OpenAPI spec shapes in docs/openapi.yaml

describe('POST /api/v1/auth/login', function () {
    it('returns token and user on valid credentials', function () {
        User::factory()->create([
            'email'    => 'demo@demo.com',
            'password' => Hash::make('password'),
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email'    => 'demo@demo.com',
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']])
            ->assertJsonPath('user.email', 'demo@demo.com');
    });

    it('returns 401 on wrong password', function () {
        User::factory()->create(['email' => 'demo@demo.com']);

        $this->postJson('/api/v1/auth/login', [
            'email'    => 'demo@demo.com',
            'password' => 'wrong-password',
        ])->assertUnauthorized();
    });

    it('validates email format', function () {
        $this->postJson('/api/v1/auth/login', [
            'email'    => 'not-an-email',
            'password' => 'password',
        ])->assertUnprocessable();
    });

    it('requires both fields', function () {
        $this->postJson('/api/v1/auth/login', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    });
});

describe('POST /api/v1/auth/logout', function () {
    it('revokes the token and returns 204', function () {
        $user  = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/auth/logout')
            ->assertNoContent();
    });

    it('returns 401 without token', function () {
        $this->postJson('/api/v1/auth/logout')->assertUnauthorized();
    });
});

describe('GET /api/v1/auth/me', function () {
    it('returns current user', function () {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonStructure(['id', 'name', 'email'])
            ->assertJsonPath('email', $user->email);
    });

    it('returns 401 without token', function () {
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
    });
});
