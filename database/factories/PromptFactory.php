<?php

namespace Database\Factories;

use App\Models\Prompt;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Prompt>
 */
class PromptFactory extends Factory
{
    protected $model = Prompt::class;

    public function definition(): array
    {
        $key = 'prompt_'.Str::random(6);

        return [
            'key' => $key,
            'title' => 'Prompt '.$this->faker->words(2, true),
            'body' => $this->faker->sentence(12),
            'config' => [
                'temperature' => 0.7,
                'top_p' => 0.9,
                'top_k' => 40,
                'max_tokens' => 512,
            ],
        ];
    }
}
