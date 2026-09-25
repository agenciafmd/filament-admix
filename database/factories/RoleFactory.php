<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Database\Factories;

use Agenciafmd\Admix\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
final class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        return [
            'is_active' => true,
            'name' => fake()->unique()->jobTitle(),
            'permissions' => [],
        ];
    }

    public function inactive(): self
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    public function withPermissions(array $permissions): self
    {
        return $this->state(fn (): array => [
            'permissions' => $permissions,
        ]);
    }
}
