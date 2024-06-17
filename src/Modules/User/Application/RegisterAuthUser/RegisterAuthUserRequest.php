<?php
declare(strict_types=1);

namespace App\Modules\User\Application\RegisterAuthUser;

use App\Modules\Shared\Application\ValidatedRequest;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RegisterAuthUserRequest extends ValidatedRequest
{
    public string $email;

    public string $password;

    public string $passwordConfirmation;

    public ?string $firstName;

    public ?string $lastName;

    public ?string $deviceName;

    public function __construct(
        ValidatorInterface $validator, string $email, string $password, string $passwordConfirmation,
        ?string $firstName, ?string $lastName, ?string $deviceName = null
    ) {
        parent::__construct($validator);

        $this->email = $email;
        $this->password = $password;
        $this->passwordConfirmation = $passwordConfirmation;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->deviceName = $deviceName;
    }
}
