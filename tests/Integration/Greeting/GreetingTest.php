<?php

namespace App\Tests\Integration\Greeting;

use App\Modules\Greeting\Application\CreateGreeting\CreateGreetingRequest;
use App\Modules\Greeting\Application\CreateGreeting\CreateGreetingUseCase;
use App\Modules\Greeting\Application\DeleteGreeting\DeleteGreetingRequest;
use App\Modules\Greeting\Application\DeleteGreeting\DeleteGreetingUseCase;
use App\Modules\Greeting\Application\ListGreetings\ListGreetingsRequest;
use App\Modules\Greeting\Application\ListGreetings\ListGreetingsUseCase;
use App\Modules\Greeting\Application\UpdateGreeting\UpdateGreetingRequest;
use App\Modules\Greeting\Application\UpdateGreeting\UpdateGreetingUseCase;
use App\Modules\Shared\Domain\Exception\ValidationException;
use App\Modules\User\Infrastructure\Security\AuthUser;
use App\Tests\DatabaseTestCase;
use App\Tests\Seeder\GreetingSeeder;

class GreetingTest extends DatabaseTestCase
{
    public function test_create_greeting_use_case(): void
    {
        $user = self::$userSeeder->seedUser();
        $userId = $user['user']->getId();

        $container = static::getContainer();

        $request = new CreateGreetingRequest('Hi, there!', 'primary', $userId);

        /** @var CreateGreetingUseCase $useCase */
        $useCase = $container->get(CreateGreetingUseCase::class);

        /** @var \App\Modules\Greeting\Application\CreateGreeting\CreateGreetingResponse $response */
        $response = $useCase->run($request);

        $this->assertNotEmpty($response->greeting->getId());
        $this->assertEquals('Hi, there!', $response->greeting->getText());
        $this->assertEquals($userId, $response->greeting->getAuthor()->getId());

        $this->assertEmpty($response->greeting->getUpdatedBy());
        $this->assertEmpty($response->greeting->getUpdatedAt());
    }

    public function test_greeting_list_use_case(): void
    {
        $user = self::$userSeeder->seedUser([
            'roles' => ['ROLE_ADMIN'],
        ]);

        $greetingSeeder = new GreetingSeeder($this->getEntityManager());

        $greetings = [];
        for ($i = 0; $i < 10; $i++) {
            $greetings[] = $greetingSeeder->seedGreeting($user['user'], [
                'text' => 'Greeting-'.$i,
                'variant' => $i % 2 ? 'primary' : 'secondary',
            ]);
        }

        $container = static::getContainer();

        $request = new ListGreetingsRequest(10, 0, '');

        /** @var ListGreetingsUseCase $useCase */
        $useCase = $container->get(ListGreetingsUseCase::class);

        /** @var \App\Modules\Greeting\Application\ListGreetings\ListGreetingsResponse $response */
        $response = $useCase->run($request);

        $this->assertNotEmpty($response->greetings);

        // Check that last seeded greeting comes first in response.
        $this->assertEquals($greetings[0]->getId(), $response->greetings[9]->getId());
    }

    public function test_update_greeting_use_case(): void
    {
        $user = self::$userSeeder->seedUser();
        $userId = $user['user']->getId();

        $greetingSeeder = new GreetingSeeder($this->getEntityManager());

        $greeting = $greetingSeeder->seedGreeting($user['user'], [
            'text' => 'Hi, there!',
            'variant' => 'primary',
        ]);

        $container = static::getContainer();

        $request = new UpdateGreetingRequest($greeting->getId(), 'Updated greeting', 'secondary', $userId);

        /** @var UpdateGreetingUseCase $useCase */
        $useCase = $container->get(UpdateGreetingUseCase::class);

        /** @var \App\Modules\Greeting\Application\UpdateGreeting\UpdateGreetingResponse $response */
        $response = $useCase->run($request);

        $this->assertNotEmpty($response->greeting->getId());
        $this->assertEquals('Updated greeting', $response->greeting->getText());
        $this->assertEquals('secondary', $response->greeting->getVariant()->getName());

        $this->assertNotEmpty($response->greeting->getUpdatedBy()->getId());
        $this->assertNotEmpty($response->greeting->getUpdatedAt());
    }

    public function test_delete_greeting_use_case(): void
    {
        $user = self::$userSeeder->seedUser();
        $client = static::getReusableClient();

        $loginUser = new AuthUser($user['user']);
        $client->loginUser($loginUser);

        $greetingSeeder = new GreetingSeeder($this->getEntityManager());

        $greeting = $greetingSeeder->seedGreeting($user['user'], [
            'text' => 'Hi, there!',
            'variant' => 'primary',
        ]);

        $container = static::getContainer();

        $request = new DeleteGreetingRequest($greeting->getId(), $user['user']->getId());

        /** @var DeleteGreetingUseCase $useCase */
        $useCase = $container->get(DeleteGreetingUseCase::class);

        /** @var \App\Modules\Shared\Application\MessageResponse $response */
        $response = $useCase->run($request);

        $this->assertEquals('Greeting deleted', $response->message);
    }

    public function test_create_greeting_use_case_validation(): void
    {
        $user = self::$userSeeder->seedUser();
        $userId = $user['user']->getId();

        $container = static::getContainer();

        $request = new CreateGreetingRequest('X', 'primary', $userId);

        /** @var CreateGreetingUseCase $useCase */
        $useCase = $container->get(CreateGreetingUseCase::class);

        $this->expectException(ValidationException::class);
        $useCase->run($request);

        $request = new CreateGreetingRequest('', 'primary', $userId);

        $this->expectException(ValidationException::class);
        $useCase->run($request);
    }

    public function test_update_greeting_use_case_validation(): void
    {
        $user = self::$userSeeder->seedUser();
        $userId = $user['user']->getId();

        $greetingSeeder = new GreetingSeeder($this->getEntityManager());

        $greeting = $greetingSeeder->seedGreeting($user['user'], [
            'text' => 'Hi, there!',
            'variant' => 'primary',
        ]);

        $container = static::getContainer();

        /** @var UpdateGreetingUseCase $useCase */
        $useCase = $container->get(UpdateGreetingUseCase::class);

        $request = new UpdateGreetingRequest($greeting->getId(), 'X', 'secondary', $userId);

        $this->expectException(ValidationException::class);
        $useCase->run($request);

        $request = new UpdateGreetingRequest($greeting->getId(), '', 'secondary', $userId);

        $this->expectException(ValidationException::class);
        $useCase->run($request);
    }
}
