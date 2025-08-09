<?php

namespace Packages\ToxicityFilter\Exceptions;

class ContentTooLongException extends ToxicityFilterException
{
    public function __construct(int $maxLength, int $actualLength)
    {
        parent::__construct("Content is too long. Maximum allowed: {$maxLength} characters, provided: {$actualLength} characters.");
    }
}
