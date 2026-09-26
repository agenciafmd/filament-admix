<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Traits;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

trait WithScopes
{
    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function isActive(Builder $query): void
    {
        $query->where($query->qualifyColumn('is_active'), true);
    }

    /**
     * Ordena pelo `$defaultSort` do model.
     *
     * A coluna `sort` é ordenada de forma crescente, deixando os nulos por último.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function sort(Builder $query): void
    {
        foreach ($this->defaultSort as $field => $direction) {
            if ($field === 'sort') {
                $query->orderByRaw('-sort DESC');

                continue;
            }

            $query->orderBy($query->qualifyColumn($field), $direction === 'desc' ? 'desc' : 'asc');
        }
    }
}
