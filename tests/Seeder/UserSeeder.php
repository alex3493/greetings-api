<?php

namespace App\Tests\Seeder;

use App\Modules\Shared\Domain\ValueObject\EntityId;
use App\Modules\User\Domain\AuthToken;
use App\Modules\User\Domain\RefreshToken;
use App\Modules\User\Domain\User;
use App\Modules\User\Infrastructure\Security\AuthUser;
use DateInterval;
use DateTime;
use Doctrine\Persistence\ObjectManager;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserSeeder extends SeederBase
{
    private UserPasswordHasherInterface $passwordHasher;

    private JWTTokenManagerInterface $JWTManager;

    public function __construct(
        ObjectManager $manager, UserPasswordHasherInterface $passwordHasher, JWTTokenManagerInterface $JWTManager
    ) {
        parent::__construct($manager);

        $this->passwordHasher = $passwordHasher;
        $this->JWTManager = $JWTManager;
    }

    /**
     * @param array $options
     * @param array $withTokens - if not empty, create a user ready to access private pages.
     * @param bool $withJwt
     * @return array
     * @throws \Random\RandomException
     * @throws \App\Modules\Shared\Domain\Exception\UnprocessableEntityDomainException
     */
    public function seedUser(array $options = [], array $withTokens = [], bool $withJwt = false): array
    {
        $options = array_merge([
            'email' => 'test@example.com',
            'password' => 'password',
            'firstName' => 'John',
            'lastName' => 'Doe',
            'roles' => ['ROLE_USER'],
        ], $options);

        $user = User::create($options['email'], $options['password'], $options['firstName'], $options['lastName'],
            $options['roles']);

        $authUser = new AuthUser($user);

        $hashedPassword = $this->passwordHasher->hashPassword($authUser, $options['password']);
        $user->setPassword($hashedPassword);

        /** @var \App\Modules\User\Infrastructure\Persistence\Doctrine\UserRepository $repository */
        $repository = $this->objectManager->getRepository(User::class);

        $repository->save($user);

        // We have a user at this point. No tokens were created yet, so user must log in
        // prior to access private pages.

        // Create device tokens if need be. We can pass multiple devices, so that multiple
        // tokens are created.
        // When in test method we can get user tokens: $user->getTokens()
        if ($withTokens) {
            $tokenRepository = $this->objectManager->getRepository(AuthToken::class);
            foreach ($withTokens as $withToken) {
                $token = bin2hex(random_bytes(32));

                $withToken = array_merge([
                    'isExpired' => false,
                    'expiresAfter' => null,
                    'name' => 'web',
                ], $withToken);

                $expiresAfter = $withToken['expiresAfter'] ?? null;
                $expiresAt = $withToken['isExpired'] ? new DateTime('yesterday noon') : ($expiresAfter > 0 ? (new DateTime())->add(new DateInterval("PT{$expiresAfter}M")) : null);
                $authToken = new AuthToken(EntityId::create(), $user, $token, $withToken['name'], new DateTime(), null,
                    $expiresAt);

                $tokenRepository->save($authToken);

                $user->addAuthToken($authToken);
            }

            $repository->save($user);
        }

        if ($withJwt) {
            $JWTToken = $this->JWTManager->create($authUser);
            $JWTRefreshToken = bin2hex(random_bytes(64));

            $refreshTokenRepository = $this->objectManager->getRepository(RefreshToken::class);
            $refreshToken = new RefreshToken();

            $refreshToken->setUsername($user->getEmail());
            $refreshToken->setRefreshToken($JWTRefreshToken);
            $refreshToken->setValid(new DateTime('+1 hours'));

            $refreshTokenRepository->save($refreshToken);

            // Add test refresh token for created user.
            //$connection = $this->objectManager->getConnection();
            //
            //$connection->insert('refresh_tokens', [
            //    'refresh_token' => $JWTRefreshToken,
            //    'username' => $user->getEmail(),
            //    'valid' => (new DateTime('+1 hours'))->format('Y-m-d H:i:s'),
            //]);
        }

        return [
            'user' => $user,
            'app_token' => isset($user->getAuthTokens()[0]) // first valid app token (if any)
                ? $user->getAuthTokens()[0]->getToken() : false,
            'jwt_token' => $JWTToken ?? false,
            'refresh_token' => $JWTRefreshToken ?? false,
        ];
    }
}
