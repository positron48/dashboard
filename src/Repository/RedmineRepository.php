<?php

namespace App\Repository;

use App\Entity\Redmine;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Redmine|null find($id, $lockMode = null, $lockVersion = null)
 * @method Redmine|null findOneBy(array $criteria, array $orderBy = null)
 * @method Redmine[]    findAll()
 * @method Redmine[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RedmineRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Redmine::class);
    }

    // /**
    //  * @return Redmine[] Returns an array of Redmine objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('r.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Redmine
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
