<?php

declare(strict_types=1);

namespace Forumify\Milhq\Components;

use Forumify\Plugin\Attribute\PluginVersion;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[PluginVersion('forumify/forumify-milhq-plugin', 'premium')]
#[AsLiveComponent('Milhq\\CourseList\\Expanded', '@ForumifyMilhqPlugin/frontend/components/course_list_expanded.html.twig')]
class CourseListExpanded extends CourseList
{
}
