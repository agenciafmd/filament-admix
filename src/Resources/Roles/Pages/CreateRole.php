<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Resources\Roles\Pages;

use Agenciafmd\Admix\Resources\Concerns\RedirectBack;
use Agenciafmd\Admix\Resources\Roles\RoleResource;
use Agenciafmd\Admix\Resources\Roles\Schemas\RoleForm;
use Filament\Resources\Pages\CreateRecord;

final class CreateRole extends CreateRecord
{
    use RedirectBack;

    protected static string $resource = RoleResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return RoleForm::mergePermissions($data);
    }
}
