<?php
declare(strict_types=1);

namespace App\Modules\User\Application\LogoutAuthUser;

use App\Modules\Shared\Application\Contract\ResponseInterface;
use App\Modules\User\Domain\User;

class LogoutAuthUserResponse implements ResponseInterface
{
    public User $user;
}
