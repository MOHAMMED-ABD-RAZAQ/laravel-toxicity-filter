<?php

namespace Packages\ToxicityFilter\Exceptions;

class ProviderNotFoundException extends ToxicityFilterException
{
    public function __construct(string $provider)
    {
        parent::__construct("Toxicity provider '{$provider}' not found or not configured.");
    }
}
