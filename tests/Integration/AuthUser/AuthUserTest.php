<?php

namespace App\Tests\Integration\AuthUser;

use App\Modules\Shared\Domain\Exception\FormValidationException;
use App\Modules\Shared\Domain\Exception\UnauthorizedDomainException;
use App\Modules\Shared\Domain\Exception\ValidationException;
use App\Modules\User\Application\ChangePassword\ChangePasswordRequest;
use App\Modules\User\Application\ChangePassword\ChangePasswordUseCase;
use App\Modules\User\Application\DeleteAuthUser\DeleteAuthUserRequest;
use App\Modules\User\Application\DeleteAuthUser\DeleteAuthUserUseCase;
use App\Modules\User\Application\LoginAuthUser\LoginAuthUserRequest;
use App\Modules\User\Application\LoginAuthUser\LoginAuthUserUseCase;
use App\Modules\User\Application\LogoutAuthUser\LogoutAuthUserRequest;
use App\Modules\User\Application\LogoutAuthUser\LogoutAuthUserUseCase;
use App\Modules\User\Application\RegisterAuthUser\RegisterAuthUserRequest;
use App\Modules\User\Application\RegisterAuthUser\RegisterAuthUserUseCase;
use App\Modules\User\Application\SignOutAuthUser\SignOutAuthUserRequest;
use App\Modules\User\Application\SignOutAuthUser\SignOutAuthUserUseCase;
use App\Modules\User\Domain\AuthToken;
use App\Modules\User\Domain\User;
use App\Tests\DatabaseTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AuthUserTest extends DatabaseTestCase
{
    public function test_register_auth_user_use_case(): void
    {
        $container = static::getContainer();

        $validator = $container->get(ValidatorInterface::class);

        $request = new RegisterAuthUserRequest($validator, 'test@test.com', 'password', 'password', 'First', 'Last',
            'iPhone 15');

        $useCase = $container->get(RegisterAuthUserUseCase::class);

        /** @var \App\Modules\User\Application\RegisterAuthUser\RegisterAuthUserResponse $response */
        $response = $useCase->run($request);

        $this->assertEquals('test@test.com', $response->user->getEmail());

        // Check that we have a token in response.
        $this->assertNotEmpty($response->token);

        // Check that user tokens were persisted in DB.
        $tokenRepository = $this->getRepository(AuthToken::class);
        $tokens = $tokenRepository->findAll();

        $this->assertCount(1, $tokens);

        $this->assertEquals('iPhone 15', $tokens[0]->getName());
        $this->assertEquals('test@test.com', $tokens[0]->getUser()->getEmail());
    }

    public function test_login_auth_user_use_case(): void
    {
        $container = static::getContainer();

        static::$userSeeder->seedUser([
            'email' => 'test@test.com',
            'password' => 'password',
            'firstName' => 'First',
            'lastName' => 'Last',
            'roles' => ['ROLE_USER'],
        ]);

        $request = new LoginAuthUserRequest('test@test.com', 'password', 'iPhone 15');

        $useCase = $container->get(LoginAuthUserUseCase::class);

        /** @var \App\Modules\User\Application\LoginAuthUser\LoginAuthUserResponse $response */
        $response = $useCase->run($request);

        $this->assertEquals('test@test.com', $response->user->getEmail());

        // Check that we have a token in response.
        $this->assertNotEmpty($response->token);

        // Check that user tokens were persisted in DB.
        $tokenRepository = $this->getRepository(AuthToken::class);
        $tokens = $tokenRepository->findAll();

        $this->assertCount(1, $tokens);

        $this->assertEquals('iPhone 15', $tokens[0]->getName());
        $this->assertEquals('test@test.com', $tokens[0]->getUser()->getEmail());
    }

    public function test_register_auth_user_use_case_error_email_already_taken(): void
    {
        $container = static::getContainer();

        static::$userSeeder->seedUser([
            'email' => 'test@test.com',
            'password' => 'password',
            'firstName' => 'First',
            'lastName' => 'Last',
            'roles' => ['ROLE_USER'],
        ]);

        $validator = $container->get(ValidatorInterface::class);

        $request = new RegisterAuthUserRequest($validator, 'test@test.com', 'password', 'password', 'Another', 'More',
            'iPhone 15');

        $useCase = $container->get(RegisterAuthUserUseCase::class);

        $this->expectException(ValidationException::class);
        $useCase->run($request);
    }

    public function test_login_auth_user_use_case_error_user_not_found(): void
    {
        $container = static::getContainer();

        $request = new LoginAuthUserRequest('test@test.com', 'password', 'iPhone 15');

        $useCase = $container->get(LoginAuthUserUseCase::class);

        $this->expectException(UnauthorizedDomainException::class);

        $useCase->run($request);
    }

    public function test_login_auth_user_use_case_error_invalid_password(): void
    {
        $container = static::getContainer();

        static::$userSeeder->seedUser([
            'email' => 'test@test.com',
            'password' => 'password',
            'firstName' => 'First',
            'lastName' => 'Last',
            'roles' => ['ROLE_USER'],
        ]);

        $request = new LoginAuthUserRequest('test@test.com', 'wrong-password', 'iPhone 15');

        $useCase = $container->get(LoginAuthUserUseCase::class);

        $this->expectException(UnauthorizedDomainException::class);

        $useCase->run($request);
    }

    public function test_logout_auth_user_use_case(): void
    {
        $container = static::getContainer();

        // Seed user with two registered devices.
        $user = static::$userSeeder->seedUser([], [
            ['name' => 'iPhone 15'],
            ['name' => 'iPad', 'expiresAfter' => 24 * 60],
        ]);

        // Accessing from the first device.
        $token = $user['user']->getAuthTokens()[0];

        // Logout from the first device.
        $request = new LogoutAuthUserRequest($token->getId());
        $useCase = $container->get(LogoutAuthUserUseCase::class);

        $response = $useCase->run($request);
        $tokens = $response->user->getAuthTokens();

        // Check that we have now only one remaining registered device.
        $this->assertCount(1, $tokens);

        // Check that changes were persisted.
        $tokenRepository = $this->getRepository(AuthToken::class);
        $tokens = $tokenRepository->findAll();

        $this->assertCount(1, $tokens);
    }

    public function test_sign_out_auth_user_use_case(): void
    {
        $container = static::getContainer();

        // Seed user with two registered devices.
        $user = static::$userSeeder->seedUser([], [
            ['name' => 'iPhone 15'],
            ['name' => 'iPad', 'expiresAfter' => 24 * 60],
        ]);

        // Logout from all devices.
        $request = new SignOutAuthUserRequest($user['user']->getId());
        $useCase = $container->get(SignOutAuthUserUseCase::class);

        $response = $useCase->run($request);
        $tokens = $response->user->getAuthTokens();

        // Check that there are no tokens in response.
        $this->assertCount(0, $tokens);

        // Check that tokens were also removed from the DB.
        $tokenRepository = $this->getRepository(AuthToken::class);
        $tokens = $tokenRepository->findAll();

        $this->assertCount(0, $tokens);
    }

    public function test_change_password_auth_user_use_case(): void
    {
        $container = static::getContainer();

        $validator = $container->get(ValidatorInterface::class);

        $user = static::$userSeeder->seedUser([], [
            ['name' => 'iPhone 15', 'expiresAfter' => null],
        ]);

        $request = new ChangePasswordRequest($validator, $user['user']->getId(), 'password', 'new-password',
            'new-password');
        $useCase = $container->get(ChangePasswordUseCase::class);

        /** @var \App\Modules\User\Application\ChangePassword\ChangePasswordResponse $response */
        $response = $useCase->run($request);

        $this->assertEquals($user['user']->getId(), $response->user->getId());
    }

    public function test_delete_auth_user_account_use_case(): void
    {
        $container = static::getContainer();

        $user = static::$userSeeder->seedUser([], [
            ['name' => 'iPhone 15', 'expiresAfter' => null],
        ]);

        // Delete user and auth tokens.
        $request = new DeleteAuthUserRequest($user['user']->getId(), 'password');
        $useCase = $container->get(DeleteAuthUserUseCase::class);

        /** @var \App\Modules\User\Application\DeleteAuthUser\DeleteAuthUserResponse $response */
        $response = $useCase->run($request);

        $this->assertEquals('User account deleted successfully', $response->message);

        // Check that both user and token repositories were updated.
        $userRepository = $this->getRepository(User::class);
        $users = $userRepository->findAll();
        $this->assertCount(0, $users);

        $authTokenRepository = $this->getRepository(AuthToken::class);
        $authTokens = $authTokenRepository->findAll();
        $this->assertCount(0, $authTokens);
    }

    public function test_delete_auth_user_account_use_case_error_wrong_password(): void
    {
        $container = static::getContainer();

        $user = static::$userSeeder->seedUser([], [
            ['name' => 'iPhone 15', 'expiresAfter' => null],
        ]);

        // Try to delete account providing wrong current password.
        $request = new DeleteAuthUserRequest($user['user']->getId(), 'wrong-password');
        $useCase = $container->get(DeleteAuthUserUseCase::class);

        // Check that the request fails.
        $this->expectException(FormValidationException::class);

        $useCase->run($request);
    }
}
