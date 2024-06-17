<?php

namespace App\Modules\Shared\Infrastructure\MessageHandler;

use App\Modules\Shared\Domain\Message\MercureUpdateMessage;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class MercureUpdateMessageHandler
{
    private HubInterface $hub;

    public function __construct(HubInterface $hub)
    {
        $this->hub = $hub;
    }

    public function __invoke(MercureUpdateMessage $message): void
    {
        $update = new Update($message->getTopic(), json_encode($message->getPayload()), true);

        $this->hub->publish($update);
    }
}
