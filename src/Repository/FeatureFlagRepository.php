<?php

namespace Devexploris\ShizukuFeatureFlags\Repository;

use Devexploris\ShizukuFeatureFlags\Entity\FeatureFlag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FeatureFlag>
 */
class FeatureFlagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeatureFlag::class);
    }

}
