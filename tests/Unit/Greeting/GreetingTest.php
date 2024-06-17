<?php

namespace App\Tests\Unit\Greeting;

use App\Modules\Greeting\Domain\Greeting;
use App\Modules\Shared\Domain\ValueObject\EntityId;
use App\Modules\User\Domain\User;
use DateTime;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class GreetingTest extends TestCase
{
    public function test_create_greeting(): void
    {
        $greeting = Greeting::create('He, there!', 'primary', $this->testUser());
        self::assertInstanceOf(Greeting::class, $greeting);

        self::assertIsString($greeting->getId());
        self::assertEquals(36, strlen($greeting->getId()));

        self::assertEquals('primary', $greeting->getVariant());
        self::assertEquals('primary', $greeting->getVariant()->getName());
        self::assertEquals('test@example.com', $greeting->getAuthor()->getEmail());
    }

    public function test_create_greeting_variant_validation(): void
    {
        self::expectException(InvalidArgumentException::class);
        Greeting::create('He, there!', 'invalid', $this->testUser());
    }

    public function test_init_existing_greeting(): void
    {
        $greeting = new Greeting(EntityId::create(), 'Hi, there!', 'primary', $this->testUser(), new DateTime());
        self::assertInstanceOf(Greeting::class, $greeting);

        self::assertIsString($greeting->getId());
        self::assertEquals(36, strlen($greeting->getId()));

        self::assertEquals('primary', $greeting->getVariant());
        self::assertEquals('test@example.com', $greeting->getAuthor()->getEmail());
    }

    private function testUser(): User
    {
        return User::create('test@example.com', 'password', 'First', 'Last', ['ROLE_USER']);
    }
}
