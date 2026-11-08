<?php

declare(strict_types=1);

namespace PluginTests\Tests\Unit\Service;

use DateTime;
use Forumify\Core\Repository\SettingRepository;
use Forumify\Milhq\Entity\Soldier;
use Forumify\Milhq\Entity\Status;
use Forumify\Milhq\Repository\AssignmentRecordRepository;
use Forumify\Milhq\Repository\RankRecordRepository;
use Forumify\Milhq\Repository\ReportInRepository;
use Forumify\Milhq\Repository\SoldierRepository;
use Forumify\Milhq\Repository\StatusRepository;
use Forumify\Milhq\Service\SoldierService;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class SoldierServiceTest extends TestCase
{
    public function testSkipsStatusHistoryWhenNoStatusFreezes(): void
    {
        $statusRepository = $this->createMock(StatusRepository::class);
        $statusRepository->expects(self::once())->method('findBy')->with(['freezeTimeInService' => true])->willReturn([]);

        $assignmentRepository = $this->createMock(AssignmentRecordRepository::class);
        $assignmentRepository->expects(self::never())->method('findStatusHistory');

        $service = $this->createService($statusRepository, $assignmentRepository);

        self::assertSame(20, $service->getTimeInService($this->createSoldier())->days);
        self::assertSame(20, $service->getTimeInService($this->createSoldier())->days);
    }

    public function testLoadsFreezingStatusesOnceAcrossSoldiers(): void
    {
        $status = $this->createMock(Status::class);
        $status->method('getId')->willReturn(1);

        $statusRepository = $this->createMock(StatusRepository::class);
        $statusRepository->expects(self::once())->method('findBy')->willReturn([$status]);

        $assignmentRepository = $this->createMock(AssignmentRecordRepository::class);
        $assignmentRepository->expects(self::exactly(2))->method('findStatusHistory')->willReturn([]);

        $service = $this->createService($statusRepository, $assignmentRepository);

        $service->getTimeInService($this->createSoldier());
        $service->getTimeInService($this->createSoldier());
    }

    private function createService(
        StatusRepository $statusRepository,
        AssignmentRecordRepository $assignmentRepository,
    ): SoldierService {
        return new SoldierService(
            $this->createStub(SoldierRepository::class),
            $this->createStub(SettingRepository::class),
            $this->createStub(Security::class),
            $this->createStub(RankRecordRepository::class),
            $assignmentRepository,
            $this->createStub(ReportInRepository::class),
            $statusRepository,
            $this->createStub(TranslatorInterface::class),
        );
    }

    private function createSoldier(): Soldier
    {
        $soldier = $this->createStub(Soldier::class);
        $soldier->method('getCreatedAt')->willReturn(new DateTime('-20 days'));

        return $soldier;
    }
}
