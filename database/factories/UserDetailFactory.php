<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserDetailFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'address' => $this->faker->address(),
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->email(),
            'linkedin' => 'https://linkedin.com/in/'.$this->faker->userName(),
            'website' => 'https://'.$this->faker->domainName(),
            'github' => 'https://github.com/'.$this->faker->userName(),
            'other_contact' => $this->faker->optional()->email(),
        ];
    }
}
