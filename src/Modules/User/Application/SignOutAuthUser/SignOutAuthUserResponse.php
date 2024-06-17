<?php
declare(strict_types=1);

namespace App\Modules\User\Application\SignOutAuthUser;

use App\Modules\Shared\Application\Contract\ResponseInterface;
use App\Modules\User\Domain\User;

class SignOutAuthUserResponse implements ResponseInterface
{
    public User $user;
}
