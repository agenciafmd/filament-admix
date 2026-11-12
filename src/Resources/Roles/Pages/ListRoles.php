<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Resources\Roles\Pages;

use Agenciafmd\Admix\Resources\Roles\RoleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Override;

final class ListRoles extends ListRecords
{
    #[Override]
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
