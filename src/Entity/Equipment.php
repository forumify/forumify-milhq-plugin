<?php

declare(strict_types=1);

namespace Forumify\Milhq\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\AuditableEntityInterface;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use Forumify\Milhq\Entity\Enum\EquipmentType;
use Forumify\Milhq\Repository\EquipmentRepository;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EquipmentRepository::class)]
#[ORM\Table('milhq_equipment')]
#[ApiResource(
    routePrefix: '/milhq',
    operations: [
        new Get(),
        new GetCollection(),
        new Post(security: 'is_granted("milhq.admin.organization.equipment.create")'),
        new Patch(security: 'is_granted("milhq.admin.organization.equipment.manage")'),
        new Delete(security: 'is_granted("milhq.admin.organization.equipment.delete")'),
    ],
)]
class Equipment implements AuditableEntityInterface
{
    use IdentifiableEntityTrait;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(allowNull: false)]
    #[Groups(['Equipment', 'MilhqSummary'])]
    private string $name;

    #[ORM\Column(enumType: EquipmentType::class)]
    #[Assert\NotBlank(allowNull: false)]
    #[Groups(['Equipment', 'MilhqSummary'])]
    private EquipmentType $type;



    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getType(): EquipmentType
    {
        return $this->type;
    }

    public function setType(EquipmentType $type): void
    {
        $this->type = $type;
    }

    public function getIdentifierForAudit(): string
    {
        return (string)$this->getId();
    }

    public function getNameForAudit(): string
    {
        return $this->getName();
    }
}
