<?php

declare(strict_types=1);

namespace Forumify\Milhq\Entity\Enum;

enum CourseResult: string
{
    case Excused = 'excused';
    case Failed = 'failed';
    case NoShow = 'no-show';
    case Passed = 'passed';
}
