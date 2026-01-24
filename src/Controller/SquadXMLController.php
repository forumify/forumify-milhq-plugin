<?php

declare(strict_types=1);

namespace Forumify\Milhq\Controller;

use DateInterval;
use Forumify\Core\Repository\SettingRepository;
use Forumify\Milhq\Service\SquadXMLGenerator;
use Forumify\Plugin\Attribute\PluginVersion;
use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[PluginVersion('forumify/forumify-milhq-plugin', 'premium')]
class SquadXMLController extends AbstractController
{
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly SquadXMLGenerator $xmlGenerator,
        private readonly SettingRepository $settingRepository,
        private readonly FilesystemOperator $milhqAssetStorage,
    ) {
    }

    #[Route('/squad.xml', 'xml')]
    public function xml(): Response
    {
        $enabled = $this->settingRepository->get('milhq.squadxml.enabled');
        if (!$enabled) {
            throw $this->createNotFoundException();
        }

        $xml = $this->cache->get('milhq.squadxml', function (ItemInterface $item) {
            $item->expiresAfter(new DateInterval('PT15M'));
            return $this->xmlGenerator->generateXml();
        });

        return new Response($xml, 200, ['Content-Type' => 'text/xml']);
    }

    #[Route('/logo.paa', 'logo')]
    public function logo(): Response
    {
        $picture = $this->settingRepository->get('milhq.squadxml.picture');
        if ($picture === null) {
            throw $this->createNotFoundException();
        }

        try {
            $file = $this->milhqAssetStorage->read($picture);
            return new Response($file, 200, ['Content-Type' => 'application/octet-stream']);
        } catch (\Throwable $ex) {
            throw $this->createNotFoundException($ex->getMessage());
        }
    }

    #[Route('/squad.dtd', 'dtd')]
    public function dtd(): Response
    {
        return new Response(<<<DTDSTR
<!ELEMENT squad (name, email, web?, picture?, title?, member+)>
<!ATTLIST squad nick CDATA #REQUIRED>
<!ELEMENT member (name, email, icq?, remark?)>
<!ATTLIST member id CDATA #REQUIRED nick CDATA #REQUIRED>
<!ELEMENT name (#PCDATA)>
<!ELEMENT email (#PCDATA)>
<!ELEMENT icq (#PCDATA)>
<!ELEMENT web (#PCDATA)>
<!ELEMENT picture (#PCDATA)>
<!ELEMENT title (#PCDATA)>
<!ELEMENT remark (#PCDATA)>
DTDSTR, 200, ['Content-Type' => 'text/plain']);
    }
}
