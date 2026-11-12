<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Database\Factories;

use Agenciafmd\Admix\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/**
 * @extends Factory<User>
 */
final class UserFactory extends Factory
{
    #[Override]
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'is_active' => true,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'secret',
        ];
    }
}
