<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => $this->faker->jobTitle(),
            'company' => $this->faker->company(),
            'description' => $this->faker->paragraphs(3, true),
            'location' => $this->faker->city().', '.$this->faker->stateAbbr(),
            'requirements' => [
                $this->faker->sentence(),
                $this->faker->sentence(),
                $this->faker->sentence(),
            ],
            'company_id' => Company::factory(),
        ];
    }
}
