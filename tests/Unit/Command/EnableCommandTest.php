<?php

namespace Devexploris\ShizukuFeatureFlags\Tests\Unit\Command;

use Devexploris\ShizukuFeatureFlags\Command\EnableCommand;
use Devexploris\ShizukuFeatureFlags\Entity\FeatureFlag;
use Devexploris\ShizukuFeatureFlags\Repository\FeatureFlagRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class EnableCommandTest extends TestCase
{
    // --- Mode interactif ---

    public function testEnablesFlagSuccessfully(): void
    {
        $flag = new FeatureFlag('checkout_new_flow', 'New checkout', false);

        $repository = $this->createMock(FeatureFlagRepository::class);
        $repository->expects($this->once())->method('findBy')->with(['isEnabled' => false, 'isLocked' => false])->willReturn([$flag]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('flush');

        $tester = new CommandTester(new EnableCommand($repository, $em));
        $tester->setInputs(['checkout_new_flow']);
        $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertTrue($flag->isEnabled());
        $this->assertStringContainsString('checkout_new_flow', $tester->getDisplay());
    }

    public function testReturnSuccessWhenNoDisabledFlags(): void
    {
        $repository = $this->createMock(FeatureFlagRepository::class);
        $repository->expects($this->once())->method('findBy')->with(['isEnabled' => false, 'isLocked' => false])->willReturn([]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('flush');

        $tester = new CommandTester(new EnableCommand($repository, $em));
        $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('No disabled and unlocked', $tester->getDisplay());
    }

    // --- Mode non-interactif ---

    public function testNonInteractiveEnablesFlagByName(): void
    {
        $flag = new FeatureFlag('my_flag', 'A flag', false);

        $repository = $this->createMock(FeatureFlagRepository::class);
        $repository->expects($this->once())->method('findOneBy')->with(['name' => 'my_flag'])->willReturn($flag);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('flush');

        $tester = new CommandTester(new EnableCommand($repository, $em));
        $tester->execute(['--name' => 'my_flag']);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertTrue($flag->isEnabled());
        $this->assertStringContainsString('my_flag', $tester->getDisplay());
    }

    public function testNonInteractiveReturnsFailureWhenFlagNotFound(): void
    {
        $repository = $this->createMock(FeatureFlagRepository::class);
        $repository->expects($this->once())->method('findOneBy')->with(['name' => 'unknown_flag'])->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('flush');

        $tester = new CommandTester(new EnableCommand($repository, $em));
        $tester->execute(['--name' => 'unknown_flag']);

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertStringContainsString('does not exist', $tester->getDisplay());
    }

    public function testNonInteractiveReturnsFailureWhenFlagIsLocked(): void
    {
        $flag = new FeatureFlag('locked_flag', 'A locked flag', false);
        $flag->setIsLocked(true);

        $repository = $this->createMock(FeatureFlagRepository::class);
        $repository->expects($this->once())->method('findOneBy')->with(['name' => 'locked_flag'])->willReturn($flag);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('flush');

        $tester = new CommandTester(new EnableCommand($repository, $em));
        $tester->execute(['--name' => 'locked_flag']);

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertStringContainsString('locked', $tester->getDisplay());
    }
}
