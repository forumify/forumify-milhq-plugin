<?php

declare(strict_types=1);

namespace Forumify\Milhq\Entity;

use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use Forumify\Milhq\Entity\Enum\EquipmentType;
use Forumify\Milhq\Repository\EquipmentRepository;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EquipmentRepository::class)]
#[ORM\Table('milhq_equipment')]
class Equipment
{
    use IdentifiableEntityTrait;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(allowNull: false)]
    private string $name;

    #[ORM\Column(enumType: EquipmentType::class)]
    #[Assert\NotBlank(allowNull: false)]
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
}
