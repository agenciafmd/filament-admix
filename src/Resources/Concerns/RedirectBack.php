<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Resources\Concerns;

trait RedirectBack
{
    protected function getRedirectUrl(): string
    {
        $previousUrl = property_exists($this, 'previousUrl') ? $this->previousUrl : null;

        if (is_string($previousUrl) && $previousUrl !== '') {
            return $previousUrl;
        }

        return $this->getResourceUrl('index');
    }
}
