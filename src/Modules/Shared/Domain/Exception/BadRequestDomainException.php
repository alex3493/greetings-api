<?php

namespace App\Modules\Shared\Domain\Exception;

class BadRequestDomainException extends DomainException
{
    public function __construct(string $description)
    {
        parent::__construct($description, self::$codes['BAD_REQUEST']);
    }
}
