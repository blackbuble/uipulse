<?php

use App\Models\User;
use App\Models\Project;
use Laravel\Sanctum\Sanctum;

test('authenticated user can list their projects', function () {
    $user = User::factory()->has(Project::factory()->count(3))->create();

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/projects');

    $response->assertStatus(200)
        ->assertJsonCount(3);
});

test('unauthenticated user cannot list projects', function () {
    $response = $this->getJson('/api/projects');

    $response->assertStatus(401);
});
