<?php
declare(strict_types=1);

namespace App\Modules\Greeting\Infrastructure\Persistence\Doctrine;

use App\Modules\Greeting\Domain\Greeting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class GreetingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Greeting::class);
    }

    /**
     * @param \App\Modules\Greeting\Domain\Greeting $greeting
     * @return void
     */
    public function save(Greeting $greeting): void
    {
        $this->getEntityManager()->persist($greeting);
        $this->getEntityManager()->flush();
    }

    /**
     * @param \App\Modules\Greeting\Domain\Greeting $greeting
     * @return void
     */
    public function delete(Greeting $greeting): void
    {
        $this->getEntityManager()->remove($greeting);
        $this->getEntityManager()->flush();
    }

    /**
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function findOrdered(int $limit = 10, int $offset = 0): array
    {
        $builder = $this->createQueryBuilder('g');

        $builder->addSelect('COALESCE(g.updatedAt, g.createdAt) AS HIDDEN orderColumn')->orderBy('orderColumn', 'DESC')
                ->setFirstResult($offset)->setMaxResults($limit);

        return $builder->getQuery()->getResult();
    }
}
