<?php
declare(strict_types=1);

namespace App\Modules\User\Application\RegisterAuthUser;

use App\Modules\Shared\Application\Contract\ResponseInterface;
use App\Modules\User\Domain\User;

class RegisterAuthUserResponse implements ResponseInterface
{
    public string $token;

    public User $user;
}
