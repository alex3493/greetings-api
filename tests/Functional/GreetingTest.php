<?php

namespace App\Tests\Functional;

use App\Modules\Greeting\Domain\Greeting;
use App\Modules\Shared\Domain\Message\MercureUpdateMessage;
use App\Tests\DatabaseTestCase;
use App\Tests\Seeder\GreetingSeeder;

class GreetingTest extends DatabaseTestCase
{
    public function test_we_can_create_a_greeting(): void
    {
        $user = static::$userSeeder->seedUser([
            'email' => 'test@example.com',
            'password' => 'password',
            'firstName' => 'First',
            'lastName' => 'Last',
            'roles' => ['ROLE_USER'],
        ], [], true);

        $token = $user['jwt_token'];

        $client = self::getReusableClient();

        $client->jsonRequest('POST', '/api/web/greetings', [
            'text' => 'Hi, there!',
            'variant' => 'primary',
        ], [
            'HTTP_Authorization' => 'Bearer '.$token,
        ]);

        $response = json_decode($client->getResponse()->getContent());

        $discoveryLink = $client->getResponse()->headers->get('link');
        $this->assertStringContainsString('.well-known/mercure', $discoveryLink);

        $this->assertResponseIsSuccessful();

        $greeting = $response->greeting;
        $this->assertNotEmpty($greeting->id);
        $this->assertEquals('Hi, there!', $greeting->text);
        $this->assertEquals('primary', $greeting->variant->name);
        $this->assertEquals($user['user']->getId(), $greeting->author->id);

        // Execute queue worker to publish Mercure update.
        $this->transport('async')->queue()->assertNotEmpty();

        // Check that we have queued the message of correct class.
        $event = $this->transport('async')->queue()->first();
        $this->assertInstanceOf(MercureUpdateMessage::class, $event->getMessage());

        /** @var MercureUpdateMessage $message */
        $message = $event->getMessage();

        // Check that we send additional data along with greetings text.
        $this->assertNotEmpty($message->getPayload()['causer']);
        $this->assertNotEmpty($message->getPayload()['reason']);

        $this->transport('async')->process(1);
        $this->transport('async')->queue()->assertEmpty();

        // Check that the greeting was persisted.
        $repository = $this->getRepository(Greeting::class);
        $greetings = $repository->findAll();

        $this->assertCount(1, $greetings);
        $this->assertEquals($greeting->id, $greetings[0]->getId());

        $this->assertEmpty($greetings[0]->getUpdatedBy());
        $this->assertEmpty($greetings[0]->getUpdatedAt());
    }

    public function test_we_can_list_latest_greetings(): void
    {
        $user = static::$userSeeder->seedUser([
            'email' => 'test@example.com',
            'password' => 'password',
            'firstName' => 'First',
            'lastName' => 'Last',
            'roles' => ['ROLE_USER'],
        ], [], true);

        $token = $user['jwt_token'];

        $greetingSeeder = new GreetingSeeder($this->getEntityManager());

        $greetings = [];
        for ($i = 0; $i < 10; $i++) {
            $greetings[] = $greetingSeeder->seedGreeting($user['user'], [
                'text' => 'Greeting-'.$i,
                'variant' => $i % 2 ? 'primary' : 'secondary',
            ]);
        }

        $client = self::getReusableClient();

        $client->jsonRequest('GET', '/api/web/greetings?limit=10&offset=0', [], [
            'HTTP_Authorization' => 'Bearer '.$token,
        ]);

        $response = json_decode($client->getResponse()->getContent());

        $discoveryLink = $client->getResponse()->headers->get('link');
        $this->assertStringContainsString('.well-known/mercure', $discoveryLink);

        $this->assertResponseIsSuccessful();

        // Check that last seeded greeting comes first in response.
        $this->assertEquals($greetings[0]->getId(), $response->greetings[9]->id);
    }

    public function test_we_can_list_greetings_after_given_id()
    {
        $user = static::$userSeeder->seedUser([
            'email' => 'test@example.com',
            'password' => 'password',
            'firstName' => 'First',
            'lastName' => 'Last',
            'roles' => ['ROLE_USER'],
        ], [], true);

        $token = $user['jwt_token'];

        $greetingSeeder = new GreetingSeeder($this->getEntityManager());

        $greetings = [];
        for ($i = 0; $i < 20; $i++) {
            $greetings[] = $greetingSeeder->seedGreeting($user['user'], [
                'text' => 'Greeting-'.$i,
                'variant' => $i % 2 ? 'primary' : 'secondary',
            ]);
        }

        $afterId = $greetings[9]->getId();

        $client = self::getReusableClient();

        $client->jsonRequest('GET', '/api/web/greetings?limit=10&afterId='.$afterId, [], [
            'HTTP_Authorization' => 'Bearer '.$token,
        ]);

        $this->assertResponseIsSuccessful();

        $response = json_decode($client->getResponse()->getContent());

        $this->assertCount(10, $response->greetings);
        $this->assertEquals('Greeting-19', $response->greetings[0]->text);
    }

    public function test_we_can_update_a_greeting(): void
    {
        $user = static::$userSeeder->seedUser([
            'email' => 'test@example.com',
            'password' => 'password',
            'firstName' => 'First',
            'lastName' => 'Last',
            'roles' => ['ROLE_USER'],
        ], [], true);

        $token = $user['jwt_token'];

        $greetingSeeder = new GreetingSeeder($this->getEntityManager());

        $greeting = $greetingSeeder->seedGreeting($user['user'], [
            'text' => 'Hi, there!',
            'variant' => 'primary',
        ]);

        $client = self::getReusableClient();

        $client->jsonRequest('PATCH', '/api/web/greeting/'.$greeting->getId(), [
            'text' => 'Updated greeting',
            'variant' => 'warning',
        ], [
            'HTTP_Authorization' => 'Bearer '.$token,
        ]);

        $response = json_decode($client->getResponse()->getContent());

        $this->assertResponseIsSuccessful();

        $this->assertEquals($greeting->getId(), $response->greeting->id);

        // Execute queue worker to publish Mercure update.
        $this->transport('async')->queue()->assertNotEmpty();

        // Check that we have queued the message of correct class.
        $event = $this->transport('async')->queue()->first();
        $this->assertInstanceOf(MercureUpdateMessage::class, $event->getMessage());

        // Check that affected greeting id is included into update topic.
        $this->assertStringEndsWith('/greeting/'.$greeting->getId(), $event->getMessage()->getTopic());

        $this->assertInstanceOf(MercureUpdateMessage::class, $event->getMessage());

        /** @var MercureUpdateMessage $message */
        $message = $event->getMessage();

        // Check that we send additional data along with greetings text.
        $this->assertNotEmpty($message->getPayload()['causer']);
        $this->assertNotEmpty($message->getPayload()['reason']);

        // On greeting update we also publish generic list update message.
        $this->transport('async')->process(2);
        $this->transport('async')->queue()->assertEmpty();

        // Check that the greeting was persisted.
        $repository = $this->getRepository(Greeting::class);

        /** @var \App\Modules\Greeting\Domain\Greeting $persistedGreeting */
        $persistedGreeting = $repository->find($greeting->getId());

        $this->assertEquals('Updated greeting', $persistedGreeting->getText());
        $this->assertEquals('warning', $persistedGreeting->getVariant()->getName());

        $this->assertNotEmpty($persistedGreeting->getUpdatedBy()->getId());
        $this->assertNotEmpty($persistedGreeting->getUpdatedAt());
    }

    public function test_we_can_delete_a_greeting(): void
    {
        $user = static::$userSeeder->seedUser([
            'email' => 'test@example.com',
            'password' => 'password',
            'firstName' => 'First',
            'lastName' => 'Last',
            'roles' => ['ROLE_USER'],
        ], [], true);

        $token = $user['jwt_token'];

        $greetingSeeder = new GreetingSeeder($this->getEntityManager());

        $greeting = $greetingSeeder->seedGreeting($user['user'], [
            'text' => 'Hi, there!',
            'variant' => 'primary',
        ]);

        $client = self::getReusableClient();

        $client->jsonRequest('DELETE', '/api/web/greeting/'.$greeting->getId(), [], [
            'HTTP_Authorization' => 'Bearer '.$token,
        ]);

        $response = json_decode($client->getResponse()->getContent());

        $this->assertResponseIsSuccessful();

        $this->assertEquals('Greeting deleted', $response->message);

        // Check that the greeting was deleted.
        $repository = $this->getRepository(Greeting::class);

        $this->assertNull($repository->find($greeting->getId()));

        // Execute queue worker to publish Mercure update.
        $this->transport('async')->queue()->assertNotEmpty();

        // We send both list and item update messages.
        $this->transport('async')->queue()->assertCount(2);

        // Check that we have queued the message of correct class.
        $event = $this->transport('async')->queue()->first();
        $this->assertInstanceOf(MercureUpdateMessage::class, $event->getMessage());

        // Check that affected greeting id is included into update topic.
        $this->assertStringEndsWith('/greeting/'.$greeting->getId(), $event->getMessage()->getTopic());

        $this->assertInstanceOf(MercureUpdateMessage::class, $event->getMessage());

        /** @var MercureUpdateMessage $message */
        $message = $event->getMessage();

        $payload = $message->getPayload();

        // Check the payload.
        $this->assertEquals('delete', $payload['reason']);
        $this->assertEquals($greeting->getId(), $payload['greeting']['id']);
        $this->assertEquals($user['user']->getId(), $payload['causer']['id']);
    }

    public function test_we_can_read_a_greeting(): void
    {
        $user = static::$userSeeder->seedUser([
            'email' => 'test@example.com',
            'password' => 'password',
            'firstName' => 'First',
            'lastName' => 'Last',
            'roles' => ['ROLE_USER'],
        ], [], true);

        $token = $user['jwt_token'];

        $greetingSeeder = new GreetingSeeder($this->getEntityManager());

        $greeting = $greetingSeeder->seedGreeting($user['user'], [
            'text' => 'Hi, there!',
            'variant' => 'primary',
        ]);

        $client = self::getReusableClient();

        $client->jsonRequest('GET', '/api/web/greeting/'.$greeting->getId(), [], [
            'HTTP_Authorization' => 'Bearer '.$token,
        ]);

        $discoveryLink = $client->getResponse()->headers->get('link');
        $this->assertStringContainsString('.well-known/mercure', $discoveryLink);

        $response = json_decode($client->getResponse()->getContent());

        $this->assertResponseIsSuccessful();

        $this->assertEquals($greeting->getId(), $response->greeting->id);
    }

    public function test_update_greeting_permissions(): void
    {
        $user = static::$userSeeder->seedUser([
            'email' => 'user@example.com',
            'password' => 'password',
            'firstName' => 'First',
            'lastName' => 'Last',
            'roles' => ['ROLE_USER'],
        ], [], true);

        $token = $user['jwt_token'];

        $author = static::$userSeeder->seedUser([
            'email' => 'author@example.com',
            'password' => 'password',
            'firstName' => 'First',
            'lastName' => 'Last',
            'roles' => ['ROLE_USER'],
        ], [], true);

        $greetingSeeder = new GreetingSeeder($this->getEntityManager());

        $greeting = $greetingSeeder->seedGreeting($author['user'], [
            'text' => 'Hi, there!',
            'variant' => 'primary',
        ]);

        $client = self::getReusableClient();

        $client->jsonRequest('PATCH', '/api/web/greeting/'.$greeting->getId(), [
            'text' => 'Updated greeting',
            'variant' => 'warning',
        ], [
            'HTTP_Authorization' => 'Bearer '.$token,
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function test_update_greeting_permissions_admin(): void
    {
        $admin = static::$userSeeder->seedUser([
            'email' => 'admin@example.com',
            'password' => 'password',
            'firstName' => 'First',
            'lastName' => 'Last',
            'roles' => ['ROLE_ADMIN'],
        ], [], true);

        $token = $admin['jwt_token'];

        $author = static::$userSeeder->seedUser([
            'email' => 'author@example.com',
            'password' => 'password',
            'firstName' => 'First',
            'lastName' => 'Last',
            'roles' => ['ROLE_USER'],
        ], [], true);

        $greetingSeeder = new GreetingSeeder($this->getEntityManager());

        $greeting = $greetingSeeder->seedGreeting($author['user'], [
            'text' => 'Hi, there!',
            'variant' => 'primary',
        ]);

        $client = self::getReusableClient();

        $client->jsonRequest('PATCH', '/api/web/greeting/'.$greeting->getId(), [
            'text' => 'Updated greeting',
            'variant' => 'warning',
        ], [
            'HTTP_Authorization' => 'Bearer '.$token,
        ]);

        $this->assertResponseIsSuccessful();

        // Check that the greeting was persisted.
        $repository = $this->getRepository(Greeting::class);

        /** @var \App\Modules\Greeting\Domain\Greeting $persistedGreeting */
        $persistedGreeting = $repository->find($greeting->getId());

        $this->assertEquals($admin['user']->getId(), $persistedGreeting->getUpdatedBy()->getId());
        $this->assertNotEmpty($persistedGreeting->getUpdatedAt());
    }

    public function test_create_greeting_validation_error(): void
    {
        $user = static::$userSeeder->seedUser([
            'email' => 'test@example.com',
            'password' => 'password',
            'firstName' => 'First',
            'lastName' => 'Last',
            'roles' => ['ROLE_USER'],
        ], [], true);

        $token = $user['jwt_token'];

        $client = self::getReusableClient();

        $client->jsonRequest('POST', '/api/web/greetings', [
            'text' => '     ', // Check that trim normalizer works as expected.
            'variant' => 'primary',
        ], [
            'HTTP_Authorization' => 'Bearer '.$token,
        ]);

        $response = json_decode($client->getResponse()->getContent());

        $this->assertResponseStatusCodeSame(422);

        $this->assertCount(1, $response->errors);

        $this->assertCount(2, $response->errors[0]->errors);
        $this->assertEquals('text', $response->errors[0]->property);
        $this->assertEquals('Greeting', $response->errors[0]->context);
        $this->assertEquals('This value should not be blank.', $response->errors[0]->errors[0]);
        $this->assertEquals('Greeting text must be at least 2 characters long', $response->errors[0]->errors[1]);
    }
}


