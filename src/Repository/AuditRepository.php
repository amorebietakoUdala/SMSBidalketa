<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class AuditRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, \App\Entity\Audit::class);
    }

    public function findByTimestamp(array $criteria = null)
    {
        $criteria = $this->__remove_blank_filters($criteria);
        $qb = $this->createQueryBuilder('a');
        if (array_key_exists('fromDate', $criteria) && null !== $criteria['fromDate']) {
            $qb->andWhere('a.timestamp >= :fromDate');
            $qb->setParameter('fromDate', \DateTime::createFromFormat('Y-m-d H:i', $criteria['fromDate']));
            unset($criteria['fromDate']);
        }
        if (array_key_exists('toDate', $criteria) && null !== $criteria['toDate']) {
            $qb->andWhere('a.timestamp <= :toDate');
            $qb->setParameter('toDate', \DateTime::createFromFormat('Y-m-d H:i', $criteria['toDate']));
            unset($criteria['toDate']);
        }
        $criteriaEqualFields = ['user'];
        $criteriaEqual = $this->__filterCriteria($criteria, $criteriaEqualFields);
        $qb = $this->__addEqualCriteria($qb, $criteriaEqual);

        $criteriaLikeFields = ['telephones'];
        $criteriaLike = $this->__filterCriteria($criteria, $criteriaLikeFields);
        $qb = $this->__addLikeCriteria($qb, $criteriaLike);
        $result = $qb->getQuery()->getResult();

        return $result;
    }

    private function __addEqualCriteria($qb, array $criteriaEqual)
    {
        if (count($criteriaEqual)) {
            foreach ($criteriaEqual as $field => $value) {
                $qb->andWhere('a.'.$field.' = :'.$field)
                    ->setParameter($field, $value);
            }
        }

        return $qb;
    }

    private function __addLikeCriteria($qb, array $criteriaLike)
    {
        if (count($criteriaLike)) {
            foreach ($criteriaLike as $field => $value) {
                $qb->andWhere('a.'.$field.' LIKE :'.$field)
                        ->setParameter($field, '%'.$value.'%');
            }
        }

        return $qb;
    }

    private function __filterCriteria(array $criteria, $filteredFields)
    {
        $filteredCriteria = array_filter(
            $criteria,
            function ($key) use ($filteredFields) {
                return in_array($key, $filteredFields);
            },
            ARRAY_FILTER_USE_KEY
        );

        return $filteredCriteria;
    }

    private function __remove_blank_filters($criteria)
    {
        $new_criteria = [];
        foreach ($criteria as $key => $value) {
            if (!empty($value)) {
                $new_criteria[$key] = $value;
            }
        }

        return $new_criteria;
    }
}
