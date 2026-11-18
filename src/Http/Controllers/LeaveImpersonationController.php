<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Http\Controllers;

use Agenciafmd\Admix\Models\User;
use Agenciafmd\Admix\Resources\Users\UserResource;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;

/**
 * Takes the administrator back to their own account and to the list of users.
 */
final class LeaveImpersonationController
{
    public function __invoke(): RedirectResponse
    {
        abort_unless(User::isImpersonating(), 403);

        $impersonator = User::leaveImpersonation();

        if (! $impersonator instanceof User) {
            return redirect()->to(Filament::getLoginUrl() ?? url('/'));
        }

        if (! Filament::hasTenancy()) {
            return redirect()->to(UserResource::getUrl('index'));
        }

        $organization = Filament::getUserDefaultTenant($impersonator);

        return redirect()->to($organization === null ? Filament::getUrl() ?? url('/') : UserResource::getUrl('index', tenant: $organization));
    }
}
