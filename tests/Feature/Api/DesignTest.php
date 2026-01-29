<?php

use App\Models\User;
use App\Models\Project;
use App\Models\Design;
use Laravel\Sanctum\Sanctum;

test('authenticated user can store design from figma', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    Sanctum::actingAs($user);

    $response = $this->postJson("/api/projects/{$project->id}/designs", [
        'name' => 'Figma Frame 1',
        'figma_url' => 'https://www.figma.com/file/test',
        'figma_file_key' => 'test-key',
        'metadata' => ['width' => 100],
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('design.name', 'Figma Frame 1');

    $this->assertDatabaseHas('designs', ['name' => 'Figma Frame 1']);
});

test('unauthenticated user cannot store design', function () {
    $project = Project::factory()->create();

    $response = $this->postJson("/api/projects/{$project->id}/designs", [
        'name' => 'Figma Frame 1',
    ]);

    $response->assertStatus(401);
});
