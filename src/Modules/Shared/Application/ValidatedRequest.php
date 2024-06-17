<?php

namespace App\Modules\Shared\Application;

use App\Modules\Shared\Domain\Exception\ValidationException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ValidatedRequest implements Contract\RequestInterface
{
    protected ValidatorInterface $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @param string|null $context
     * @return void
     * @throws \App\Modules\Shared\Domain\Exception\ValidationException
     */
    public function validate(?string $context = null): void
    {
        $errors = $this->validator->validate($this);

        if (count($errors) > 0) {
            throw new ValidationException($errors, $context);
        }
    }
}
