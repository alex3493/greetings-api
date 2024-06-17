<?php
declare(strict_types=1);

namespace App\Modules\Greeting\Application\CreateGreeting;

use App\Modules\Greeting\Domain\Greeting;
use App\Modules\Shared\Application\Contract\ResponseInterface;

class CreateGreetingResponse implements ResponseInterface
{
    public Greeting $greeting;
}
