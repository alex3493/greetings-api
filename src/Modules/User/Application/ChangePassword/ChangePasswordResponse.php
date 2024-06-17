<?php
declare(strict_types=1);

namespace App\Modules\User\Application\ChangePassword;

use App\Modules\Shared\Application\Contract\ResponseInterface;
use App\Modules\User\Domain\User;

class ChangePasswordResponse implements ResponseInterface
{
    public User $user;
}
