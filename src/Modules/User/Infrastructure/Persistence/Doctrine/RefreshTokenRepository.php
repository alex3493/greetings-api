<?php

namespace App\Modules\User\Infrastructure\Persistence\Doctrine;

use App\Modules\User\Domain\Contract\RefreshTokenRepositoryInterface;
use App\Modules\User\Domain\RefreshToken;

class RefreshTokenRepository extends \Gesdinet\JWTRefreshTokenBundle\Entity\RefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    public function save(RefreshToken $refreshToken): void
    {
        $this->getEntityManager()->persist($refreshToken);
        $this->getEntityManager()->flush();
    }

    public function delete(RefreshToken $refreshToken): void
    {
        $this->getEntityManager()->remove($refreshToken);
        $this->getEntityManager()->flush();
    }

    public function findByUser(string $username): array
    {
        return $this->findBy([
            'username' => $username,
        ], ['valid' => 'DESC']);
    }
}
