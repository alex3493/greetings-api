<?php
declare(strict_types=1);

namespace App\Modules\Greeting\Application\UpdateGreeting;

use App\Modules\Greeting\Domain\Greeting;
use App\Modules\Shared\Application\Contract\ResponseInterface;

class UpdateGreetingResponse implements ResponseInterface
{
    public Greeting $greeting;
}
