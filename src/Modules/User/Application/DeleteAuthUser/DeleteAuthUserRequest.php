<?php
declare(strict_types=1);

namespace App\Modules\User\Application\DeleteAuthUser;

use App\Modules\Shared\Application\Contract\RequestInterface;

class DeleteAuthUserRequest implements RequestInterface
{
    public string $id;

    public string $password;

    public function __construct(string $id, string $password)
    {
        $this->id = $id;
        $this->password = $password;
    }
}
