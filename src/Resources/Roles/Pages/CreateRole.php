<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Resources\Roles\Pages;

use Agenciafmd\Admix\Resources\Concerns\RedirectBack;
use Agenciafmd\Admix\Resources\Roles\RoleResource;
use Filament\Resources\Pages\CreateRecord;
use Override;

final class CreateRole extends CreateRecord
{
    use RedirectBack;

    #[Override]
    protected static string $resource = RoleResource::class;
}
