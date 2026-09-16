<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Resources\Users\Pages;

use Agenciafmd\Admix\Resources\Concerns\RedirectBack;
use Agenciafmd\Admix\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * TODO: enviar verificação de e-mail no afterCreate()
 * https://filamentmastery.com/articles/email-verification-in-filament-userresource-filters-and-actions
 */
final class CreateUser extends CreateRecord
{
    use RedirectBack;

    protected static string $resource = UserResource::class;
}
