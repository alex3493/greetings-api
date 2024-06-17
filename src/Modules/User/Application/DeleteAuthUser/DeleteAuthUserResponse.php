<?php
declare(strict_types=1);

namespace App\Modules\User\Application\DeleteAuthUser;

use App\Modules\Shared\Application\Contract\ResponseInterface;

class DeleteAuthUserResponse implements ResponseInterface
{
    public string $message;
}
