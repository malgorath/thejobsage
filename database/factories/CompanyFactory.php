<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'website' => 'https://'.$this->faker->domainName(),
            'description' => $this->faker->paragraph(),
        ];
    }
}
