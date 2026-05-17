<?php

namespace Devexploris\ShizukuFeatureFlags\Tests\Unit\Command;

use Devexploris\ShizukuFeatureFlags\Command\CreateCommand;
use Devexploris\ShizukuFeatureFlags\Entity\FeatureFlag;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class CreateCommandTest extends TestCase
{
    // --- Mode interactif ---

    public function testCreateFlagPersistsCorrectEntity(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (FeatureFlag $flag): bool {
                return $flag->getName() === 'checkout_new_flow'
                    && $flag->getDescription() === 'New checkout flow'
                    && $flag->isEnabled() === true;
            }));
        $em->expects($this->once())->method('flush');

        $tester = new CommandTester(new CreateCommand($em));
        $tester->setInputs(['checkout_new_flow', 'New checkout flow', 'y']);
        $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('checkout_new_flow', $tester->getDisplay());
    }

    public function testCreateFlagDisabledByDefault(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (FeatureFlag $flag): bool {
                return $flag->isEnabled() === false;
            }));
        $em->expects($this->once())->method('flush');

        $tester = new CommandTester(new CreateCommand($em));
        $tester->setInputs(['my_flag', 'A flag', 'n']);
        $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
    }

    // --- Mode non-interactif ---

    public function testNonInteractiveCreateEnabledFlag(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (FeatureFlag $flag): bool {
                return $flag->getName() === 'my_new_flag'
                    && $flag->getDescription() === 'My description'
                    && $flag->isEnabled() === true;
            }));
        $em->expects($this->once())->method('flush');

        $tester = new CommandTester(new CreateCommand($em));
        $tester->execute([
            '--name'        => 'my_new_flag',
            '--description' => 'My description',
            '--enable'      => true,
        ]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('my_new_flag', $tester->getDisplay());
    }

    public function testNonInteractiveCreateDisabledFlag(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (FeatureFlag $flag): bool {
                return $flag->getName() === 'my_disabled_flag'
                    && $flag->isEnabled() === false;
            }));
        $em->expects($this->once())->method('flush');

        $tester = new CommandTester(new CreateCommand($em));
        $tester->execute([
            '--name'        => 'my_disabled_flag',
            '--description' => 'Disabled by default',
        ]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
    }

    public function testNonInteractiveRejectsInvalidSnakeCaseName(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('persist');
        $em->expects($this->never())->method('flush');

        $tester = new CommandTester(new CreateCommand($em));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('snake_case');

        $tester->execute([
            '--name'        => 'InvalidName',
            '--description' => 'Bad name',
        ]);
    }

    public function testNonInteractiveRejectsEmptyName(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('persist');

        $tester = new CommandTester(new CreateCommand($em));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('cannot be empty');

        $tester->execute([
            '--name'        => '',
            '--description' => 'No name',
        ]);
    }
}
