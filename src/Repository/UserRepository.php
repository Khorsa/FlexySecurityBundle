<?php

namespace flexycms\FlexySecurityBundle\Repository;

use flexycms\FlexySecurityBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
	
    private $dataManager;


    public function __construct(ManagerRegistry $registry, EntityManagerInterface $dataManager)
    {
        $this->dataManager = $dataManager;
        parent::__construct($registry, User::class);
    }

    public function delete(User $user): void
    {
        $this->dataManager->remove($user);
        $this->dataManager->flush();
    }

    public function create(User $item): void
    {
        $this->dataManager->persist($item);
        $this->dataManager->flush();
    }

    public function update(User $item): void
    {
        $this->dataManager->flush();
    }

    public function delete(User $item): void
    {
        $this->dataManager->remove($item);
        $this->dataManager->flush();
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(UserInterface $user, string $newEncodedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newEncodedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }


    /**
     * @param string $searchString
     * @param array|null $order
     * @param array|null $limit
     * @return array
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function getBySearch(string $searchString, ?array $order, ?array  $limit): array
    {
        $qb = $this->createQueryBuilder('t');

        if (trim($searchString)) {
            $qb->addCriteria(
                Criteria::create()
                    ->where(Criteria::expr()->contains("t.email", $searchString))
            );
        }
        if (is_array($order)) $qb->orderBy('t.'.$order[0], $order[1]);


        if (is_array($limit)) {
            if (is_numeric($limit[0])) $qb->setFirstResult((int)($limit[0]));
            if (is_numeric($limit[1])) $qb->setMaxResults((int)($limit[1]));
        }

        return $qb->getQuery()->execute();
    }


    /**
     * @return int
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function countAll(): int
    {
        $qb = $this->createQueryBuilder('c');
        $qb->select('count(c.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }


    /**
     * @param string $searchString
     * @return int
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function countBySearch(string $searchString): int
    {
        $qb = $this->createQueryBuilder('t');
        $qb->select('count(t.id)');

        if (trim($searchString)) {
            $qb->addCriteria(
                Criteria::create()
                    ->where(Criteria::expr()->contains("t.email", $searchString))
            );        }
        return $qb->getQuery()->getSingleScalarResult();
    }
}
