<?php

declare(strict_types=1);

namespace PluginTests\Tests\Application;

use Forumify\Milhq\Repository\AwardRepository;
use League\Flysystem\FilesystemOperator;

class AwardTest extends MilhqWebTestCase
{
    public function testCreateAwardWithImage(): void
    {
        $this->client->request('GET', '/admin/milhq/awards/create');
        $this->client->submitForm('Save', [
            'award[name]' => 'Purple Heart',
            'award[description]' => 'For wounds received.',
            'award[image][file]' => TEST_DATA_DIR . '/sergeant.png',
        ]);

        self::assertResponseIsSuccessful();

        $award = self::getContainer()->get(AwardRepository::class)->findOneBy(['name' => 'Purple Heart']);
        self::assertNotNull($award);
        self::assertNotNull($award->image);

        /** @var FilesystemOperator $storage */
        $storage = self::getContainer()->get('milhq_asset.storage');
        self::assertTrue($storage->fileExists($award->image));

        $storage->delete($award->image);
    }
}
