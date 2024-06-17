<?php
declare(strict_types=1);

namespace App\Modules\User\Application\LogoutWebUser;

use App\Modules\Shared\Application\Contract\ResponseInterface;

class LogoutWebUserResponse implements ResponseInterface
{
    public string $message;
}
