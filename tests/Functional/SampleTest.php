<?php

namespace App\Tests\Functional;

use App\Tests\DatabaseTestCase;

class SampleTest extends DatabaseTestCase
{
    public function test_example(): void
    {
        $client = self::getReusableClient();
        $client->jsonRequest('GET', '/api/');

        // $response = json_decode($client->getResponse()->getContent());

        $this->assertResponseIsSuccessful();
    }

    public function test_we_can_access_private_page_from_app(): void
    {
        $user = static::$userSeeder->seedUser([], [
            ['name' => 'web', 'expiresAfter' => 24 * 60],
        ]);

        $token = $user['app_token'];

        $client = self::getReusableClient();

        $client->jsonRequest('GET', '/api/app/dashboard', [], [
            'HTTP_Authorization' => 'Bearer '.$token,
        ]);

        $response = json_decode($client->getResponse()->getContent());

        $this->assertResponseIsSuccessful();
        $this->assertEquals('Welcome to dashboard. You are logged in.', $response->data->message);
        $this->assertEquals('test@example.com', $response->user->email);
    }

    public function test_we_can_access_private_page_from_web(): void
    {
        $user = static::$userSeeder->seedUser([], [], true);

        $token = $user['jwt_token'];

        $client = self::getReusableClient();

        $client->jsonRequest('GET', '/api/web/dashboard', [], [
            'HTTP_Authorization' => 'Bearer '.$token,
        ]);

        $response = json_decode($client->getResponse()->getContent());

        $this->assertResponseIsSuccessful();
        $this->assertEquals('Welcome to dashboard. You are logged in.', $response->data->message);
        $this->assertEquals('test@example.com', $response->user->email);
    }

    public function test_we_cannot_access_private_page_when_unauthorized(): void
    {
        $client = self::getReusableClient();

        $client->jsonRequest('GET', '/api/app/dashboard', [], [
            'HTTP_Authorization' => 'Bearer wrong_token',
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function test_we_cannot_access_private_page_when_token_expired(): void
    {
        $user = static::$userSeeder->seedUser([], [
            ['name' => 'web', 'isExpired' => true],
        ]);

        $token = $user['app_token'];

        $client = self::getReusableClient();

        $client->jsonRequest('GET', '/api/app/dashboard', [], [
            'HTTP_Authorization' => 'Bearer '.$token,
        ]);

        $this->assertResponseStatusCodeSame(401);
    }
}
