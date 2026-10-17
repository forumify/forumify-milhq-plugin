<?php

declare(strict_types=1);

namespace Forumify\Milhq\Entity\Record;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\AuditableEntityInterface;
use Forumify\Milhq\Entity\Award;
use Forumify\Milhq\Entity\AwardTier;
use Forumify\Milhq\Entity\Soldier;
use Forumify\Milhq\Api\Processor\RecordProcessor;
use Forumify\Milhq\Repository\AwardRecordRepository;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: AwardRecordRepository::class)]
#[ORM\Table('milhq_record_award')]
#[ApiResource(
    routePrefix: '/milhq',
    normalizationContext: ['groups' => ['Record', 'Record::read', 'MilhqSummary']],
    denormalizationContext: ['groups' => ['Record', 'Record::write']],
    order: ['createdAt' => 'DESC'],
    operations: [
        new Get(),
        new GetCollection(),
        new Post(
            security: 'is_granted("milhq.admin.records.award_records.create")',
            processor: RecordProcessor::class,
        ),
        new Delete(security: 'is_granted("milhq.admin.records.award_records.delete")'),
        new GetCollection(
            uriTemplate: '/soldiers/{soldierId}/award-records',
            uriVariables: [
                'soldierId' => new Link(fromClass: Soldier::class, toProperty: 'soldier'),
            ],
        ),
    ],
)]
class AwardRecord implements RecordInterface, AuditableEntityInterface
{
    use RecordFields;

    #[ORM\ManyToOne(targetEntity: Soldier::class, inversedBy: 'awardRecords')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    #[Groups('Record')]
    private Soldier $soldier;

    #[ORM\ManyToOne(targetEntity: Award::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[Assert\NotNull]
    #[Groups('AwardRecord')]
    private Award $award;

    #[ORM\ManyToOne(targetEntity: AwardTier::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    #[Groups('AwardRecord')]
    private ?AwardTier $tier = null;

    public function getAward(): Award
    {
        return $this->award;
    }

    public function setAward(Award $award): void
    {
        $this->award = $award;
    }

    public function getTier(): ?AwardTier
    {
        return $this->tier;
    }

    public function setTier(?AwardTier $tier): void
    {
        $this->tier = $tier;
    }

    #[Assert\Callback]
    public function validateTier(ExecutionContextInterface $context): void
    {
        if (!isset($this->award) || $this->award->autoAdvanceTiers) {
            return;
        }

        if ($this->tier === null) {
            if (!$this->award->tiers->isEmpty()) {
                $context->buildViolation('This award requires a tier to be selected.')->atPath('tier')->addViolation();
            }
            return;
        }

        if ($this->tier->parent->getId() !== $this->award->getId()) {
            $context->buildViolation('This tier does not belong to the selected award.')->atPath('tier')->addViolation();
        }
    }
}
