<?php

declare(strict_types=1);

namespace Forumify\Milhq\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class CourseExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('milhq_course_prerequisites', [CourseExtensionRuntime::class, 'getPrerequisites']),
            new TwigFilter('milhq_course_qualifications', [CourseExtensionRuntime::class, 'getQualifications']),
            new TwigFilter('milhq_course_users', [CourseExtensionRuntime::class, 'getUsers']),
        ];
    }
}
