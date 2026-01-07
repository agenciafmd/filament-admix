<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Database\Factories;

use Agenciafmd\Admix\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

final class UserFactory extends Factory
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
