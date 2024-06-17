<?php

namespace App\Modules\Greeting\Application\ListGreetings;

use App\Modules\Shared\Application\Contract\ResponseInterface;

class ListGreetingsResponse implements ResponseInterface
{
    public array $greetings;
}
