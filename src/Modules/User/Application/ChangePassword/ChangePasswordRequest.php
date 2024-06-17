<?php
declare(strict_types=1);

namespace App\Modules\User\Application\ChangePassword;

use App\Modules\Shared\Application\ValidatedRequest;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ChangePasswordRequest extends ValidatedRequest
{
    public string $id;

    public string $currentPassword;

    public string $password;

    public string $passwordConfirmation;

    public function __construct(
        ValidatorInterface $validator, string $id, string $currentPassword, string $password,
        string $passwordConfirmation
    ) {
        parent::__construct($validator);

        $this->id = $id;
        $this->currentPassword = $currentPassword;
        $this->password = $password;
        $this->passwordConfirmation = $passwordConfirmation;
    }
}
