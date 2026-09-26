<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Resources\Roles\Pages;

use Agenciafmd\Admix\Models\Role;
use Agenciafmd\Admix\Resources\Concerns\RedirectBack;
use Agenciafmd\Admix\Resources\Roles\RoleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditRole extends EditRecord
{
    use RedirectBack;

    protected static string $resource = RoleResource::class;

    /**
     * @var array<int, string>
     */
    protected $listeners = [
        'auditRestored',
    ];

    public function getRelationManagers(): array
    {
        $record = $this->getRecord();

        if ($record instanceof Role && $record->trashed()) {
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
}
