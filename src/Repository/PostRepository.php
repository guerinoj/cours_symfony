<?php

namespace App\Repository;

use App\Entity\Post;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Post>
 */
class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    /**
     * Recherche des posts publiés avec filtres optionnels
     *
     * @param string|null $search Terme de recherche (titre, contenu, auteur)
     * @param string|null $category Nom de la catégorie
     * @return Post[] Tableau des posts correspondants
     */
    public function findPublishedWithFilters(?string $search = null, ?string $category = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.author', 'a') // Jointure avec l'auteur pour la recherche
            ->where('p.is_published = :published')
            ->setParameter('published', true)
            ->orderBy('p.createdAt', 'DESC');

        // Ajouter le filtre de recherche textuelle si fourni
        if (!empty($search)) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('LOWER(p.title)', 'LOWER(:search)'),
                    $qb->expr()->like('LOWER(p.content)', 'LOWER(:search)'),
                    $qb->expr()->like('LOWER(a.name)', 'LOWER(:search)')
                )
            )
            ->setParameter('search', '%' . $search . '%');
        }

        // Ajouter le filtre par catégorie si fourni
        if (!empty($category)) {
            $qb->innerJoin('p.categories', 'c')
                ->andWhere('c.name = :categoryName')
                ->setParameter('categoryName', $category);
        }

        return $qb->getQuery()->getResult();
    }

//    /**
//     * @return Post[] Returns an array of Post objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Post
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
