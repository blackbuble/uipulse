<?php

namespace Database\Factories;

use App\Models\Design;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AiAnalysis>
 */
class AiAnalysisFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'design_id' => Design::factory(),
            'type' => $this->faker->randomElement(['accessibility', 'responsiveness', 'code_gen']),
            'provider' => 'openai',
            'model_name' => 'gpt-4-turbo',
            'status' => 'completed',
            'results' => [
                'score' => $this->faker->numberBetween(70, 95),
                'findings' => $this->faker->sentences(3),
                'recommendations' => $this->faker->sentences(2),
            ],
            'prompt' => 'Analyze this UI design for ' . $this->faker->word(),
        ];
    }
}
