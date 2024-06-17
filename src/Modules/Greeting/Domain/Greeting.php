<?php

namespace App\Modules\Greeting\Domain;

use App\Modules\Shared\Domain\ValueObject\EntityId;
use App\Modules\User\Domain\User;
use DateTime;

class Greeting
{
    private string $id;

    private string $text;

    private GreetingVariant $variant;

    private User $author;

    private ?User $updatedBy;

    private DateTime $createdAt;

    private ?DateTime $updatedAt;

    public function __construct(
        EntityId $id, string $text, string $variant, User $user, DateTime $createdAt, ?User $updatedBy = null,
        ?DateTime $updatedAt = null
    ) {
        $this->id = $id->getValue();
        $this->text = $text;
        $this->variant = new GreetingVariant($variant);
        $this->author = $user;
        $this->createdAt = $createdAt;

        $this->updatedBy = $updatedBy;
        $this->updatedAt = $updatedAt;
    }

    public static function create(string $text, string $variant, User $author): Greeting
    {
        return new self(EntityId::create(), $text, $variant, $author, new DateTime());
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function setText(string $text): Greeting
    {
        $this->text = $text;

        return $this;
    }

    public function getVariant(): GreetingVariant
    {
        return $this->variant;
    }

    public function setVariant(GreetingVariant $variant): Greeting
    {
        $this->variant = $variant;

        return $this;
    }

    public function getAuthor(): User
    {
        return $this->author;
    }

    public function setAuthor(User $author): Greeting
    {
        $this->author = $author;

        return $this;
    }

    public function getUpdatedBy(): ?User
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?User $updatedBy): Greeting
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?DateTime $updatedAt): Greeting
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
