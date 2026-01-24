<?php

declare(strict_types=1);

namespace Forumify\Milhq\Twig;

use DateTimeInterface;
use Forumify\Milhq\Entity\Document;
use Forumify\Milhq\Entity\Soldier;
use Forumify\Milhq\Entity\Record\AssignmentRecord;
use Forumify\Milhq\Entity\Record\AwardRecord;
use Forumify\Milhq\Entity\Record\CombatRecord;
use Forumify\Milhq\Entity\Record\QualificationRecord;
use Forumify\Milhq\Entity\Record\RankRecord;
use Forumify\Milhq\Entity\Record\RecordInterface;
use Forumify\Milhq\Entity\Record\ServiceRecord;
use Twig\Extension\RuntimeExtensionInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Contracts\Translation\TranslatorInterface;

class DocumentExtensionRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private readonly TranslatorInterface $translatorInterface,
        private readonly Packages $packages,
    ) {
    }

    public function parseDocument(Document $document, RecordInterface $record): string
    {
        $replacers = [
            ...$this->userToFields($record->getSoldier()),
            ...$this->recordToFields($record),
        ];

        return $this->parseRecurse($document->getContent(), $replacers);
    }

    private function parseRecurse(string $content, array $replacers): string
    {
        $openPos = strpos($content, '{');
        if ($openPos === false) {
            return $content;
        }

        $closePos = strpos($content, '}', $openPos);
        if ($closePos === false) {
            return $content;
        }

        $replacer = substr($content, $openPos + 1, $closePos - $openPos - 1);
        $replacer = $replacers[$replacer] ?? null;
        $newContent = $replacer === null ? '' : $replacer();
        $newContent = substr($content, 0, $openPos) . $newContent . substr($content, $closePos + 1);

        return $this->parseRecurse($newContent, $replacers);
    }

    /**
     * @return array<string, callable(): string>
     */
    private function userToFields(Soldier $user): array
    {
        return [
            'user_assignment_position' => fn () => $user->getPosition()?->getName() ?? '',
            'user_assignment_specialty' => fn () => $user->getSpecialty()?->getName() ?? '',
            'user_assignment_unit' => fn () => $user->getUnit()?->getName() ?? '',
            'user_email' => fn () => $user->getUser()?->getEmail() ?? '',
            'user_name' => fn () => $user->getName(),
            'user_rank' => fn () => $user->getRank()?->getName() ?? '',
            'user_rank_abbreviation' => fn () => $user->getRank()?->getAbbreviation() ?? '',
            'user_status' => fn () => $user->getStatus()?->getName() ?? '',
        ];
    }

    /**
     * @return array<string, callable(): string>
     */
    private function recordToFields(RecordInterface $record): array
    {
        if ($record instanceof AssignmentRecord) {
            return [
                'assignment_record_date' => fn () => $this->date($record->getCreatedAt()),
                'assignment_record_position' => fn () => $record->getPosition()?->getName() ?? '',
                'assignment_record_specialty' => fn () => $record->getSpecialty()?->getName() ?? '',
                'assignment_record_status' => fn () => $record->getStatus()?->getName() ?? '',
                'assignment_record_text' => fn () => $record->getText(),
                'assignment_record_type' => fn () => $record->getType(),
                'assignment_record_unit' => fn () => $record->getUnit()?->getName() ?? '',
            ];
        }

        if ($record instanceof AwardRecord) {
            return [
                'award_record_award' => fn () => $record->getAward()->getName(),
                'award_record_award_image' => fn () => $this->img($record->getAward()->getImage()),
                'award_record_date' => fn () => $this->date($record->getCreatedAt()),
                'award_record_text' => fn () => $record->getText(),
            ];
        }

        if ($record instanceof CombatRecord) {
            return [
                'combat_record_date' => fn () => $this->date($record->getCreatedAt()),
                'combat_record_text' => fn () => $record->getText(),
            ];
        }

        if ($record instanceof QualificationRecord) {
            return [
                'qualification_record_date' => fn () => $this->date($record->getCreatedAt()),
                'qualification_record_qualification' => fn () => $record->getQualification()->getName(),
                'qualification_record_qualification_image' => fn () => $this->img($record->getQualification()->getImage()),
                'qualification_record_text' => fn () => $record->getText(),
            ];
        }

        if ($record instanceof RankRecord) {
            return [
                'rank_record_date' => fn () => $this->date($record->getCreatedAt()),
                'rank_record_rank' => fn () => $record->getRank()->getName(),
                'rank_record_rank_abbreviation' => fn () => $record->getRank()->getAbbreviation(),
                'rank_record_rank_image' => fn () => $this->img($record->getRank()->getImage()),
                'rank_record_text' => fn () => $record->getText(),
                'rank_record_type' => fn () => $record->getType(),
            ];
        }

        if ($record instanceof ServiceRecord) {
            return [
                'service_record_date' => fn () => $this->date($record->getCreatedAt()),
                'service_record_text' => fn () => $record->getText(),
            ];
        }

        return [];
    }

    private function date(?DateTimeInterface $date): string
    {
        if ($date === null) {
            return '';
        }

        return $this->translatorInterface->trans('date', ['date' => $date]);
    }

    private function img(?string $img): string
    {
        if ($img === null) {
            return '';
        }

        $imgUrl = $this->packages->getUrl($img, 'milhq.asset');
        return "<img src='$imgUrl'>";
    }
}
