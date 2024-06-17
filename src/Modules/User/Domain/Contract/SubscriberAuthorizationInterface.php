<?php

namespace App\Modules\User\Domain\Contract;

use App\Modules\User\Infrastructure\Security\AuthUser;

interface SubscriberAuthorizationInterface
{
    public function authorizeSocketSubscriber(?AuthUser $authUser, string $context, string $socketId): array;
}
