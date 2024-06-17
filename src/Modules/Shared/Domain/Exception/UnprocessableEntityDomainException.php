<?php

namespace App\Modules\Shared\Domain\Exception;

class UnprocessableEntityDomainException extends DomainException
{
    public function __construct(string $description)
    {
        parent::__construct($description, self::$codes['UNPROCESSABLE_ENTITY']);
    }
}
