<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Models\Scopes;

use Agenciafmd\Admix\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * @implements Scope<User>
 */
final class AdmixTypeScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder->where($builder->qualifyColumn('type'), 'admix');
    }
}
