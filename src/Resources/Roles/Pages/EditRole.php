<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Resources\Roles\Pages;

use Agenciafmd\Admix\Resources\Concerns\RedirectBack;
use Agenciafmd\Admix\Resources\Roles\RoleResource;
use Agenciafmd\Admix\Resources\Roles\Schemas\RoleForm;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditRole extends EditRecord
{
    use RedirectBack;

    protected static string $resource = RoleResource::class;

    protected $listeners = [
        'auditRestored',
    ];

    public function getRelationManagers(): array
    {
        if ($this->record->trashed()) {
            return [];
        }

        return parent::getRelationManagers();
    }

    public function auditRestored(): void
    {
        $this->fillForm();
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return RoleForm::mergePermissions($data);
    }
}
