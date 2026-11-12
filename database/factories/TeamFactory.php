<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Database\Factories;

use Agenciafmd\Admix\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/**
 * @extends Factory<Team>
 */
final class TeamFactory extends Factory
{
    #[Override]
    protected $model = Team::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'is_active' => true,
            'name' => $name,
            'slug' => str($name)->slug()->toString(),
        ];
    }

    public function inactive(): self
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }
}
