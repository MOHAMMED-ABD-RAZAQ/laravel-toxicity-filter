<?php

namespace Packages\ToxicityFilter\Exceptions;

class ProviderApiException extends ToxicityFilterException
{
    public function __construct(string $provider, string $message, int $code = 0, ?\Throwable $previous = null)
    {
        $message = "API error from {$provider}: {$message}";
        parent::__construct($message, $code, $previous);
    }
}
