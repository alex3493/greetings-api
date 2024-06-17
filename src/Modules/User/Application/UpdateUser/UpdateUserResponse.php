<?php
declare(strict_types=1);

namespace App\Modules\User\Application\UpdateUser;

use App\Modules\Shared\Application\Contract\ResponseInterface;
use App\Modules\User\Domain\User;

class UpdateUserResponse implements ResponseInterface
{
    public User $user;
}
