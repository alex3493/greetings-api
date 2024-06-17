<?php
declare(strict_types=1);

namespace App\Modules\User\Domain\Contract;

use App\Modules\User\Domain\User;

interface UserServiceInterface
{
    public function create(string $email, ?string $password, ?string $firstName, ?string $lastName, array $roles = []
    ): User;

    public function update(string $id, ?string $firstName, ?string $lastName): User;

    public function logout(string $id): void;

    public function delete(string $id): void;

    public function save(User $user): User;

    public function findByEmail(string $email): ?User;

    public function findById(string $id): ?User;

    public function freshUserById(string $id): ?User;
}
