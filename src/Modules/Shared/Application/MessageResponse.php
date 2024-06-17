<?php

namespace App\Modules\Shared\Application;

class MessageResponse implements Contract\ResponseInterface
{
    public string $message;

    public function __construct(string $message)
    {
        $this->message = $message;
    }
}
