<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Resources\Users\Pages;

use Agenciafmd\Admix\Resources\Concerns\RedirectBack;
use Agenciafmd\Admix\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditUser extends EditRecord
{
    use RedirectBack;

    protected static string $resource = UserResource::class;

    protected $listeners = [
        'auditRestored',
    ];

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
