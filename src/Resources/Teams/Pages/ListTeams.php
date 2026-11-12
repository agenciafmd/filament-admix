<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Resources\Teams\Pages;

use Agenciafmd\Admix\Resources\Teams\TeamResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Override;

final class ListTeams extends ListRecords
{
    #[Override]
    protected static string $resource = TeamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
