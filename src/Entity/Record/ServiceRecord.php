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
use Forumify\Milhq\Api\Processor\RecordProcessor;
use Forumify\Milhq\Repository\ServiceRecordRepository;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ServiceRecordRepository::class)]
#[ORM\Table('milhq_record_service')]
#[ApiResource(
    routePrefix: '/milhq',
    normalizationContext: ['groups' => ['Record', 'Record::read', 'MilhqSummary']],
    denormalizationContext: ['groups' => ['Record', 'Record::write']],
    order: ['createdAt' => 'DESC'],
    operations: [
        new Get(),
        new GetCollection(),
        new Post(
            security: 'is_granted("milhq.admin.records.service_records.create")',
            processor: RecordProcessor::class,
        ),
        new Delete(security: 'is_granted("milhq.admin.records.service_records.delete")'),
        new GetCollection(
            uriTemplate: '/soldiers/{soldierId}/service-records',
            uriVariables: [
                'soldierId' => new Link(fromClass: Soldier::class, toProperty: 'soldier'),
            ],
        ),
    ],
)]
class ServiceRecord implements RecordInterface, AuditableEntityInterface
{
    use RecordFields;

    #[ORM\ManyToOne(targetEntity: Soldier::class, inversedBy: 'serviceRecords')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    #[Groups('Record')]
    private Soldier $soldier;
}
