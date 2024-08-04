<?php
declare(strict_types=1);

namespace App\Modules\User\Infrastructure\Security;

use App\Modules\User\Domain\User;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class AuthUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    private User $user;

    private ?string $deviceId;

    public function __construct(User $user, ?string $deviceId = null)
    {
        $this->user = $user;
        $this->deviceId = $deviceId;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->user->getId();
    }

    /**
     * @return \App\Modules\User\Domain\User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @param \App\Modules\User\Domain\User $user
     * @return $this
     */
    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return array
     */
    public function getRoles(): array
    {
        $roles = $this->user->getRoles();
        // Guarantee every user at least has ROLE_USER.
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param array $roles
     * @return $this
     */
    public function setRoles(array $roles): self
    {
        $this->user->setRoles($roles);

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->user->getEmail();
    }

    public function getPassword(): ?string
    {
        return $this->user->getPassword();
    }

    public function getDeviceId(): ?string
    {
        return $this->deviceId;
    }

    public function eraseCredentials(): void
    {
        $this->user->setPassword(null);
    }
}
