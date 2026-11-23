<?php

declare(strict_types=1);

namespace PluginTests\Tests\Unit\Entity;

use Forumify\Milhq\Entity\Rank;
use Forumify\Milhq\Entity\RankGroup;
use PHPUnit\Framework\TestCase;

class RankTest extends TestCase
{
    public function testGroupPositionTakesPrecedenceOverRankPosition(): void
    {
        $nco = $this->createGroup(3);
        $enlisted = $this->createGroup(4);

        $corporal = $this->createRank(9, $nco);
        $private = $this->createRank(4, $enlisted);

        self::assertLessThan(0, $corporal->compare($private));
        self::assertGreaterThan(0, $private->compare($corporal));
    }

    public function testRankPositionDecidesWithinSameGroup(): void
    {
        $group = $this->createGroup(1);

        $sergeant = $this->createRank(8, $group);
        $corporal = $this->createRank(9, $group);

        self::assertLessThan(0, $sergeant->compare($corporal));
        self::assertSame(0, $corporal->compare($this->createRank(9, $group)));
    }

    public function testUngroupedRankIsJunior(): void
    {
        $grouped = $this->createRank(5, $this->createGroup(4));
        $ungrouped = $this->createRank(1, null);

        self::assertLessThan(0, $grouped->compare($ungrouped));
    }

    private function createGroup(int $position): RankGroup
    {
        $group = new RankGroup();
        $group->setPosition($position);

        return $group;
    }

    private function createRank(int $position, ?RankGroup $group): Rank
    {
        $rank = new Rank();
        $rank->setPosition($position);
        $rank->setGroup($group);

        return $rank;
    }
}
