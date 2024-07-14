<?php

namespace App\Tests\Integration\User;

use App\Modules\Shared\Domain\Exception\ValidationException;
use App\Modules\User\Application\CreateUser\CreateUserRequest;
use App\Modules\User\Application\CreateUser\CreateUserUseCase;
use App\Modules\User\Application\LogoutWebUser\LogoutWebUserRequest;
use App\Modules\User\Application\LogoutWebUser\LogoutWebUserUseCase;
use App\Modules\User\Application\UpdateUser\UpdateUserRequest;
use App\Modules\User\Application\UpdateUser\UpdateUserUseCase;
use App\Tests\DatabaseTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserTest extends DatabaseTestCase
{
    public function test_create_user_use_case(): void
    {
        $container = static::getContainer();

        $validator = $container->get(ValidatorInterface::class);

        $request = new CreateUserRequest($validator, 'test@test.com', 'password', 'password', 'First', 'Last');

        /** @var CreateUserUseCase $useCase */
        $useCase = $container->get(CreateUserUseCase::class);

        /** @var \App\Modules\User\Application\CreateUser\CreateUserResponse $response */
        $response = $useCase->run($request);

        $this->assertEquals('test@test.com', $response->user->getEmail());

        // Check that we hash user password.
        $this->assertNotEquals('password', $response->user->getPassword());

        $this->assertCount(1, $response->user->getRoles());
        $this->assertEquals('ROLE_USER', $response->user->getRole());

        $connection = $this->getEntityManager()->getConnection();
        $sql = 'SELECT roles FROM user WHERE id = :id';
        $stmt = $connection->prepare($sql);
        $stmt->bindValue('id', $response->user->getId());

        /** @var \Doctrine\DBAL\Result $result */
        $result = $stmt->executeQuery();
        $roles = json_decode($result->fetchOne());

        // Check that role is persisted in DB. We cannot use User::getRoles() because it
        // decorates DB data, injecting ROLE_USER if no roles are stored.
        $this->assertCount(1, $roles);
        $this->assertEquals('ROLE_USER', $roles[0]);
    }

    public function test_update_user_use_case(): void
    {
        $container = static::getContainer();

        $user = self::$userSeeder->seedUser();

        $request = new UpdateUserRequest($user['user']->getId(), 'First', 'Last');

        /** @var UpdateUserUseCase $useCase */
        $useCase = $container->get(UpdateUserUseCase::class);
        $response = $useCase->run($request);

        $this->assertEquals('test@example.com', $response->user->getEmail());
        $this->assertEquals('First', $response->user->getFirstName());
        $this->assertEquals('Last', $response->user->getLastName());

        // Admin user.
        $user = self::$userSeeder->seedUser([
            'email' => 'admin@example.com',
            'roles' => ['ROLE_ADMIN'],
        ]);

        $request = new UpdateUserRequest($user['user']->getId(), 'First', 'Last');

        /** @var UpdateUserUseCase $useCase */
        $useCase = $container->get(UpdateUserUseCase::class);
        $response = $useCase->run($request);

        $this->assertEquals('admin@example.com', $response->user->getEmail());
        $this->assertEquals('First', $response->user->getFirstName());
        $this->assertEquals('Last', $response->user->getLastName());

        // Check that user roles are not affected by update.
        $this->assertCount(2, $response->user->getRoles());
        $this->assertContains('ROLE_ADMIN', $response->user->getRoles());
        $this->assertEquals('ROLE_ADMIN', $response->user->getRole());
    }

    public function test_create_user_use_case_error_email_already_taken(): void
    {
        $container = static::getContainer();

        $validator = $container->get(ValidatorInterface::class);

        self::$userSeeder->seedUser([
            'email' => 'test@test.com',
        ]);

        $request = new CreateUserRequest($validator, 'test@test.com', 'password', 'password', 'First', 'Last');

        /** @var CreateUserUseCase $useCase */
        $useCase = $container->get(CreateUserUseCase::class);

        $this->expectException(ValidationException::class);
        $useCase->run($request);
    }

    public function test_logout_user_use_case(): void
    {
        $container = static::getContainer();

        $user = self::$userSeeder->seedUser([], [], true);

        $request = new LogoutWebUserRequest($user['user']->getId());

        /** @var LogoutWebUserUseCase $useCase */
        $useCase = $container->get(LogoutWebUserUseCase::class);

        /** @var \App\Modules\User\Application\LogoutWebUser\LogoutWebUserResponse $response
         */
        $response = $useCase->run($request);

        $this->assertEquals('You have successfully logged out', $response->message);
    }

    public function test_create_user_console_command(): void
    {
        static::getContainer();
        $application = new Application(self::$kernel);

        $command = $application->find('app:add-user');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            // Pass arguments to the helper.
            'email' => 'admin@example.com',
            'password' => 'password',
            'first-name' => 'First',
            'last-name' => 'Last',
            '--admin' => true,
        ]);

        $commandTester->assertCommandIsSuccessful();

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('[OK] Administrator user was successfully created: admin@example.com',
            $output);

        $commandTester->execute([
            // Pass arguments to the helper.
            'email' => 'user@example.com',
            'password' => 'password',
            'first-name' => 'First',
            'last-name' => 'Last',
            // '--admin' => true,
        ]);

        $commandTester->assertCommandIsSuccessful();

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('[OK] User was successfully created: user@example.com', $output);
    }
}
