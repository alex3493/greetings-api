<?php

namespace App\Modules\User\Infrastructure\Security;

use App\Modules\Shared\Domain\Exception\AccessDeniedDomainException;
use App\Modules\User\Domain\Contract\SubscriberAuthorizationInterface;
use Pusher\Pusher;
use Pusher\PusherInterface;

class PusherSubscriberAuthorizer implements SubscriberAuthorizationInterface
{
    private PusherInterface $pusher;

    public function __construct(Pusher $pusher)
    {
        $this->pusher = $pusher;
    }

    /**
     * @param \App\Modules\User\Infrastructure\Security\AuthUser|null $authUser
     * @param string $context
     * @param string $socketId
     * @return array
     * @throws \App\Modules\Shared\Domain\Exception\AccessDeniedDomainException
     * @throws \Pusher\PusherException
     */
    public function authorizeSocketSubscriber(?AuthUser $authUser, string $context, string $socketId): array
    {
        if (! $authUser instanceof AuthUser) {
            throw new AccessDeniedDomainException('You must be logged in.');
        }

        // TODO: implement per-user permissions...

        $response = $this->pusher->authorizeChannel($context, $socketId);

        return json_decode($response, true);
    }
}
