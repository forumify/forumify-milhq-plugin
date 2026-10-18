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
use Forumify\Milhq\Entity\Soldier;
use Forumify\Milhq\Entity\Rank;
use Forumify\Milhq\Api\Processor\RecordProcessor;
use Forumify\Milhq\Repository\RankRecordRepository;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RankRecordRepository::class)]
#[ORM\Table('milhq_record_rank')]
#[ApiResource(
    routePrefix: '/milhq',
    normalizationContext: ['groups' => ['Record', 'Record::read', 'MilhqSummary']],
    denormalizationContext: ['groups' => ['Record', 'Record::write']],
    order: ['createdAt' => 'DESC'],
    operations: [
        new Get(),
        new GetCollection(),
        new Post(
            security: 'is_granted("milhq.admin.records.rank_records.create")',
            processor: RecordProcessor::class,
        ),
        new Delete(security: 'is_granted("milhq.admin.records.rank_records.delete")'),
        new GetCollection(
            uriTemplate: '/soldiers/{soldierId}/rank-records',
            uriVariables: [
                'soldierId' => new Link(fromClass: Soldier::class, toProperty: 'soldier'),
            ],
        ),
    ],
)]
class RankRecord implements RecordInterface, AuditableEntityInterface
{
    use RecordFields;

    #[ORM\ManyToOne(targetEntity: Soldier::class, inversedBy: 'rankRecords')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    #[Groups('Record')]
    private Soldier $soldier;

    #[ORM\ManyToOne(targetEntity: Rank::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    #[Groups('RankRecord')]
    private Rank $rank;

    #[ORM\Column(length: 16)]
    #[Assert\Choice(['promotion', 'demotion'])]
    #[Groups('RankRecord')]
    private string $type = 'promotion';

    public function getRank(): Rank
    {
        return $this->rank;
    }

    public function setRank(Rank $rank): void
    {
        $this->rank = $rank;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }
}
