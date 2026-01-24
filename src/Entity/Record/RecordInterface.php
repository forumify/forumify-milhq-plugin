<?php

declare(strict_types=1);

namespace Forumify\Milhq\Entity\Record;

use DateTime;
use Forumify\Milhq\Entity\Document;
use Forumify\Milhq\Entity\Soldier;

interface RecordInterface
{
    public function getId(): int;

    public function getAuthor(): ?Soldier;

    public function setAuthor(?Soldier $author): void;

    public function getSoldier(): Soldier;

    public function setSoldier(Soldier $user): void;

    public function getText(): string;

    public function setText(string $text): void;

    public function getDocument(): ?Document;

    public function setDocument(?Document $document): void;

    public function getCreatedAt(): DateTime;

    public function setCreatedAt(DateTime $createdAt): void;

    public function getUpdatedAt(): ?DateTime;

    public function setUpdatedAt(?DateTime $updatedAt): void;
}
