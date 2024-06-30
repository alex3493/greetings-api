<?php
declare(strict_types=1);

namespace App\Modules\User\Infrastructure\Persistence;

use App\Modules\Shared\Domain\Exception\NotFoundDomainException;
use App\Modules\Shared\Domain\Exception\ValidationException;
use App\Modules\User\Domain\Contract\UserServiceInterface;
use App\Modules\User\Domain\RefreshToken;
use App\Modules\User\Domain\User;
use App\Modules\User\Infrastructure\Persistence\Doctrine\UserRepository;
use App\Modules\User\Infrastructure\Security\AuthUser;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserService implements UserServiceInterface
{
    private UserRepository $repository;

    private UserPasswordHasherInterface $passwordHasher;

    private ValidatorInterface $validator;

    public function __construct(
        UserRepository $repository, UserPasswordHasherInterface $passwordHasher, ValidatorInterface $validator
    ) {
        $this->repository = $repository;
        $this->passwordHasher = $passwordHasher;
        $this->validator = $validator;
    }

    /**
     * @param string $email
     * @param string|null $password
     * @param string|null $firstName
     * @param string|null $lastName
     * @param array $roles
     * @return \App\Modules\User\Domain\User
     * @throws \App\Modules\Shared\Domain\Exception\UnprocessableEntityDomainException
     * @throws \App\Modules\Shared\Domain\Exception\ValidationException
     */
    public function create(string $email, ?string $password, ?string $firstName, ?string $lastName, array $roles = []
    ): User {
        // If no user roles provided we always add default user role.
        if (empty($roles)) {
            $roles = ['ROLE_USER'];
        }

        $user = User::create($email, $password, $firstName, $lastName, $roles);

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            throw new ValidationException($errors);
        }

        $authUser = new AuthUser($user);

        $hashedPassword = $this->passwordHasher->hashPassword($authUser, $password);
        $user->setPassword($hashedPassword);

        $this->repository->save($user);

        return $user;
    }

    /**
     * @param string $id
     * @param string|null $firstName
     * @param string|null $lastName
     * @return \App\Modules\User\Domain\User
     * @throws \App\Modules\Shared\Domain\Exception\NotFoundDomainException
     */
    public function update(string $id, ?string $firstName, ?string $lastName): User
    {
        $user = $this->repository->find($id);
        $this->repository->refresh($user);

        if (is_null($user)) {
            throw new NotFoundDomainException('User not found');
        }

        $user->setFirstName($firstName);
        $user->setLastName($lastName);

        $this->repository->save($user);

        return $user;
    }

    /**
     * @param string $id
     * @return void
     * @throws \App\Modules\Shared\Domain\Exception\NotFoundDomainException
     * @throws \Doctrine\ORM\Exception\NotSupported
     */
    public function logout(string $id): void
    {
        /** @var \App\Modules\User\Domain\User $user */
        $user = $this->repository->find($id);
        $this->repository->refresh($user);

        if (is_null($user)) {
            throw new NotFoundDomainException('User not found');
        }

        /** @var \App\Modules\User\Domain\Contract\RefreshTokenRepositoryInterface $refreshTokenRepository */
        $refreshTokenRepository = $this->repository->getRelatedRepository(RefreshToken::class);

        $tokens = $refreshTokenRepository->findByUser($user->getUserIdentifier());
        foreach ($tokens as $token) {
            $refreshTokenRepository->delete($token);
        }
    }

    /**
     * @param string $id
     * @return void
     * @throws \App\Modules\Shared\Domain\Exception\NotFoundDomainException
     */
    public function delete(string $id): void
    {
        $user = $this->repository->find($id);

        if (is_null($user)) {
            throw new NotFoundDomainException('User not found');
        }

        // Delete from repository.
        $this->repository->delete($user);
    }

    public function save(User $user): User
    {
        $this->repository->save($user);

        return $user;
    }

    public function findByEmail(string $email): ?User
    {
        return $this->repository->findOneBy(['email' => $email]);
    }

    public function findById(string $id): ?User
    {
        return $this->repository->find($id);
    }

    public function freshUserById(string $id): ?User
    {
        $user = $this->repository->find($id);
        $this->repository->refresh($user);

        return $user;
    }
}
