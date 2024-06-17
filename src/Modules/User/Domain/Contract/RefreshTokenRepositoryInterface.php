<?php

namespace App\Modules\User\Domain\Contract;

use App\Modules\User\Domain\RefreshToken;

interface RefreshTokenRepositoryInterface
{
    public function save(RefreshToken $refreshToken): void;

    public function delete(RefreshToken $refreshToken): void;
}
