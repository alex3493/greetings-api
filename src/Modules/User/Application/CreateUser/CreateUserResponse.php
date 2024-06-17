<?php
declare(strict_types=1);

namespace App\Modules\User\Application\CreateUser;

use App\Modules\Shared\Application\Contract\ResponseInterface;
use App\Modules\User\Domain\User;

class CreateUserResponse implements ResponseInterface
{
    public User $user;
}
