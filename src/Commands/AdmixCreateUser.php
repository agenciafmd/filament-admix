<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Commands;

use Agenciafmd\Admix\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

final class AdmixCreateUser extends Command
{
    protected $signature = 'admix:create-user';

    protected $description = 'Create a admix user';

    public function handle(): void
    {
        $name = text(
            label: 'Name',
            required: true,
        );
        $email = text(
            label: 'Email address',
            required: true,
            validate: fn (string $email): ?string => match (true) {
                ! filter_var($email, FILTER_VALIDATE_EMAIL) => 'The email address must be valid.',
                default => null,
            },
        );
        $password = Hash::make(password(
            label: 'Password',
            required: true,
        ));

        User::query()
            ->updateOrCreate([
                'email' => $email,
            ], [
                'is_active' => true,
                'name' => $name,
                'password' => $password,
                'email_verified_at' => now(),
            ]);

        $this->info('User created!');
        $this->line($name . ' (' . $email . ')' . "\n");
    }
}
