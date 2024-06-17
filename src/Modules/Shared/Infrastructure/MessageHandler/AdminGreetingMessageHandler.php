<?php

namespace App\Modules\Shared\Infrastructure\MessageHandler;

use App\Modules\Shared\Domain\Message\AdminGreetingMessage;
use App\Modules\Shared\Domain\Notification\PushNotificationServiceInterface;
use DateTime;

class AdminGreetingMessageHandler
{
    private PushNotificationServiceInterface $service;

    public function __construct(PushNotificationServiceInterface $service)
    {
        $this->service = $service;
    }

    public function __invoke(AdminGreetingMessage $greeting): void
    {
        $this->service->send('private-greeting', 'message_sent', [
            'greeting' => $greeting->getGreeting(),
            'author_id' => $greeting->getAuthorId(),
            'author_name' => $greeting->getAuthorName(),
            'timestamp' => (new DateTime())->format('U'),
        ]);
    }
}
