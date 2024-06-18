<?php
declare(strict_types=1);

namespace App\Modules\User\Domain;

use App\Modules\Shared\Domain\ValueObject\Email;
use App\Modules\Shared\Domain\ValueObject\EntityId;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class User
{
    private string $id;

    private string $email;

    private ?string $password;

    private ?string $firstName;

    private ?string $lastName;

    /**
     * @var array<string>
     */
    private array $roles = [];

    /**
     * @var \Doctrine\Common\Collections\Collection<\App\Modules\User\Domain\AuthToken>
     */
    private Collection $authTokens;

    private DateTime $createdAt;

    public function __construct(
        EntityId $id, Email $email, string $password, ?string $firstName, ?string $lastName, array $roles,
        DateTime $createdAt
    ) {
        $this->id = $id->getValue();
        $this->email = $email->getValue();
        $this->password = $password;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->roles = $roles;
        $this->createdAt = $createdAt;

        $this->authTokens = new ArrayCollection();
    }

    /**
     * @param string $email
     * @param string $password
     * @param string|null $firstName
     * @param string|null $lastName
     * @param array $roles
     * @return \App\Modules\User\Domain\User
     * @throws \App\Modules\Shared\Domain\Exception\UnprocessableEntityDomainException
     */
    public static function create(
        string $email, string $password, ?string $firstName, ?string $lastName, array $roles = []
    ): User {
        return new self(EntityId::create(), new Email($email), $password, $firstName, $lastName, $roles,
            new DateTime());
    }

    /**
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param \App\Modules\Shared\Domain\ValueObject\Email $email
     * @return $this
     */
    public function setEmail(Email $email): User
    {
        $this->email = $email->getValue();

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * @param string|null $password
     * @return $this
     */
    public function setPassword(?string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    /**
     * @param string|null $firstName
     * @return $this
     */
    public function setFirstName(?string $firstName): User
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    /**
     * @param string|null $lastName
     * @return $this
     */
    public function setLastName(?string $lastName): User
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * @return array
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
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
        $this->roles = $roles;

        return $this;
    }

    /**
     * @return string
     */
    public function getRole(): string
    {
        return $this->getRoles()[0];
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAuthTokens(): Collection
    {
        // Regular getter.
        // return $this->authTokens;

        /** @link https://github.com/api-platform/core/issues/285 */

        // Workaround: make sure we always get a plain array collection.
        // ArrayCollection::removeElement() method may introduce wrong indices in resulting
        // normalized array.
        // We have to find a better solution and revert immediately if we find cases when
        // $this->authTokens is a PersistentCollection at this point. The code below may
        // totally break persistent collections...
        return new ArrayCollection(array_values($this->authTokens->toArray()));
    }

    // Reserved for cases when we have to reinit collection just before normalizing result.
    //public function importAuthTokens(array $authTokens): void
    //{
    //    $this->authTokens = new ArrayCollection(array_values($authTokens));
    //}

    /**
     * @param \App\Modules\User\Domain\AuthToken $authToken
     * @return $this
     */
    public function addAuthToken(AuthToken $authToken): self
    {
        $this->authTokens->add($authToken);

        return $this;
    }

    /**
     * @param \App\Modules\User\Domain\AuthToken $authToken
     * @return $this
     */
    public function removeAuthToken(AuthToken $authToken): self
    {
        $this->authTokens->removeElement($authToken);

        return $this;
    }

    /**
     * @return $this
     */
    public function removeAllAuthTokens(): self
    {
        $this->authTokens->clear();

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /**
     * @return string
     */
    public function getDisplayName(): string
    {
        return $this->firstName.' '.$this->lastName;
    }
}
