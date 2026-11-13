<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Resources\Organizations\Pages;

use Agenciafmd\Admix\Resources\Concerns\RedirectBack;
use Agenciafmd\Admix\Resources\Organizations\OrganizationResource;
use Filament\Resources\Pages\CreateRecord;
use Override;

final class CreateOrganization extends CreateRecord
{
    use RedirectBack;

    #[Override]
    protected static string $resource = OrganizationResource::class;
}
