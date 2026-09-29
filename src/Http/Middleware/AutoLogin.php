<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Http\Middleware;

use Agenciafmd\Admix\Models\User;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Logs in automatically on the local environment, according to `filament-admix.auto_login`:
 * an e-mail logs in as that user, `true` logs in as the first active administrator.
 */
final class AutoLogin
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Filament::auth();

        if (app()->isLocal() && $guard->guest() && ($user = $this->user()) instanceof User) {
            $guard->login($user);
        }

        return $next($request);
    }

    private function user(): ?User
    {
        $autoLogin = config('filament-admix.auto_login');

        if (blank($autoLogin) || $autoLogin === false) {
            return null;
        }

        return User::query()
            ->isActive()
            ->when(
                is_string($autoLogin),
                fn (Builder $query): Builder => $query->where('email', $autoLogin),
                fn (Builder $query): Builder => $query->whereNull('role_id')->oldest('id'),
            )
            ->first();
    }
}
