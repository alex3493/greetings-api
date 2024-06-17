<?php
declare(strict_types=1);

namespace App\Modules\User\Application\LogoutAuthUser;

use App\Modules\Shared\Application\Contract\RequestInterface;

class LogoutAuthUserRequest implements RequestInterface
{
    public string $tokenId;

    public function __construct(?string $tokenId)
    {
        $this->tokenId = $tokenId;
    }
}
