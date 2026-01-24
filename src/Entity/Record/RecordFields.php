<?php

declare(strict_types=1);

namespace Forumify\Milhq\Entity\Record;

use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\IdentifiableEntityTrait;
use Forumify\Core\Entity\TimestampableEntityTrait;
use Forumify\Milhq\Entity\Document;
use Forumify\Milhq\Entity\Soldier;

trait RecordFields
{
    use IdentifiableEntityTrait;
    use TimestampableEntityTrait;

    #[ORM\ManyToOne(targetEntity: Soldier::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Soldier $author = null;

    #[ORM\Column(type: 'text')]
    private string $text = '';

    #[ORM\ManyToOne(targetEntity: Document::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Document $document = null;

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
}
