<?php
declare(strict_types=1);

namespace App\Modules\User\Infrastructure\Security;

use App\Modules\Shared\Domain\Exception\AccessDeniedDomainException;
use App\Modules\Shared\Domain\Exception\FormValidationException;
use App\Modules\Shared\Domain\Exception\NotFoundDomainException;
use App\Modules\Shared\Domain\Exception\UnauthorizedDomainException;
use App\Modules\Shared\Domain\Exception\ValidationException;
use App\Modules\User\Domain\Contract\AuthTokenServiceInterface;
use App\Modules\User\Domain\Contract\AuthUserServiceInterface;
use App\Modules\User\Domain\Contract\UserServiceInterface;
use App\Modules\User\Domain\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AuthUserService implements AuthUserServiceInterface
{
    private UserServiceInterface $userService;

    private UserPasswordHasherInterface $passwordHasher;

    private AuthTokenServiceInterface $tokenService;

    private ValidatorInterface $validator;

    private LoggerInterface $logger;

    public function __construct(
        UserServiceInterface $userService, UserPasswordHasherInterface $passwordHasher,
        AuthTokenServiceInterface $tokenService, ValidatorInterface $validator, LoggerInterface $logger
    ) {
        $this->userService = $userService;
        $this->passwordHasher = $passwordHasher;
        $this->tokenService = $tokenService;
        $this->validator = $validator;
        $this->logger = $logger;
    }

    /**
     * @param string $email
     * @param string $password
     * @param string|null $firstName
     * @param string|null $lastName
     * @param string|null $deviceName
     * @return array
     * @throws \App\Modules\Shared\Domain\Exception\UnprocessableEntityDomainException
     * @throws \App\Modules\Shared\Domain\Exception\ValidationException
     */
    public function register(
        string $email, string $password, ?string $firstName, ?string $lastName, ?string $deviceName = null
    ): array {
        $user = User::create($email, $password, $firstName, $lastName, ['ROLE_USER']);

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            throw new ValidationException($errors);
        }

        $authUser = new AuthUser($user);

        $hashedPassword = $this->passwordHasher->hashPassword($authUser, $password);
        $user->setPassword($hashedPassword);

        $deviceName = $deviceName ?? 'web';
        $authToken = $this->tokenService->generateAndSaveToken($user, $deviceName);

        $user->addAuthToken($authToken);

        $this->userService->save($user);

        return [$user, $authToken->getToken()];
    }

    /**
     * @param string $email
     * @param string $password
     * @param string|null $deviceName
     * @return array
     * @throws \App\Modules\Shared\Domain\Exception\UnauthorizedDomainException
     */
    public function login(string $email, string $password, ?string $deviceName = null): array
    {
        $user = $this->userService->findByEmail($email);

        if (is_null($user)) {
            throw new UnauthorizedDomainException('Invalid credentials');
        }

        $authUser = new AuthUser($user);

        if (! $this->passwordHasher->isPasswordValid($authUser, $password)) {
            throw new UnauthorizedDomainException('Invalid credentials');
        }

        $deviceName = $deviceName ?? 'web';

        $existing = $this->tokenService->existing($user, $deviceName);

        if (! is_null($existing)) {
            $this->tokenService->delete($existing);
        }

        $authToken = $this->tokenService->generateAndSaveToken($user, $deviceName);

        $user->addAuthToken($authToken);
        $this->userService->save($user);

        return [$user, $authToken->getToken()];
    }

    /**
     * @param string $tokenId
     * @return \App\Modules\User\Domain\User
     * @throws \App\Modules\Shared\Domain\Exception\NotFoundDomainException
     */
    public function logout(string $tokenId): User
    {
        /** @var ?\App\Modules\User\Domain\AuthToken $token */
        $token = $this->tokenService->find($tokenId);
        if (is_null($token)) {
            throw new NotFoundDomainException('Token not found');
        }

        $userId = $token->getUser()->getId();
        $user = $this->userService->freshUserById($userId);

        $user->removeAuthToken($token);
        $this->userService->save($user);

        $this->tokenService->delete($token);

        return $user;
    }

    /**
     * @param string $userId
     * @return \App\Modules\User\Domain\User
     * @throws \App\Modules\Shared\Domain\Exception\AccessDeniedDomainException
     */
    public function signOut(string $userId): User
    {
        $user = $this->userService->freshUserById($userId);

        if (is_null($user)) {
            throw new AccessDeniedDomainException('User not found');
        }

        $user->removeAllAuthTokens();
        $this->userService->save($user);

        return $user;
    }

    /**
     * @param string $userId
     * @param string $currentPassword
     * @param string $password
     * @return \App\Modules\User\Domain\User
     * @throws \App\Modules\Shared\Domain\Exception\AccessDeniedDomainException
     * @throws \App\Modules\Shared\Domain\Exception\FormValidationException
     */
    public function changePassword(string $userId, string $currentPassword, string $password): User
    {
        // When we change password we check that the current password provided in request is valid.
        // We must get fresh user here because user password was already erased in
        // authentication manager.
        $user = $this->userService->freshUserById($userId);

        if (is_null($user)) {
            throw new AccessDeniedDomainException('User not found');
        }

        $authUser = new AuthUser($user);

        if (! $this->passwordHasher->isPasswordValid($authUser, $currentPassword)) {
            throw new FormValidationException('Invalid credentials', [
                [
                    'property' => 'currentPassword',
                    'errors' => ['Wrong value for your current password.'],
                    'context' => 'User',
                ],
            ]);
        }

        $hashedPassword = $this->passwordHasher->hashPassword($authUser, $password);
        $user->setPassword($hashedPassword);

        $this->userService->save($user);

        return $user;
    }

    /**
     * @param string $id
     * @param string $password
     * @return void
     * @throws \App\Modules\Shared\Domain\Exception\AccessDeniedDomainException
     * @throws \App\Modules\Shared\Domain\Exception\FormValidationException
     */
    public function deleteAccount(string $id, string $password): void
    {
        // When we delete account we check that the password provided in request is valid.
        // We must get fresh user here because user password was already erased in
        // authentication manager.
        $user = $this->userService->freshUserById($id);

        if (is_null($user)) {
            throw new AccessDeniedDomainException('User not found');
        }

        $authUser = new AuthUser($user);

        if (! $this->passwordHasher->isPasswordValid($authUser, $password)) {
            throw new FormValidationException('Invalid credentials', [
                [
                    'property' => 'password',
                    'errors' => ['Wrong value for your current password.'],
                    'context' => 'User',
                ],
            ]);
        }

        $this->userService->delete($id);
    }
}
