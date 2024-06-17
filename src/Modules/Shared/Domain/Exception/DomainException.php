<?php

namespace App\Modules\Shared\Domain\Exception;

use Exception;

abstract class DomainException extends Exception
{
    protected static array $codes = [
        'BAD_REQUEST' => 400,
        'NOT_FOUND' => 404,
        'UNPROCESSABLE_ENTITY' => 422,
        'UNAUTHORIZED' => 401,
        'FORBIDDEN' => 403,
    ];
}
