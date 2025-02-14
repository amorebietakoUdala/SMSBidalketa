<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AuditRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, \App\Entity\Audit::class);
    }

    public function findByDeliveryId(string $deliveryId)
    {
        $qb = $this->createQueryBuilder('a');
        return $this->addDeliveryIdEquals($qb, $deliveryId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findDeliveryIdGTForProvider(string $provider, string $deliveryId)
    {
        $qb = $this->createQueryBuilder('a');
        $qb = $this->addDeliveryIdGT($qb, $deliveryId);
        $qb = $this->addProviderEquals($qb, $provider);
        return $qb->getQuery()->getResult();
    }

    private function addProviderEquals($qb, $provider)
    {
        return $qb->andWhere('a.provider = :provider')
            ->setParameter('provider', $provider);
    }

    private function addDeliveryIdEquals($qb, $deliveryId)
    {
        return $qb->andWhere('a.deliveryId = :deliveryId')
            ->setParameter('deliveryId', $deliveryId);
    }

    private function addDeliveryIdGT($qb, $deliveryId)
    {
        return $qb->andWhere('a.deliveryId > :deliveryId')
            ->setParameter('deliveryId', $deliveryId);
    }

    public function findByTimestamp(array $criteria = null, array $order = null, $limit = null)
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
        if (null === $order) {
            $qb->orderBy('a.timestamp', 'DESC');
        } else {
            $qb->orderBy(array_key_first($order), \ARRAY_VALUE[array_key_first($order)]);
        }
        $result = $qb->getQuery()->setMaxResults($limit)->getResult();

        return $result;
    }


    private function __addEqualCriteria($qb, array $criteriaEqual)
    {
        if (count($criteriaEqual)) {
            foreach ($criteriaEqual as $field => $value) {
                $qb->andWhere('a.' . $field . ' = :' . $field)
                    ->setParameter($field, $value);
            }
        }

        return $qb;
    }

    private function __addLikeCriteria($qb, array $criteriaLike)
    {
        if (count($criteriaLike)) {
            foreach ($criteriaLike as $field => $value) {
                $qb->andWhere('a.' . $field . ' LIKE :' . $field)
                    ->setParameter($field, '%' . $value . '%');
            }
        }

        return $qb;
    }

    private function __filterCriteria(array $criteria, $filteredFields)
    {
        $filteredCriteria = array_filter(
            $criteria,
            fn($key) => in_array($key, $filteredFields),
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
