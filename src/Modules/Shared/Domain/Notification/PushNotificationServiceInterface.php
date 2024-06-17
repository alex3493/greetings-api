<?php

namespace App\Modules\Shared\Domain\Notification;

interface PushNotificationServiceInterface
{
    public function send(string $channel, string $event, array $payload): void;
}
