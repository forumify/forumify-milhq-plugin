<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Controller;

use Forumify\Core\Repository\SettingRepository;
use Forumify\Core\Service\MediaService;
use Forumify\Core\Twig\Extension\MenuRuntime;
use Forumify\Milhq\Admin\Form\ConfigurationType;
use League\Flysystem\FilesystemOperator;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[IsGranted('milhq.admin.configuration.manage')]
class ConfigurationController extends AbstractController
{
    /**
     * @param TagAwareCacheInterface $cache
     */
    public function __construct(
        private readonly SettingRepository $settingRepository,
        private readonly CacheInterface $cache,
        private readonly MediaService $mediaService,
        private readonly FilesystemOperator $milhqAssetStorage,
    ) {
    }

    #[Route('/configuration', 'configuration')]
    public function __invoke(Request $request): Response
    {
        if ($request->query->get('ignore-perscom')) {
            $this->settingRepository->set('milhq.hide-perscom-migration-warning', true);
            return $this->redirectToRoute('milhq_admin_configuration');
        }

        $form = $this->createForm(ConfigurationType::class, $this->settingRepository->toFormData('milhq'));

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $this->handleSquadXmlPicture($data);

            $this->settingRepository->handleFormData($data);

            try {
                $this->cache->invalidateTags([MenuRuntime::MENU_CACHE_TAG]);
            } catch (InvalidArgumentException) {
            }

            return $this->redirectToRoute('milhq_admin_configuration');
        }

        return $this->render('@ForumifyMilhqPlugin/admin/configuration/configuration.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    private function handleSquadXmlPicture(array &$data): void
    {
        $picture = $data['milhq__squadxml__new_picture'] ?? null;
        unset($data['milhq__squadxml__new_picture']);
        if ($picture instanceof UploadedFile) {
            $data['milhq__squadxml__picture'] = $this->mediaService->saveToFilesystem(
                $this->milhqAssetStorage,
                $picture,
            );
        }

        $picturePreview = $data['milhq__squadxml__new_picture_preview'] ?? null;
        unset($data['milhq__squadxml__new_picture_preview']);
        if ($picturePreview instanceof UploadedFile) {
            $data['milhq__squadxml__picture_preview'] = $this->mediaService->saveToFilesystem(
                $this->milhqAssetStorage,
                $picturePreview,
            );
        }
    }
}
