<?php

namespace Devexploris\ShizukuFeatureFlags\Tests\Unit\Command;

use Devexploris\ShizukuFeatureFlags\Command\LockCommand;
use Devexploris\ShizukuFeatureFlags\Entity\FeatureFlag;
use Devexploris\ShizukuFeatureFlags\Repository\FeatureFlagRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class LockCommandTest extends TestCase
{
    // --- Mode interactif ---

    public function testLocksFlagSuccessfully(): void
    {
        $flag = new FeatureFlag('checkout_new_flow', 'New checkout', true);

        $repository = $this->createMock(FeatureFlagRepository::class);
        $repository->expects($this->once())->method('findBy')->with(['isLocked' => false])->willReturn([$flag]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('flush');

        $tester = new CommandTester(new LockCommand($repository, $em));
        $tester->setInputs(['checkout_new_flow']);
        $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertTrue($flag->isLocked());
        $this->assertStringContainsString('checkout_new_flow', $tester->getDisplay());
    }

    public function testReturnSuccessWhenAllFlagsAlreadyLocked(): void
    {
        $repository = $this->createMock(FeatureFlagRepository::class);
        $repository->expects($this->once())->method('findBy')->with(['isLocked' => false])->willReturn([]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('flush');

        $tester = new CommandTester(new LockCommand($repository, $em));
        $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('already locked', $tester->getDisplay());
    }

    // --- Mode non-interactif ---

    public function testNonInteractiveLocksFlagByName(): void
    {
        $flag = new FeatureFlag('my_flag', 'A flag', true);

        $repository = $this->createMock(FeatureFlagRepository::class);
        $repository->expects($this->once())->method('findOneBy')->with(['name' => 'my_flag'])->willReturn($flag);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('flush');

        $tester = new CommandTester(new LockCommand($repository, $em));
        $tester->execute(['--name' => 'my_flag']);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertTrue($flag->isLocked());
        $this->assertStringContainsString('my_flag', $tester->getDisplay());
    }

    public function testNonInteractiveReturnsFailureWhenFlagNotFound(): void
    {
        $repository = $this->createMock(FeatureFlagRepository::class);
        $repository->expects($this->once())->method('findOneBy')->with(['name' => 'unknown_flag'])->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('flush');

        $tester = new CommandTester(new LockCommand($repository, $em));
        $tester->execute(['--name' => 'unknown_flag']);

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertStringContainsString('does not exist', $tester->getDisplay());
    }

    public function testNonInteractiveReturnsFailureWhenFlagAlreadyLocked(): void
    {
        $flag = new FeatureFlag('locked_flag', 'A locked flag', true);
        $flag->setIsLocked(true);

        $repository = $this->createMock(FeatureFlagRepository::class);
        $repository->expects($this->once())->method('findOneBy')->with(['name' => 'locked_flag'])->willReturn($flag);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('flush');

        $tester = new CommandTester(new LockCommand($repository, $em));
        $tester->execute(['--name' => 'locked_flag']);

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertStringContainsString('already locked', $tester->getDisplay());
    }
}
