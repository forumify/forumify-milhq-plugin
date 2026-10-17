<?php

declare(strict_types=1);

namespace PluginTests\Tests\Application\Api;

use Doctrine\Common\Collections\ArrayCollection;
use Forumify\Core\Service\TokenService;
use Forumify\Testing\Api\ApiTestCase;
use Forumify\Testing\Api\Crud\DeleteTestTrait;
use Forumify\Testing\Api\Crud\GetCollectionTestTrait;
use Forumify\Testing\Api\Crud\GetTestTrait;
use Forumify\Testing\Api\Crud\PatchTestTrait;
use Forumify\Testing\Api\Crud\PostTestTrait;
use Forumify\Testing\Factories\Core\RoleFactory;
use Forumify\Testing\Factories\Core\UserFactory;
use PluginTests\Tests\Factories\Milhq\FormFactory;
use PluginTests\Tests\Factories\Milhq\FormFieldFactory;
use PluginTests\Tests\Factories\Milhq\FormStatusFactory;
use PluginTests\Tests\Factories\Milhq\FormSubmissionFactory;
use PluginTests\Tests\Factories\Milhq\SoldierFactory;
use PluginTests\Tests\Factories\Stories\MilsimStory;

class FormApiTest extends ApiTestCase
{
    use GetCollectionTestTrait;
    use GetTestTrait;
    use PostTestTrait;
    use PatchTestTrait;
    use DeleteTestTrait;

    protected static function factory(): string
    {
        return FormFactory::class;
    }

    protected static function endpoint(): string
    {
        return '/api/milhq/forms';
    }

    protected function getPostBody(): array
    {
        return ['name' => 'Leave of Absence'];
    }

    protected function getPatchBody(): array
    {
        return ['name' => 'Extended Leave of Absence'];
    }

    public function testGetEmbedsFieldsAndStatuses(): void
    {
        MilsimStory::load();

        $form = $this->get(self::endpoint() . '/' . MilsimStory::formEnlistment()->getId());

        self::assertSame(['reason'], array_column($form['fields'], 'key'));
        self::assertTrue($form['fields'][0]['required']);
        self::assertSame(['Pending', 'Approved', 'Denied'], array_column($form['statuses'], 'name'));
        self::assertSame($form['statuses'][0]['@id'], $form['defaultStatus']['@id']);
    }

    public function testPostFieldGeneratesKey(): void
    {
        $form = FormFactory::createOne();

        $field = $this->post("/api/milhq/forms/{$form->getId()}/fields", [
            'json' => ['label' => 'Preferred Callsign', 'type' => 'text', 'required' => true],
        ]);

        self::assertSame('preferred-callsign', $field['key']);
    }

    public function testPostFieldWithDuplicateLabelIsRejected(): void
    {
        $form = FormFactory::createOne();
        $this->post("/api/milhq/forms/{$form->getId()}/fields", [
            'json' => ['label' => 'Preferred Callsign', 'type' => 'text'],
        ]);

        $response = $this->request('POST', "/api/milhq/forms/{$form->getId()}/fields", [
            'json' => ['label' => 'Preferred Callsign', 'type' => 'text'],
        ]);

        self::assertSame(422, $response->getStatusCode());
    }

    public function testPostFieldRequiresType(): void
    {
        $form = FormFactory::createOne();

        $response = $this->request('POST', "/api/milhq/forms/{$form->getId()}/fields", [
            'json' => ['label' => 'Favourite Colour'],
        ]);

        self::assertSame(422, $response->getStatusCode());
    }

    public function testPostFieldRejectsUnknownType(): void
    {
        $form = FormFactory::createOne();

        $response = $this->request('POST', "/api/milhq/forms/{$form->getId()}/fields", [
            'json' => ['label' => 'Favourite Colour', 'type' => 'rainbow'],
        ]);

        self::assertSame(422, $response->getStatusCode());
    }

    public function testSoldierCreatesSubmission(): void
    {
        MilsimStory::load();
        $form = MilsimStory::formEnlistment();
        FormFieldFactory::createOne([
            'form' => $form,
            'key' => 'role',
            'label' => 'Preferred role',
            'type' => 'select',
            'fieldOptions' => ['options' => [['key' => 'rifleman', 'label' => 'Rifleman'], ['key' => 'medic', 'label' => 'Medic']]],
        ]);
        $user = UserFactory::createOne(['roleEntities' => new ArrayCollection([RoleFactory::findOrCreate(['slug' => 'super-admin'])])]);
        SoldierFactory::createOne(['user' => $user, 'name' => 'John Doe']);
        $options = ['headers' => ['Authorization' => 'Bearer ' . self::getContainer()->get(TokenService::class)->createJwt($user)]];

        $invalid = $this->request('POST', "/api/milhq/forms/{$form->getId()}/submissions", $options + [
            'json' => ['data' => ['role' => 'pilot']],
        ]);
        self::assertSame(422, $invalid->getStatusCode());
        self::assertStringContainsString('"reason": This value should not be blank.', $invalid->toArray(false)['detail']);
        self::assertStringContainsString('"role": The value you selected is not a valid choice.', $invalid->toArray(false)['detail']);

        $submission = $this->post("/api/milhq/forms/{$form->getId()}/submissions", $options + [
            'json' => ['data' => ['reason' => 'For glory', 'role' => 'medic', 'unknown' => 'dropped']],
        ]);

        self::assertSame('John Doe', $submission['soldier']['name']);
        self::assertSame('Pending', $submission['status']['name']);
        self::assertSame(['reason' => 'For glory', 'role' => 'medic'], $submission['data']);
    }

    public function testSubmissionValuesAreValidatedAgainstFieldTypes(): void
    {
        $form = FormFactory::createOne();
        FormFieldFactory::createOne(['form' => $form, 'key' => 'birthday', 'label' => 'Birthday', 'type' => 'date']);
        FormFieldFactory::createOne(['form' => $form, 'key' => 'age', 'label' => 'Age', 'type' => 'number']);
        FormFieldFactory::createOne(['form' => $form, 'key' => 'email', 'label' => 'Email', 'type' => 'email']);
        $user = UserFactory::createOne(['roleEntities' => new ArrayCollection([RoleFactory::findOrCreate(['slug' => 'super-admin'])])]);
        SoldierFactory::createOne(['user' => $user]);
        $options = ['headers' => ['Authorization' => 'Bearer ' . self::getContainer()->get(TokenService::class)->createJwt($user)]];

        $invalid = $this->request('POST', "/api/milhq/forms/{$form->getId()}/submissions", $options + [
            'json' => ['data' => ['birthday' => ['2000-01-01'], 'age' => 'old', 'email' => 'not-an-email']],
        ]);
        self::assertSame(422, $invalid->getStatusCode());
        $detail = $invalid->toArray(false)['detail'];
        self::assertStringContainsString('"birthday":', $detail);
        self::assertStringContainsString('"age":', $detail);
        self::assertStringContainsString('"email":', $detail);

        $submission = $this->post("/api/milhq/forms/{$form->getId()}/submissions", $options + [
            'json' => ['data' => ['birthday' => '2000-01-01', 'age' => '26', 'email' => 'john@example.org']],
        ]);
        self::assertSame('2000-01-01T00:00:00.000000Z', $submission['data']['birthday']);
        self::assertEquals(26, $submission['data']['age']);
        self::assertSame('john@example.org', $submission['data']['email']);
    }

    public function testSoldierCanOnlyViewOwnSubmission(): void
    {
        $user = UserFactory::createOne();
        $own = FormSubmissionFactory::createOne(['soldier' => SoldierFactory::createOne(['user' => $user])]);
        $other = FormSubmissionFactory::createOne(['soldier' => SoldierFactory::createOne()]);
        $options = ['headers' => ['Authorization' => 'Bearer ' . self::getContainer()->get(TokenService::class)->createJwt($user)]];

        self::assertSame(200, $this->request('GET', "/api/milhq/form-submissions/{$own->getId()}", $options)->getStatusCode());
        self::assertSame(403, $this->request('GET', "/api/milhq/form-submissions/{$other->getId()}", $options)->getStatusCode());
    }

    public function testSubmittingRequiresCreateSubmissionsAccess(): void
    {
        $user = UserFactory::createOne();
        SoldierFactory::createOne(['user' => $user]);
        $form = FormFactory::createOne();

        $response = $this->request('POST', "/api/milhq/forms/{$form->getId()}/submissions", [
            'json' => ['data' => []],
            'headers' => ['Authorization' => 'Bearer ' . self::getContainer()->get(TokenService::class)->createJwt($user)],
        ]);

        self::assertSame(403, $response->getStatusCode());
    }

    public function testUpdateSubmissionStatus(): void
    {
        MilsimStory::load();
        $form = MilsimStory::formEnlistment();
        $submission = FormSubmissionFactory::createOne(['form' => $form, 'status' => MilsimStory::formStatusPending()]);

        $updated = $this->patch("/api/milhq/form-submissions/{$submission->getId()}", [
            'json' => [
                'status' => '/api/milhq/form-statuses/' . MilsimStory::formStatusApproved()->getId(),
                'statusReason' => 'Welcome aboard',
                'sendNotification' => true,
                'data' => ['reason' => 'tampered'],
            ],
        ]);

        self::assertSame('Approved', $updated['status']['name']);
        self::assertSame('Welcome aboard', $updated['statusReason']);
        self::assertSame([], $updated['data']);
    }

    public function testSubmissionStatusMustBelongToForm(): void
    {
        $submission = FormSubmissionFactory::createOne();
        $otherStatus = FormStatusFactory::createOne(['form' => FormFactory::createOne(), 'name' => 'Elsewhere']);

        $response = $this->request('PATCH', "/api/milhq/form-submissions/{$submission->getId()}", [
            'json' => ['status' => "/api/milhq/form-statuses/{$otherStatus->getId()}"],
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
        ]);

        self::assertSame(422, $response->getStatusCode());
    }

    public function testGetSubmissionsOfForm(): void
    {
        $submission = FormSubmissionFactory::createOne();
        FormSubmissionFactory::createOne();

        $submissions = $this->getCollection("/api/milhq/forms/{$submission->getForm()->getId()}/submissions");

        self::assertSame([$submission->getId()], array_column($submissions, 'id'));
    }
}
