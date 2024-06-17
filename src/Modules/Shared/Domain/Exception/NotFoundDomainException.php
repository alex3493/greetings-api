<?php

namespace App\Modules\Shared\Domain\Exception;

class NotFoundDomainException extends DomainException
{
    public function __construct(string $description)
    {
        parent::__construct($description, self::$codes['NOT_FOUND']);
    }
}
