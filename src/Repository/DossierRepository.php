<?php
// src/Repository/DossierRepository.php

namespace App\Repository;

use App\Entity\Dossier;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DossierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Dossier::class);
    }

    /**
     * Dossier en brouillon de l'utilisateur connecté.
     */
    public function findBrouillonByUser(User $user): ?Dossier
    {
        return $this->findOneBy(['user' => $user, 'statut' => Dossier::STATUT_BROUILLON]);
    }

    /**
     * Tous les dossiers soumis, triés par date décroissante.
     */
    public function findAllSoumis(): array
    {
        return $this->createQueryBuilder('d')
            ->join('d.user', 'u')
            ->addSelect('u')
            ->where('d.statut = :statut')
            ->setParameter('statut', Dossier::STATUT_SOUMIS)
            ->orderBy('d.soumisAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche full-text sur nom / prénom / email / BTS.
     */
    public function search(string $term, ?string $statut = null, ?string $bts = null): array
    {
        $qb = $this->createQueryBuilder('d')
            ->join('d.user', 'u')
            ->addSelect('u')
            ->orderBy('d.createdAt', 'DESC');

        if ($term) {
            $qb->andWhere('LOWER(d.nom) LIKE :q OR LOWER(d.prenom) LIKE :q OR LOWER(u.email) LIKE :q OR LOWER(d.btsChoisi) LIKE :q')
               ->setParameter('q', '%' . strtolower($term) . '%');
        }

        if ($statut) {
            $qb->andWhere('d.statut = :statut')->setParameter('statut', $statut);
        }

        if ($bts) {
            $qb->andWhere('d.btsChoisi = :bts')->setParameter('bts', $bts);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Statistiques pour le tableau de bord admin.
     */
    public function getStats(): array
    {
        $result = $this->createQueryBuilder('d')
            ->select('d.statut, COUNT(d.id) as nb')
            ->groupBy('d.statut')
            ->getQuery()
            ->getResult();

        $stats = [
            'total'     => 0,
            'brouillon' => 0,
            'soumis'    => 0,
            'valide'    => 0,
            'refuse'    => 0,
        ];

        foreach ($result as $row) {
            $stats[$row['statut']] = (int) $row['nb'];
            $stats['total'] += (int) $row['nb'];
        }

        // Statistiques par BTS
        $parBts = $this->createQueryBuilder('d')
            ->select('d.btsChoisi as bts, COUNT(d.id) as nb')
            ->where('d.btsChoisi IS NOT NULL')
            ->groupBy('d.btsChoisi')
            ->orderBy('nb', 'DESC')
            ->getQuery()
            ->getResult();

        $stats['par_bts'] = $parBts;

        return $stats;
    }

    /**
     * Dossiers récents (7 derniers jours).
     */
    public function findRecents(int $limit = 10): array
    {
        return $this->createQueryBuilder('d')
            ->join('d.user', 'u')
            ->addSelect('u')
            ->where('d.createdAt >= :since')
            ->setParameter('since', new \DateTimeImmutable('-7 days'))
            ->orderBy('d.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
