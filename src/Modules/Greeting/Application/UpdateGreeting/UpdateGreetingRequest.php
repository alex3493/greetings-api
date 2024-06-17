<?php
declare(strict_types=1);

namespace App\Modules\Greeting\Application\UpdateGreeting;

use App\Modules\Shared\Application\Contract\RequestInterface;

class UpdateGreetingRequest implements RequestInterface
{
    public string $id;

    public string $text;

    public ?string $variant;

    public string $causerId;

    public function __construct(string $id, string $text, ?string $variant, string $causerId)
    {
        $this->id = $id;
        $this->text = $text;
        $this->variant = $variant;
        $this->causerId = $causerId;
    }
}
