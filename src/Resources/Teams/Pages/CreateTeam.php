<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Resources\Teams\Pages;

use Agenciafmd\Admix\Resources\Concerns\RedirectBack;
use Agenciafmd\Admix\Resources\Teams\TeamResource;
use Filament\Resources\Pages\CreateRecord;
use Override;

final class CreateTeam extends CreateRecord
{
    use RedirectBack;

    #[Override]
    protected static string $resource = TeamResource::class;
}
