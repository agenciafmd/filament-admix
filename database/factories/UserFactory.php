<?php

namespace Agenciafmd\Admix\Database\Factories;

use Agenciafmd\Admix\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'is_active' => true,
            'name' => fake()->name,
            'email' => fake()->unique()->safeEmail,
            'password' => 'secret',
        ];
    }
}
