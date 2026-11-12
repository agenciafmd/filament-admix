<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Resources\Table\Columns;

use Filament\Tables\Columns\Column;
use Override;

final class RatingColumn extends Column
{
    #[Override]
    protected string $view = 'filament-admix::filament.tables.columns.rating';
}
