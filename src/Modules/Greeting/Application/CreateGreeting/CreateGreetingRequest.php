<?php
declare(strict_types=1);

namespace App\Modules\Greeting\Application\CreateGreeting;

use App\Modules\Shared\Application\Contract\RequestInterface;

class CreateGreetingRequest implements RequestInterface
{
    public string $text;

    public string $variant;

    public string $authorId;

    public function __construct(string $text, string $variant, string $authorId)
    {
        $this->text = $text;
        $this->variant = $variant;
        $this->authorId = $authorId;
    }
}
