<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Database\Seeders;

use Agenciafmd\Admix\Database\Factories\RoleFactory;
use Illuminate\Database\Seeder;

final class RoleSeeder extends Seeder
{
    public function run(): void
    {
        RoleFactory::new()
            ->count(3)
            ->create();
    }
}
