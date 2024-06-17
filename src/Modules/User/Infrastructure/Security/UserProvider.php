<?php
declare(strict_types=1);

namespace App\Modules\User\Infrastructure\Security;

use App\Modules\User\Domain\Contract\UserServiceInterface;
use App\Modules\User\Domain\User;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    private UserServiceInterface $userService;

    public function __construct(UserServiceInterface $userService)
    {
        $this->userService = $userService;
    }

    /**
     * @inheritDoc
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (! $user instanceof User) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', get_class($user)));
        }

        return new AuthUser($this->userService->freshUserById($user->getId()));
    }

    /**
     * @inheritDoc
     */
    public function supportsClass(string $class): bool
    {
        return AuthUser::class === $class || is_subclass_of($class, AuthUser::class);
    }

    /**
     * @inheritDoc
     */
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        // TODO: check if findByEmail works in all scenarios.
        // return new AuthUser($this->userService->findById($identifier));
        $user = $this->userService->findByEmail($identifier);
        if (is_null($user)) {
            throw new UserNotFoundException('Invalid credentials');
        }

        return new AuthUser($this->userService->findByEmail($identifier));
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if ($user instanceof AuthUser) {
            $userEntity = $user->getUser();

            $userEntity->setPassword($newHashedPassword);
            $this->userService->save($userEntity);

            $user->setUser($userEntity);
        }
    }
}
