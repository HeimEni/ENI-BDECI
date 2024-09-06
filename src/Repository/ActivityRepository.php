<?php

namespace App\Repository;

use App\Entity\Activity;
use App\Entity\Campus;
use App\Model\ActivityFilter;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Parameter;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Activity>
 */
class ActivityRepository extends ServiceEntityRepository
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager, ManagerRegistry $registry)
    {
        parent::__construct($registry, Activity::class);
        $this->entityManager = $entityManager;
    }

    /**
     * Création d'une sortie
     *
     * @param $activity
     * @return void
     */
    public function createActivity($activity): void
    {

        $this->getEntityManager()->persist($activity);
        $this->getEntityManager()->flush();
    }

    public function findAll(): array
    {
        $entityManager = $this->getEntityManager();

        $query = $entityManager->createQuery(
            'SELECT a, l, c
            FROM App\Entity\Activity a
            INNER JOIN a.lieu l
            INNER JOIN a.campus c
            ORDER BY a.dateDebut ASC'
        );

        return $query->getResult();
    }

    public function findAllActive(): array
    {
        $entityManager = $this->getEntityManager();

        $query = $entityManager->createQuery(
            'SELECT a
            FROM App\Entity\Activity a
            WHERE a.state >= 1 
            ORDER BY a.dateDebut ASC'
        );
        return $query->getResult();
    }

    public function update($activity)
    {
        $this->getEntityManager()->persist($activity);
        $this->getEntityManager()->flush();
    }

    public function findByCreator($idUser){
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select(array('activity'))
            ->from('App\Entity\Activity', 'activity')
            ->where('activity.organisateur = :idUser')
            ->setParameter('idUser', $idUser);

        return $qb->getQuery()->getResult();
    }
    
    public function filter(
        int      $idUser,
        ActivityFilter $filter
    )
    {

        $qb = $this->entityManager->createQueryBuilder();
        $param = new ArrayCollection();
        $qb->select(array('activity'))
            ->from('App\Entity\Activity', 'activity');
        
        if ($filter->getName() != null) {
            $qb->andWhere('activity.name LIKE :name');
            $param->add(new Parameter('name', '%' . $filter->getName() . '%'));
        }

        if ($filter->getCampus() != null) {
            $qb->andWhere('activity.campus = :campus');
            $param->add(new Parameter('campus', $filter->getCampus()));
        }

        if ($filter->getDateMin() != null) {
            $qb->andWhere('activity.dateDebut >= :dateMin');
            $param->add(new Parameter('dateMin', $filter->getDateMin()));
        }

        if ($filter->getDateMax() != null) {
            $qb->andWhere('activity.dateDebut <= :dateMax');
            $param->add(new Parameter('dateMax', $filter->getDateMax()));
        }

        if ($filter->getOrganisateur() != null) {
            $qb->andWhere('activity.organisateur = :organisateur');
            $param->add(new Parameter('organisateur', $filter->getOrganisateur()));
        }

        if ($filter->getInscrit()) {
            $qb->innerJoin('activity.inscrits', 'p', 'WITH', 'p.id = :idUser');
            $param->add(new Parameter('idUser', $idUser));
        }

        if ($filter->getFinis() != null) {
            $qb->andWhere('activity.dateDebut < :now');
            $param->add(new Parameter('now', new DateTime()));
        }

        $qb->setParameters($param);
        return $qb->getQuery()->getResult();
    }
}
