<?php

namespace App\Modules\Shared\Domain\Message;

class AdminGreetingMessage implements AsyncMessageInterface
{
    private string $greeting;

    private string $authorId;

    private string $authorName;

    public function __construct(string $greeting, string $authorId, string $authorName)
    {
        $this->greeting = $greeting;
        $this->authorId = $authorId;
        $this->authorName = $authorName;
    }

    public function getGreeting(): string
    {
        return $this->greeting;
    }

    public function getAuthorId(): string
    {
        return $this->authorId;
    }

    public function getAuthorName(): string
    {
        return $this->authorName;
    }
}
