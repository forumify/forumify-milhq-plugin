<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\MenuBuilder;

use Forumify\Admin\MenuBuilder\AdminMenuBuilderInterface;
use Forumify\Core\MenuBuilder\Menu;
use Forumify\Core\MenuBuilder\MenuItem;
use Forumify\Milhq\Repository\FormRepository;
use Forumify\Plugin\Service\PluginVersionChecker;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AdminMenuBuilder implements AdminMenuBuilderInterface
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly PluginVersionChecker $pluginVersionChecker,
        private readonly FormRepository $formRepository,
    ) {
    }

    public function build(Menu $menu): void
    {
        $u = $this->urlGenerator->generate(...);

        $milhq = new Menu('MILHQ', ['icon' => 'ph ph-shield-chevron', 'permission' => 'milhq.admin.view'], [
            new MenuItem('Configuration', $u('milhq_admin_configuration'), ['icon' => 'ph ph-wrench', 'permission' => 'milhq.admin.configuration.manage']),
            new MenuItem('Soldiers', $u('milhq_admin_soldier_list'), ['icon' => 'ph ph-users', 'permission' => 'milhq.admin.soldiers.view']),
        ]);

        if ($this->pluginVersionChecker->isVersionInstalled('forumify/forumify-milhq-plugin', 'premium')) {
            $milhq
                ->addItem(new MenuItem('Operations', $u('milhq_admin_operations_list'), [
                    'icon' => 'ph ph-airplane-takeoff',
                    'permission' => 'milhq.admin.operations.view',
                ]))
                ->addItem(new MenuItem('Courses', $u('milhq_admin_courses_list'), [
                    'icon' => 'ph ph-graduation-cap',
                    'permission' => 'milhq.admin.courses.view',
                ]));
        }

        $submissionMenu = new Menu('Submissions', ['icon' => 'ph ph-table', 'permission' => 'milhq.admin.submissions.view']);
        $submissionMenu->addItem(new MenuItem('View All', $u('milhq_admin_submission_list')));
        foreach ($this->formRepository->findAll() as $form) {
            $submissionMenu->addItem(new MenuItem($form->getName(), $u('milhq_admin_submission_list', ['form' => $form->getId()])));
        }
        $milhq->addItem($submissionMenu);

        $milhq->addItem(new Menu('Records', ['icon' => 'ph ph-files', 'permission' => 'milhq.admin.records.view'], [
            new MenuItem('Service Records', $u('milhq_admin_service_records_list'), ['permission' => 'milhq.admin.records.service_records.view']),
            new MenuItem('Award Records', $u('milhq_admin_award_records_list'), ['permission' => 'milhq.admin.records.award_records.view']),
            new MenuItem('Combat Records', $u('milhq_admin_combat_records_list'), ['permission' => 'milhq.admin.records.combat_records.view']),
            new MenuItem('Rank Records', $u('milhq_admin_rank_records_list'), ['permission' => 'milhq.admin.records.rank_records.view']),
            new MenuItem('Assignment Records', $u('milhq_admin_assignment_records_list'), ['permission' => 'milhq.admin.records.assignment_records.view']),
            new MenuItem('Qualification Records', $u('milhq_admin_qualification_records_list'), ['permission' => 'milhq.admin.records.qualification_records.view']),
        ]));

        $milhq->addItem(new Menu('Organization', ['icon' => 'ph ph-buildings', 'permission' => 'milhq.admin.organization.view'], [
            new MenuItem('Awards', $u('milhq_admin_award_list'), ['permission' => 'milhq.admin.organization.awards.view']),
            new MenuItem('Documents', $u('milhq_admin_document_list'), ['permission' => 'milhq.admin.organization.documents.view']),
            new MenuItem('Forms', $u('milhq_admin_form_list'), ['permission' => 'milhq.admin.organization.forms.view']),
            new MenuItem('Positions', $u('milhq_admin_position_list'), ['permission' => 'milhq.admin.organization.positions.view']),
            new MenuItem('Qualifications', $u('milhq_admin_qualification_list'), ['permission' => 'milhq.admin.organization.qualifications.view']),
            new MenuItem('Ranks', $u('milhq_admin_rank_list'), ['permission' => 'milhq.admin.organization.ranks.view']),
            new MenuItem('Rosters', $u('milhq_admin_roster_list'), ['permission' => 'milhq.admin.organization.rosters.view']),
            new MenuItem('Specialties', $u('milhq_admin_specialty_list'), ['permission' => 'milhq.admin.organization.specialties.view']),
            new MenuItem('Statuses', $u('milhq_admin_status_list'), ['permission' => 'milhq.admin.organization.statuses.view']),
            new MenuItem('Units', $u('milhq_admin_unit_list'), ['permission' => 'milhq.admin.organization.units.view']),
        ]));

        $menu->addItem($milhq);
    }
}
