<?php

declare(strict_types=1);

namespace Forumify\Milhq;

use Forumify\Plugin\AbstractForumifyPlugin;
use Forumify\Plugin\PluginMetadata;
use Forumify\Milhq\DependencyInjection\CalendarPluginCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/** @codeCoverageIgnore */
class ForumifyMilhqPlugin extends AbstractForumifyPlugin
{
    public function getPluginMetadata(): PluginMetadata
    {
        return new PluginMetadata(
            'forumify MILHQ',
            'forumify',
            'forumify MILHQ - Personnel & Unit Management.',
            'https://forumify.net',
            'milhq_admin_configuration',
        );
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new CalendarPluginCompilerPass());
    }

    public function getPermissions(): array
    {
        return [
            'admin' => [
                'view',
                'run_sync',
                'configuration' => [
                    'manage',
                ],
                'soldiers' => [
                    'view',
                    'create',
                    'manage',
                    'delete',
                    'discharge',
                ],
                'submissions' => [
                    'view',
                    'assign_statuses',
                    'delete',
                ],
                'organization' => [
                    'view',
                    'awards' => [
                        'view',
                        'manage',
                        'delete',
                        'create',
                    ],
                    'documents' => [
                        'view',
                        'manage',
                        'delete',
                        'create',
                    ],
                    'forms' => [
                        'view',
                        'manage',
                        'delete',
                        'create',
                    ],
                    'positions' => [
                        'view',
                        'manage',
                        'delete',
                        'create',
                    ],
                    'qualifications' => [
                        'view',
                        'manage',
                        'delete',
                        'create',
                    ],
                    'ranks' => [
                        'view',
                        'manage',
                        'delete',
                        'create',
                    ],
                    'rosters' => [
                        'view',
                        'manage',
                        'delete',
                        'create',
                    ],
                    'specialties' => [
                        'view',
                        'manage',
                        'delete',
                        'create',
                    ],
                    'statuses' => [
                        'view',
                        'manage',
                        'delete',
                        'create',
                    ],
                    'units' => [
                        'view',
                        'manage',
                        'delete',
                        'create',
                    ],
                ],
                'records' => [
                    'view',
                    'assignment_records' => [
                        'view',
                        'create',
                        'delete',
                    ],
                    'award_records' => [
                        'view',
                        'create',
                        'delete',
                    ],
                    'combat_records' => [
                        'view',
                        'create',
                        'delete',
                    ],
                    'qualification_records' => [
                        'view',
                        'create',
                        'delete',
                    ],
                    'rank_records' => [
                        'view',
                        'create',
                        'delete',
                    ],
                    'service_records' => [
                        'view',
                        'create',
                        'delete',
                    ],
                ],
                'operations' => [
                    'view',
                    'manage',
                    'delete',
                ],
                'courses' => [
                    'view',
                    'manage',
                    'delete',
                ],
            ],
            'frontend' => [
                'attendance_sheet' => [
                    'view',
                ],
            ],
        ];
    }
}
