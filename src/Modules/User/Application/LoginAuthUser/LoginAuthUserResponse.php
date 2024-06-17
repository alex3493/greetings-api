<?php
declare(strict_types=1);

namespace App\Modules\User\Application\LoginAuthUser;

use App\Modules\Shared\Application\Contract\ResponseInterface;
use App\Modules\User\Domain\User;

class LoginAuthUserResponse implements ResponseInterface
{
    public string $token;

    public User $user;
}
