<?php

declare(strict_types=1);

namespace Forumify\Milhq\Repository;

use Forumify\Core\Repository\AbstractRepository;
use Doctrine\Persistence\ManagerRegistry;
use Forumify\Core\Repository\SettingRepository;
use Forumify\Milhq\Entity\Form;

/**
 * @extends AbstractRepository<Form>
 */
class FormRepository extends AbstractRepository
{
    public function __construct(
        ManagerRegistry $managerRegistry,
        private readonly SettingRepository $settingRepository,
    ) {
        parent::__construct($managerRegistry);
    }

    public static function getEntityClass(): string
    {
        return Form::class;
    }

    /**
     * @return array<Form>
     */
    public function findAllSubmissionsAllowed(): array
    {
        $qb = $this->createQueryBuilder('e');
        $this->addACLToQuery($qb, 'create_submissions');

        $enlistmentFormId = $this->settingRepository->get('milhq.enlistment.form');
        if ($enlistmentFormId !== null) {
            $qb->andWhere('e.id != :enlistmentForm')->setParameter('enlistmentForm', $enlistmentFormId);
        }

        return $qb->getQuery()->getResult();
    }
}
