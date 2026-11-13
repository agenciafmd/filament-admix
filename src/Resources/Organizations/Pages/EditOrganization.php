<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Resources\Organizations\Pages;

use Agenciafmd\Admix\Models\Organization;
use Agenciafmd\Admix\Resources\Concerns\RedirectBack;
use Agenciafmd\Admix\Resources\Organizations\OrganizationResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Override;

final class EditOrganization extends EditRecord
{
    use RedirectBack;

    #[Override]
    protected static string $resource = OrganizationResource::class;

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

        if ($record instanceof Organization && $record->trashed()) {
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
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
