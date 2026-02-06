<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Controller;

use Forumify\Admin\Crud\AbstractCrudController;
use Forumify\Milhq\Admin\Form\UserType;
use Forumify\Milhq\Entity\Soldier;
use Forumify\Milhq\Repository\AssignmentRecordRepository;
use Forumify\Milhq\Repository\SoldierRepository;
use Forumify\Plugin\Service\PluginVersionChecker;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @extends AbstractCrudController<Soldier>
 */
#[Route('/soldiers', 'soldier')]
class SoldierController extends AbstractCrudController
{
    protected string $listTemplate = '@ForumifyMilhqPlugin/admin/soldiers/list/list.html.twig';
    protected string $formTemplate = '@ForumifyMilhqPlugin/admin/soldiers/edit/form.html.twig';

    protected ?string $permissionView = 'milhq.admin.soldiers.view';
    protected ?string $permissionCreate = 'milhq.admin.soldiers.create';
    protected ?string $permissionEdit = 'milhq.admin.soldiers.manage';
    protected ?string $permissionDelete = 'milhq.admin.soldiers.delete';

    public function __construct(
        private readonly AssignmentRecordRepository $assignmentRecordRepository,
        private readonly PluginVersionChecker $pluginVersionChecker,
        private readonly SoldierRepository $soldierRepository,
    ) {
    }

    protected function getTranslationPrefix(): string
    {
        return 'milhq.' . parent::getTranslationPrefix();
    }

    protected function getEntityClass(): string
    {
        return Soldier::class;
    }

    protected function getTableName(): string
    {
        return 'Milhq\\UserTable';
    }

    #[Route('/create', '_create')]
    public function create(Request $request): Response
    {
        if (!$this->pluginVersionChecker->isVersionInstalled('forumify/forumify-milhq-plugin', ['basic', 'premium'])) {
            $soldierCount = $this->soldierRepository->count([]);
            if ($soldierCount >= 5) {
                $this->addFlash('error', 'You have exceeded the maximum allowed soldiers for the free version of MILHQ.');
                return $this->redirectToRoute($this->getRoute('list'));
            }
        }

        return parent::create($request);
    }

    protected function getForm(?object $data): FormInterface
    {
        return $this->createForm(UserType::class, $data);
    }

    protected function templateParams(array $params = []): array
    {
        $params = parent::templateParams($params);
        if (empty($params['data'])) {
            return $params;
        }

        /** @var Soldier $soldier */
        $soldier = $params['data'];
        $params['secondaryAssignmentRecords'] = $this->assignmentRecordRepository->findBy([
            'type' => 'secondary',
            'soldier' => $soldier,
        ]);

        return $params;
    }
}
