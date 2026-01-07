<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Resources\Users\Pages;

use Agenciafmd\Admix\Resources\Users\UserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
