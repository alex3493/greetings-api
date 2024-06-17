<?php
declare(strict_types=1);

namespace App\Modules\User\Application\UpdateUser;

use App\Modules\Shared\Application\Contract\RequestInterface;

class UpdateUserRequest implements RequestInterface
{
    public string $id;

    public ?string $firstName;

    public ?string $lastName;

    public function __construct(string $id, ?string $firstName, ?string $lastName)
    {
        $this->id = $id;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
    }
}
