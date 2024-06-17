<?php
declare(strict_types=1);

namespace App\Modules\Greeting\Application\ReadGreeting;

use App\Modules\Shared\Application\Contract\RequestInterface;

class ReadGreetingRequest implements RequestInterface
{
    public string $id;

    public function __construct(string $id)
    {
        $this->id = $id;
    }
}
