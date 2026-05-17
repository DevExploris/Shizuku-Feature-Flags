<?php

namespace Devexploris\ShizukuFeatureFlags\Tests\Unit\Command;

use Devexploris\ShizukuFeatureFlags\Command\ListCommand;
use Devexploris\ShizukuFeatureFlags\Entity\FeatureFlag;
use Devexploris\ShizukuFeatureFlags\Repository\FeatureFlagRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class ListCommandTest extends TestCase
{
    private FeatureFlag $enabledFlag;
    private FeatureFlag $disabledFlag;
    private FeatureFlag $lockedFlag;

    protected function setUp(): void
    {
        $this->enabledFlag  = new FeatureFlag('flag_enabled', 'An enabled flag', true);
        $this->disabledFlag = new FeatureFlag('flag_disabled', 'A disabled flag', false);
        $this->lockedFlag   = new FeatureFlag('flag_locked', 'A locked flag')->setIsLocked(true);
    }

    private function makeCommand(FeatureFlagRepository $repository): CommandTester
    {
        return new CommandTester(new ListCommand($repository));
    }

    // --- Mode interactif ---

    public function testListAll(): void
    {
        $repository = $this->createMock(FeatureFlagRepository::class);
        $repository->expects($this->once())->method('findAll')->willReturn([$this->enabledFlag, $this->disabledFlag, $this->lockedFlag]);

        $tester = $this->makeCommand($repository);
        $tester->setInputs(['All']);
        $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('flag_enabled', $tester->getDisplay());
        $this->assertStringContainsString('flag_disabled', $tester->getDisplay());
        $this->assertStringContainsString('flag_locked', $tester->getDisplay());
    }

    public function testListEnabled(): void
    {
        $repository = $this->createMock(FeatureFlagRepository::class);
        $repository->expects($this->once())->method('findBy')->with(['isEnabled' => true])->willReturn([$this->enabledFlag]);

        $tester = $this->makeCommand($repository);
        $tester->setInputs(['Enabled']);
        $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('flag_enabled', $tester->getDisplay());
        $this->assertStringNotContainsString('flag_disabled', $tester->getDisplay());
    }

    public function testListDisabled(): void
    {
        $repository = $this->createMock(FeatureFlagRepository::class);
        $repository->expects($this->once())->method('findBy')->with(['isEnabled' => false])->willReturn([$this->disabledFlag]);

        $tester = $this->makeCommand($repository);
        $tester->setInputs(['Disabled']);
        $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('flag_disabled', $tester->getDisplay());
        $this->assertStringNotContainsString('flag_enabled', $tester->getDisplay());
    }

    public function testListLocked(): void
    {
        $repository = $this->createMock(FeatureFlagRepository::class);
        $repository->expects($this->once())->method('findBy')->with(['isLocked' => true])->willReturn([$this->lockedFlag]);

        $tester = $this->makeCommand($repository);
        $tester->setInputs(['Locked']);
        $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('flag_locked', $tester->getDisplay());
        $this->assertStringNotContainsString('flag_enabled', $tester->getDisplay());
    }

    // --- Mode non-interactif ---

    public function testNonInteractiveListAll(): void
    {
        $repository = $this->createMock(FeatureFlagRepository::class);
        $repository->expects($this->once())->method('findAll')->willReturn([$this->enabledFlag, $this->disabledFlag, $this->lockedFlag]);

        $tester = $this->makeCommand($repository);
        $tester->execute(['filter' => 'All']);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('flag_enabled', $tester->getDisplay());
        $this->assertStringContainsString('flag_disabled', $tester->getDisplay());
        $this->assertStringContainsString('flag_locked', $tester->getDisplay());
    }

    public function testNonInteractiveListEnabled(): void
    {
        $repository = $this->createMock(FeatureFlagRepository::class);
        $repository->expects($this->once())->method('findBy')->with(['isEnabled' => true])->willReturn([$this->enabledFlag]);

        $tester = $this->makeCommand($repository);
        $tester->execute(['filter' => 'Enabled']);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('flag_enabled', $tester->getDisplay());
        $this->assertStringNotContainsString('flag_disabled', $tester->getDisplay());
    }

    public function testNonInteractiveListDisabled(): void
    {
        $repository = $this->createMock(FeatureFlagRepository::class);
        $repository->expects($this->once())->method('findBy')->with(['isEnabled' => false])->willReturn([$this->disabledFlag]);

        $tester = $this->makeCommand($repository);
        $tester->execute(['filter' => 'Disabled']);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('flag_disabled', $tester->getDisplay());
        $this->assertStringNotContainsString('flag_enabled', $tester->getDisplay());
    }

    public function testNonInteractiveListLocked(): void
    {
        $repository = $this->createMock(FeatureFlagRepository::class);
        $repository->expects($this->once())->method('findBy')->with(['isLocked' => true])->willReturn([$this->lockedFlag]);

        $tester = $this->makeCommand($repository);
        $tester->execute(['filter' => 'Locked']);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('flag_locked', $tester->getDisplay());
        $this->assertStringNotContainsString('flag_enabled', $tester->getDisplay());
    }
}
