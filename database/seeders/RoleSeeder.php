<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Database\Seeders;

use Agenciafmd\Admix\Models\Role;
use Illuminate\Database\Seeder;

final class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::factory()
            ->count(3)
            ->create();
    }
}
