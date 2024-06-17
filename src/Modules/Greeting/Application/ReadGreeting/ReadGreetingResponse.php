<?php
declare(strict_types=1);

namespace App\Modules\Greeting\Application\ReadGreeting;

use App\Modules\Greeting\Domain\Greeting;
use App\Modules\Shared\Application\Contract\ResponseInterface;

class ReadGreetingResponse implements ResponseInterface
{
    public Greeting $greeting;
}
