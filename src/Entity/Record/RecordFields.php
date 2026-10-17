<?php

declare(strict_types=1);

namespace Forumify\Milhq\Entity\Record;

use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\AuditableEntityInterface;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use Forumify\Core\Entity\TimestampableEntityTrait;
use Forumify\Milhq\Entity\Document;
use Forumify\Milhq\Entity\Soldier;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * @phpstan-require-implements AuditableEntityInterface
 */
trait RecordFields
{
    use IdentifiableEntityTrait;
    use TimestampableEntityTrait;

    #[ORM\ManyToOne(targetEntity: Soldier::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    #[Groups('Record::read')]
    private ?Soldier $author = null;

    #[ORM\Column(type: 'text')]
    #[Groups('Record')]
    private string $text = '';

    #[ORM\ManyToOne(targetEntity: Document::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    #[Groups('Record')]
    private ?Document $document = null;

    #[Groups('Record::write')]
    public bool $sendNotification = false;

    public function getAuthor(): ?Soldier
    {
        return $this->author;
    }

    public function setAuthor(?Soldier $author): void
    {
        $this->author = $author;
    }

    public function getSoldier(): Soldier
    {
        return $this->soldier;
    }

    public function setSoldier(Soldier $soldier): void
    {
        $this->soldier = $soldier;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function setText(string $text): void
    {
        $this->text = $text;
    }

    public function getDocument(): ?Document
    {
        return $this->document;
    }

    public function setDocument(?Document $document): void
    {
        $this->document = $document;
    }

    public function shouldSendNotification(): bool
    {
        return $this->sendNotification;
    }

    public function getIdentifierForAudit(): string
    {
        return (string)$this->getId();
    }

    public function getNameForAudit(): string
    {
        return $this->getSoldier()->getName();
    }
}
