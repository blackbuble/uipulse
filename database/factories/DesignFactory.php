<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Design>
 */
class DesignFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name' => ucfirst($this->faker->words(2, true)),
            'figma_url' => 'https://www.figma.com/file/' . $this->faker->uuid,
            'figma_file_key' => $this->faker->uuid,
            'figma_node_id' => $this->faker->numberBetween(1, 100) . ':' . $this->faker->numberBetween(1, 1000),
            'status' => 'completed',
            'metadata' => [
                'width' => 1920,
                'height' => 1080,
                'last_synced' => now()->toIso8601String(),
            ],
        ];
    }
}
