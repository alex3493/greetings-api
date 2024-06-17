<?php
declare(strict_types=1);

namespace App\Modules\User\Application\LoginAuthUser;

use App\Modules\Shared\Application\Contract\RequestInterface;

class LoginAuthUserRequest implements RequestInterface
{
    public string $email;

    public string $password;

    public ?string $deviceName;

    public function __construct(string $email, string $password, ?string $deviceName = null)
    {
        $this->email = $email;
        $this->password = $password;
        $this->deviceName = $deviceName;
    }
}
