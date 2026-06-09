<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Traits;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

trait WithScopes
{
    #[Scope]
    public static function isActive(Builder $query): void
    {
        $query->where($query->qualifyColumn('active'), true);
    }

    #[Scope]
    protected function sort(Builder $query): void
    {
        $defaultSort = $this->defaultSort ?? [
            'is_active' => 'desc',
            'name' => 'asc',
        ];

        foreach ($defaultSort as $field => $direction) {
            if ($field === 'sort') {
                $query->orderByRaw('-' . $query->qualifyColumn('sort') . ' DESC');
            } else {
                $query->orderBy($query->qualifyColumn($field), $direction);
            }
        }
    }
}
