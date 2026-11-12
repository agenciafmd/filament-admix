<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Resources\Users\Pages;

use Agenciafmd\Admix\Models\Team;
use Agenciafmd\Admix\Models\User;
use Agenciafmd\Admix\Resources\Concerns\RedirectBack;
use Agenciafmd\Admix\Resources\Users\UserResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * TODO: enviar verificação de e-mail no afterCreate()
 * https://filamentmastery.com/articles/email-verification-in-filament-userresource-filters-and-actions
 */
final class CreateUser extends CreateRecord
{
    use RedirectBack;

    #[Override]
    protected static string $resource = UserResource::class;

    /**
     * Users that are not administrators do not choose the role: the new user gets the role of who created it.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    #[Override]
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $creator = auth()->user();

        if ($creator instanceof User && ! $creator->isAdmin()) {
            $data['role_id'] = $creator->role_id;
        }

        return $data;
    }

    /**
     * With the tenancy enabled, the user joins the current team. An e-mail that already
     * exists adds that user to the team, without changing their data.
     *
     * @param  array<string, mixed>  $data
     */
    #[Override]
    protected function handleRecordCreation(array $data): Model
    {
        $team = Filament::getTenant();

        if (! Team::isTenancyEnabled() || ! $team instanceof Team) {
            return parent::handleRecordCreation($data);
        }

        $user = User::query()
            ->withTrashed()
            ->where('email', $data['email'] ?? null)
            ->first();

        if ($user?->trashed()) {
            $user->restore();
        }

        $user ??= parent::handleRecordCreation($data);

        if ($user instanceof User) {
            $user->teams()->syncWithoutDetaching([$team->getKey()]);
        }

        return $user;
    }
}
