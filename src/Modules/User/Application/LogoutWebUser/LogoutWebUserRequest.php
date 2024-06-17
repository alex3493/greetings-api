<?php
declare(strict_types=1);

namespace App\Modules\User\Application\LogoutWebUser;

use App\Modules\Shared\Application\Contract\RequestInterface;

class LogoutWebUserRequest implements RequestInterface
{
    public string $userId;

    public function __construct(?string $userId)
    {
        $this->userId = $userId;
    }
}
