<?php

namespace App\Tests\Functional;

use App\Tests\DatabaseTestCase;

class NotificationTest extends DatabaseTestCase
{
    public function test_we_can_publish_mercure_update(): void
    {
        $user = static::$userSeeder->seedUser([], [
            ['name' => 'web', 'expiresAfter' => 24 * 60],
        ]);

        $token = $user['app_token'];

        $client = self::getReusableClient();

        $client->jsonRequest('POST', '/api/app/test-mercure', [
            'topic' => 'test-topic',
            'payload' => [
                'message' => 'Hi, there!',
                'status' => 'OK',
            ],
        ], [
            'HTTP_Authorization' => 'Bearer '.$token,
        ]);

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('OK', $response->message_dispatched);

        $this->assertResponseIsSuccessful();

        $this->transport('async')->queue()->assertNotEmpty();

        $this->transport('async')->process(1);

        // This assertion detects rejected message (e.g. exception occurred).
        $this->transport('async')->rejected()->assertEmpty();

        $this->transport('async')->queue()->assertEmpty();

        // TODO: check why failed queue is empty when we have rejected message.
        // Maybe we are waiting for max retries before placing message into failed queue.
        $this->transport('failed')->queue()->assertEmpty();
    }

    public function test_authorize_private_pusher_channel()
    {
        $user = static::$userSeeder->seedUser([], [], true);

        $token = $user['jwt_token'];

        $client = self::getReusableClient();

        $client->xmlHttpRequest('POST', '/api/web/pusher-auth', [], [], [
            'HTTP_Authorization' => 'Bearer '.$token,
        ], 'socket_id=716234.2193123&channel_name=chat');

        $response = json_decode($client->getResponse()->getContent());
        $this->assertResponseIsSuccessful();
        $this->assertNotEmpty($response->auth);
    }

    public function test_authorize_mercure_subscription()
    {
        $user = static::$userSeeder->seedUser([], [], true);

        $token = $user['jwt_token'];

        $client = self::getReusableClient();

        $client->jsonRequest('GET', '/api/web/mercure-auth', [], [
            'HTTP_Authorization' => 'Bearer '.$token,
        ]);

        $this->assertResponseIsSuccessful();

        $response = json_decode($client->getResponse()->getContent());

        $this->assertNotEmpty($response->token);
    }

    // TODO: we have to use a mock for pusher service...
    public function test_send_admin_greeting()
    {
        $user = static::$userSeeder->seedUser([
            'roles' => ['ROLE_ADMIN'],
        ], [
            ['name' => 'iPhone 15'],
        ], false);

        $token = $user['app_token'];

        $client = self::getReusableClient();

        $client->jsonRequest('POST', '/api/app/admin-greeting', [
            'greeting' => 'Hello World!',
        ], [
            'HTTP_Authorization' => 'Bearer '.$token,
        ]);

        $this->assertResponseIsSuccessful();

        $this->transport('async')->queue()->assertNotEmpty();

        $this->transport('async')->process(1);

        $this->transport('async')->queue()->assertEmpty();
    }

    public function test_only_admin_can_send_admin_greeting()
    {
        $user = static::$userSeeder->seedUser([
            'roles' => ['ROLE_USER'],
        ], [
            ['name' => 'iPhone 15'],
        ], false);

        $token = $user['app_token'];

        $client = self::getReusableClient();

        $client->jsonRequest('POST', '/api/app/admin-greeting', [
            'greeting' => 'Hello World!',
        ], [
            'HTTP_Authorization' => 'Bearer '.$token,
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function test_send_admin_greeting_validation()
    {
        $user = static::$userSeeder->seedUser([
            'roles' => ['ROLE_ADMIN'],
        ], [
            ['name' => 'iPhone 15'],
        ], false);

        $token = $user['app_token'];

        $client = self::getReusableClient();

        $client->jsonRequest('POST', '/api/app/admin-greeting', [
            'greeting' => '  ',
        ], [
            'HTTP_Authorization' => 'Bearer '.$token,
        ]);

        $response = json_decode($client->getResponse()->getContent());

        $this->assertResponseStatusCodeSame(422);

        $this->assertCount(1, $response->errors);

        $this->assertCount(1, $response->errors[0]->errors);
        $this->assertEquals('greeting', $response->errors[0]->property);
        $this->assertEquals('AdminGreetings', $response->errors[0]->context);
        $this->assertEquals('Greeting cannot be empty.', $response->errors[0]->errors[0]);
    }
}
