<?php

namespace App\Modules\Greeting\Application\DeleteGreeting;

use App\Modules\Shared\Application\Contract\RequestInterface;

class DeleteGreetingRequest implements RequestInterface
{
    public string $id;

    public string $causerId;

    public function __construct(string $id, string $causerId)
    {
        $this->id = $id;
        $this->causerId = $causerId;
    }
}
