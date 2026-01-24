<?php

declare(strict_types=1);

namespace Forumify\Milhq\Twig;

use DateTime;
use Forumify\Milhq\Form\SubmissionFormType;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class DateExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('milhq_date', $this->date(...)),
        ];
    }

    private function date(string $value): ?DateTime
    {
        return DateTime::createFromFormat(SubmissionFormType::DATE_FORMAT, $value) ?: null;
    }
}
