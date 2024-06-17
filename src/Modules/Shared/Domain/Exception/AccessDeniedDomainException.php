<?php

namespace App\Modules\Shared\Domain\Exception;

class AccessDeniedDomainException extends DomainException
{
    public function __construct(string $description)
    {
        parent::__construct($description, self::$codes['FORBIDDEN']);
    }
}
