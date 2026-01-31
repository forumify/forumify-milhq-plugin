<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Forumify\Core\Repository\SettingRepository;
use Forumify\Plugin\Service\PluginVersionChecker;
use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

/**
 * @phpstan-type Result array{count: int, messages: array<string>}
 */
#[IsGranted('milhq.admin.configuration.manage')]
class MigratePerscomController extends AbstractController
{
    public function __construct(
        private readonly PluginVersionChecker $pluginVersionChecker,
        private readonly SettingRepository $settingRepository,
        private readonly EntityManagerInterface $em,
        private readonly FilesystemOperator $milhqAssetStorage,
        private readonly FilesystemOperator $assetStorage,
        private readonly ?FilesystemOperator $perscomAssetStorage = null,
    ) {
    }

    #[Route('/migrate-perscom', 'migrate_perscom')]
    public function __invoke(): Response
    {
        if (!$this->pluginVersionChecker->isVersionInstalled('forumify/forumify-perscom-plugin')) {
            $this->addFlash('error', 'The PERSCOM.io integration plugin is not installed.');
            return $this->redirectToRoute('milhq_admin_configuration');
        }

        return $this->render('@ForumifyMilhqPlugin/admin/migrate/migrate.html.twig');
    }

    #[Route('/migrate-perscom/migrate', 'migrate_perscom_migrate')]
    public function migrate(): Response
    {
        $results = [];

        $results['settings'] = $this->migrateSettings();

        // Organization
        $results['awards'] = $this->migrateTable('perscom_award', 'milhq_award', ['id', 'name', 'description', 'image', 'position', 'created_at', 'updated_at'], ['image']);
        $results['qualifications'] = $this->migrateTable('perscom_qualification', 'milhq_qualification', ['id', 'name', 'description', 'image', 'position', 'created_at', 'updated_at'], ['image']);
        $results['ranks'] = $this->migrateTable('perscom_rank', 'milhq_rank', ['id', 'name', 'description', 'abbreviation', 'paygrade', 'image', 'position', 'created_at', 'updated_at'], ['image']);
        $results['statuses'] = $this->migrateTable('perscom_status', 'milhq_status', ['id', 'name', 'color', 'position', 'created_at', 'updated_at']);
        $results['positions'] = $this->migrateTable('perscom_position', 'milhq_position', ['id', 'name', 'description', 'position', 'created_at', 'updated_at']);
        $results['specialties'] = $this->migrateTable('perscom_specialty', 'milhq_specialty', ['id', 'name', 'description', 'abbreviation', 'position', 'created_at', 'updated_at']);
        $results['rosters'] = $this->migrateTable('perscom_roster', 'milhq_roster', ['id', 'name', 'description', 'position', 'created_at', 'updated_at']);
        $results['units'] = $this->migrateTable('perscom_unit', 'milhq_unit', ['id', 'name', 'description', 'position', 'created_at', 'updated_at']);
        $results['rosterUnits'] = $this->migrateTable('perscom_roster_units', 'milhq_roster_units', ['roster_id', 'unit_id']);
        $results['documents'] = $this->migrateTable('perscom_document', 'milhq_document', ['id', 'name', 'description', 'content', 'created_at', 'updated_at', 'created_by']);

        // Soldiers
        $results['soldiers'] = $this->migrateTable('perscom_user', 'milhq_soldier', ['id', 'user_id', 'rank_id', 'unit_id', 'position_id', 'status_id', 'specialty_id', 'name', 'created_at', 'updated_at', 'signature', 'uniform', 'enlistment_topic_id', 'steam_id'], ['uniform', 'signature']);
        $results['assignmentRecords'] = $this->migrateTable('perscom_record_assignment', 'milhq_record_assignment', ['id', 'status_id', 'unit_id', 'position_id', 'specialty_id', 'author_id', 'user_id' => 'soldier_id', 'document_id', 'type', 'text', 'created_at', 'updated_at']);
        $results['awardRecords'] = $this->migrateTable('perscom_record_award', 'milhq_record_award', ['id', 'award_id', 'author_id', 'user_id' => 'soldier_id', 'document_id', 'text', 'created_at', 'updated_at']);
        $results['combatRecords'] = $this->migrateTable('perscom_record_combat', 'milhq_record_combat', ['id', 'author_id', 'user_id' => 'soldier_id', 'document_id', 'text', 'created_at', 'updated_at']);
        $results['qualificationRecords'] = $this->migrateTable('perscom_record_qualification', 'milhq_record_qualification', ['id', 'qualification_id', 'author_id', 'user_id' => 'soldier_id', 'document_id', 'text', 'created_at', 'updated_at']);
        $results['rankRecords'] = $this->migrateTable('perscom_record_rank', 'milhq_record_rank', ['id', 'rank_id', 'author_id', 'user_id' => 'soldier_id', 'document_id', 'type', 'text', 'created_at', 'updated_at']);
        $results['serviceRecords'] = $this->migrateTable('perscom_record_service', 'milhq_record_service', ['id', 'author_id', 'user_id' => 'soldier_id', 'document_id', 'text', 'created_at', 'updated_at']);
        $results['reportIns'] = $this->migrateTable('perscom_report_in', 'milhq_report_in', ['id', 'user_id' => 'soldier_id', 'last_report_in_date', 'return_status_id']);

        // Forms
        $results['forms'] = $this->migrateTable('perscom_form', 'milhq_form', ['id', 'default_status_id', 'name', 'success_message', 'description', 'instructions', 'created_at', 'updated_at']);
        $results['formFields'] = $this->migrateTable('perscom_form_field', 'milhq_form_field', ['id', 'form_id', 'key', 'type', 'label', 'help', 'required', 'position', 'created_at', 'updated_at']);
        $this->fixFormFields();
        $results['formSubmissions'] = $this->migrateTable('perscom_form_submission', 'milhq_form_submission', ['id', 'form_id', 'user_id' => 'soldier_id', 'status_id', 'data', 'status_reason', 'created_at', 'updated_at']);

        // Courses
        $results['courses'] = $this->migrateTable('perscom_course', 'milhq_course', ['id', 'slug', 'title', 'description', 'image', 'minimum_rank_id', 'prerequisites', 'qualifications', 'position']);
        $this->fixCourseImages($results['courses']);
        $results['courseInstructors'] = $this->migrateTable('perscom_course_instructor', 'milhq_course_instructor', ['id', 'course_id', 'title', 'description', 'position']);
        $results['courseClasses'] = $this->migrateTable('perscom_course_class', 'milhq_course_class', ['id', 'title', 'description', 'signup_from', 'signup_until', 'start', 'end', 'student_slots', 'result', 'created_at', 'updated_at', 'course_id', 'created_by', 'updated_by']);
        $results['courseClassInstructors'] = $this->migrateTable('perscom_course_class_instructor', 'milhq_course_class_instructor', ['id', 'user_id' => 'soldier_id', 'class_id', 'instructor_id', 'present']);
        $results['courseClassStudents'] = $this->migrateTable('perscom_course_class_student', 'milhq_course_class_student', ['id', 'user_id' => 'soldier_id', 'class_id', 'result', 'qualifications', 'service_record_text_override']);

        // Missions
        $results['operations'] = $this->migrateTable('perscom_operation', 'milhq_operation', ['id', 'title', 'description', 'image', 'start', 'end', 'mission_briefing_template', 'after_action_report_template', 'slug', 'content', 'request_rsvp']);
        $this->fixOperationImages($results['operations']);
        $results['missions'] = $this->migrateTable('perscom_mission', 'milhq_mission', ['id', 'operation_id', 'title', 'slug', 'briefing', 'start', 'end', 'send_notification', 'create_combat_records', 'combat_record_text', 'created_at', 'updated_at', 'created_by', 'updated_by']);
        $results['missionRsvps'] = $this->migrateTable('perscom_mission_rsvp', 'milhq_mission_rsvp', ['id', 'user_id' => 'soldier_id', 'mission_id', 'going', 'created_at', 'updated_at']);
        $results['missionAfterActionReports'] = $this->migrateTable('perscom_after_action_report', 'milhq_after_action_report', ['id', 'unit_id', 'mission_id', 'report', 'attendance', 'created_by', 'updated_by', 'created_at', 'updated_at']);

        return $this->render('@ForumifyMilhqPlugin/admin/migrate/migrate.html.twig', [
            'results' => $results,
        ]);
    }

    /**
     * @return Result
     */
    private function migrateSettings(): array
    {
        $settings = $this->settingRepository->getAll();
        $settingBlacklist = [
            'perscom.api_key' => true,
            'perscom.endpoint' => true,
            'perscom.sync.enabled' => true,
            'perscom.sync.is_initial_completed' => true,
        ];

        $milhqSettings = [];
        foreach ($settings as $k => $v) {
            if (!str_starts_with($k, 'perscom.') || isset($settingBlacklist[$k])) {
                continue;
            }

            $k = str_replace('perscom', 'milhq', $k);
            $milhqSettings[$k] = $v;
        }

        if (!empty($milhqSettings['milhq.profile.display_name_format'])) {
            $milhqSettings['milhq.profile.display_name_format'] = str_replace(
                'user.',
                'soldier.',
                $milhqSettings['milhq.profile.display_name_format'],
            );
        }

        $this->settingRepository->setBulk($milhqSettings);
        return ['count' => count($milhqSettings), 'messages' => []];
    }

    /**
     * @return Result
     */
    private function migrateTable(string $from, string $to, array $columns, array $imageColumns = []): array
    {
        $conn = $this->em->getConnection();

        $fromColumns = [];
        $toColumns = [];
        foreach ($columns as $fromCol => $toCol) {
            $fromColumns[] = '`' . (is_int($fromCol) ? $toCol : $fromCol) . '`';
            $toColumns[] = "`$toCol`";
        }

        $fromColumns = implode(', ', $fromColumns);
        $toColumns = implode(', ', $toColumns);

        $result = ['count' => 0, 'messages' => []];
        try {
            $conn->executeStatement("DELETE FROM $to");
            $result['count'] = $conn->executeStatement("
                INSERT INTO $to ($toColumns)
                SELECT $fromColumns FROM $from
            ");
        } catch (Throwable $ex) {
            $result['messages'][] = $ex->getMessage();
            return $result;
        }

        foreach ($imageColumns as $column) {
            $images = $conn->executeQuery("SELECT $column FROM $to WHERE $column IS NOT NULL")->fetchFirstColumn();
            foreach ($images as $image) {
                try {
                    $resource = $this->perscomAssetStorage?->readStream($image);
                    $this->milhqAssetStorage->writeStream($image, $resource);
                } catch (Throwable $ex) {
                    $result['messages'][] = $ex->getMessage();
                }
            }
        }

        return $result;
    }

    private function fixFormFields(): void
    {
        $conn = $this->em->getConnection();
        $conn->executeStatement('UPDATE milhq_form_field SET `type` = ? WHERE `type` = ?', ['datetime', 'datetime-local']);
        $conn->executeStatement('UPDATE milhq_form_field SET `type` = ? WHERE `type` = ?', ['textarea', 'code']);
        $conn->executeStatement('UPDATE milhq_form_field SET `type` = ? WHERE `type` = ?', ['checkbox', 'boolean']);

        $fieldsWithOptions = $conn->executeQuery('SELECT id, options FROM perscom_form_field WHERE `type` = ?', ['select'])->fetchAllKeyValue();
        foreach ($fieldsWithOptions as $id => $options) {
            $newOptions = [];
            foreach ((json_decode($options, true) ?? []) as $label => $key) {
                $newOptions[] = ['key' => $key, 'label' => $label];
            }
            $fieldOptions = json_encode(['options' => $newOptions]);
            $conn->executeStatement('UPDATE milhq_form_field SET `field_options` = ? WHERE `id` = ?', [$fieldOptions, $id]);
        }
    }

    private function fixCourseImages(array &$result): void
    {
        $conn = $this->em->getConnection();
        $images = $conn->executeQuery("SELECT image FROM milhq_course WHERE image IS NOT NULL")->fetchFirstColumn();
        foreach ($images as $image) {
            try {
                $resource = $this->assetStorage->readStream($image);
                $this->milhqAssetStorage->writeStream($image, $resource);
            } catch (Throwable $ex) {
                $result['messages'][] = $ex->getMessage();
            }
        }
    }

    private function fixOperationImages(array &$result): void
    {
        $conn = $this->em->getConnection();
        $images = $conn->executeQuery("SELECT image FROM milhq_operation WHERE image IS NOT NULL")->fetchFirstColumn();
        foreach ($images as $image) {
            try {
                $resource = $this->assetStorage->readStream($image);
                $this->milhqAssetStorage->writeStream($image, $resource);
            } catch (Throwable $ex) {
                $result['messages'][] = $ex->getMessage();
            }
        }
    }
}
