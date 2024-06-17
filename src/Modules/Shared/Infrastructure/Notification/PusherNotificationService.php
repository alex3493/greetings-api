<?php

namespace App\Modules\Shared\Infrastructure\Notification;

use App\Modules\Shared\Domain\Notification\PushNotificationServiceInterface;
use Pusher\Pusher;

class PusherNotificationService implements PushNotificationServiceInterface
{
    private Pusher $pusher;

    public function __construct(Pusher $pusher)
    {
        $this->pusher = $pusher;
    }

    /**
     * @param string $channel
     * @param string $event
     * @param array $payload
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pusher\ApiErrorException
     * @throws \Pusher\PusherException
     */
    public function send(string $channel, string $event, array $payload): void
    {
        $this->pusher->trigger($channel, $event, $payload);
    }
}
