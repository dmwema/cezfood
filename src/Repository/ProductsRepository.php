<?php

namespace App\Repository;

use App\Entity\Products;
use App\Entity\Users;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use http\Client\Curl\User;

/**
 * @method Products|null find($id, $lockMode = null, $lockVersion = null)
 * @method Products|null findOneBy(array $criteria, array $orderBy = null)
 * @method Products[]    findAll()
 * @method Products[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Products::class);
    }

    public function findProductsPaginated(int $page, string $slug, int $limit = 6): array
    {
        $limit = abs($limit);

        $result = [];

        $query = $this->getEntityManager()->createQueryBuilder()
            ->select('c', 'p')
            ->from('App\Entity\Products', 'p')
            ->join('p.categories', 'c')
            ->where("c.slug = '$slug'")
            ->setMaxResults($limit)
            ->setFirstResult(($page * $limit) - $limit);

        $paginator = new Paginator($query);
        $data = $paginator->getQuery()->getResult();
        
        //On vérifie qu'on a des données
        if(empty($data)){
            return $result;
        }

        //On calcule le nombre de pages
        $pages = ceil($paginator->count() / $limit);

        // On remplit le tableau
        $result['data'] = $data;
        $result['pages'] = $pages;
        $result['page'] = $page;
        $result['limit'] = $limit;

        return $result;
    }


    /**
     * @return Products[] Returns an array of Products objects
     */
    public function findSuggestions(Users $user): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.ordersDetails', 'od')
            ->leftJoin('od.orders', 'o')
            ->leftJoin('o.users', 'u')
            ->andWhere('u.city = :val')
            ->andWhere('u.id != :uid')
            ->setParameter('val', $user->getCity())
            ->setParameter('uid', $user->getId())
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
            ;
    }

    /**
     * @return Products[] Returns an array of Products objects
     */
    public function findSimilars(Products $products): array
    {
        $productsData = $this->createQueryBuilder('p')
            ->leftJoin('p.categories', 'c')
            ->where('c.id = :cat')
            ->orWhere('LOWER(p.mark) = LOWER(:mark)')
            ->setParameter('cat', $products->getCategories()->getId())
            ->setParameter('mark', $products->getMark())
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
        $return = [];
        foreach ($productsData as $similar) {
            if (
                ($similar->getCategories() === $products->getCategories() && $similar->getId() !== $products->getId() && $similar->getPrice() > $products->getPrice())
                || ($similar->getCategories() !== $products->getCategories() && $similar->getMark() === $products->getMark())
            ) {
                $return[] = $similar;
            }
        }
        return $return;
    }
    /*
    public function findOneBySomeField($value): ?Products
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
