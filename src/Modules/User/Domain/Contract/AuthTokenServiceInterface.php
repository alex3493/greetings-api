<?php
declare(strict_types=1);

namespace App\Modules\User\Domain\Contract;

use App\Modules\User\Domain\AuthToken;
use App\Modules\User\Domain\User;

interface AuthTokenServiceInterface
{
    public function generateAndSaveToken(User $user, string $deviceName): AuthToken;

    public function find(string $tokenId): ?AuthToken;

    public function delete(AuthToken $authToken): void;

    public function existing(User $user, string $deviceName): ?AuthToken;
}
