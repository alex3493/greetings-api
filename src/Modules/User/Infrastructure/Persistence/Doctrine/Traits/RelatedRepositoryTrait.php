<?php

namespace App\Modules\User\Infrastructure\Persistence\Doctrine\Traits;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\NotSupported;

trait RelatedRepositoryTrait
{
    /**
     * @param $className
     * @return \Doctrine\ORM\EntityRepository
     * @throws \Doctrine\ORM\Exception\NotSupported
     */
    public function getRelatedRepository($className): EntityRepository
    {
        if (! isset($this->_em)) {
            throw new NotSupported('RelatedRepositoryTrait can only be used with EntityRepository class or its descendants');
        }

        return $this->_em->getRepository($className);
    }
}
