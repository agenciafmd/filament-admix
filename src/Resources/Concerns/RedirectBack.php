<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Resources\Concerns;

trait RedirectBack
{
    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? self::getResource()::getUrl('index');
    }
}
