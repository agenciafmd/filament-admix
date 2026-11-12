<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Resources\Users\Pages;

use Agenciafmd\Admix\Models\User;
use Agenciafmd\Admix\Resources\Concerns\RedirectBack;
use Agenciafmd\Admix\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Override;

final class EditUser extends EditRecord
{
    use RedirectBack;

    #[Override]
    protected static string $resource = UserResource::class;

    /**
     * @var array<int, string>
     */
    #[Override]
    protected $listeners = [
        'auditRestored',
    ];

    public function getRelationManagers(): array
    {
        $record = $this->getRecord();

        if ($record instanceof User && $record->trashed()) {
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
            DeleteAction::make()
                ->using(fn (User $record): bool => UserResource::deleteRecord($record)),
        ];
    }
}
