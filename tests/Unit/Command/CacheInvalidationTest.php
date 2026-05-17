<?php

namespace Devexploris\ShizukuFeatureFlags\Tests\Unit\Command;

use Devexploris\ShizukuFeatureFlags\Command\CreateCommand;
use Devexploris\ShizukuFeatureFlags\Command\DeleteCommand;
use Devexploris\ShizukuFeatureFlags\Command\DisableCommand;
use Devexploris\ShizukuFeatureFlags\Command\EnableCommand;
use Devexploris\ShizukuFeatureFlags\Command\LockCommand;
use Devexploris\ShizukuFeatureFlags\Entity\FeatureFlag;
use Devexploris\ShizukuFeatureFlags\Repository\FeatureFlagRepository;
use Devexploris\ShizukuFeatureFlags\Service\FeatureFlagService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Contracts\Cache\CacheInterface;

class CacheInvalidationTest extends TestCase
{
    private function em(): EntityManagerInterface
    {
        return $this->createStub(EntityManagerInterface::class);
    }

    public function testEnableInvalidatesCache(): void
    {
        $flag = new FeatureFlag('my_flag', 'desc');

        $repository = $this->createStub(FeatureFlagRepository::class);
        $repository->method('findOneBy')->willReturn($flag);

        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())->method('delete')->with(FeatureFlagService::cacheKey('my_flag'));

        (new CommandTester(new EnableCommand($repository, $this->em(), $cache)))
            ->execute(['--name' => 'my_flag']);
    }

    public function testEnableDoesNotInvalidateCacheOnFailure(): void
    {
        $repository = $this->createStub(FeatureFlagRepository::class);
        $repository->method('findOneBy')->willReturn(null);

        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->never())->method('delete');

        (new CommandTester(new EnableCommand($repository, $this->em(), $cache)))
            ->execute(['--name' => 'unknown_flag']);
    }

    public function testDisableInvalidatesCache(): void
    {
        $flag = new FeatureFlag('my_flag', 'desc')->setIsEnabled(true);

        $repository = $this->createStub(FeatureFlagRepository::class);
        $repository->method('findOneBy')->willReturn($flag);

        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())->method('delete')->with(FeatureFlagService::cacheKey('my_flag'));

        (new CommandTester(new DisableCommand($repository, $this->em(), $cache)))
            ->execute(['--name' => 'my_flag']);
    }

    public function testLockInvalidatesCache(): void
    {
        $flag = new FeatureFlag('my_flag', 'desc');

        $repository = $this->createStub(FeatureFlagRepository::class);
        $repository->method('findOneBy')->willReturn($flag);

        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())->method('delete')->with(FeatureFlagService::cacheKey('my_flag'));

        (new CommandTester(new LockCommand($repository, $this->em(), $cache)))
            ->execute(['--name' => 'my_flag']);
    }

    public function testDeleteInvalidatesCache(): void
    {
        $flag = new FeatureFlag('my_flag', 'desc');
        $flag->setIsLocked(true);

        $repository = $this->createStub(FeatureFlagRepository::class);
        $repository->method('findOneBy')->willReturn($flag);

        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())->method('delete')->with(FeatureFlagService::cacheKey('my_flag'));

        (new CommandTester(new DeleteCommand($repository, $this->em(), $cache)))
            ->execute(['--name' => 'my_flag']);
    }

    public function testCreateInvalidatesCache(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())->method('delete')->with(FeatureFlagService::cacheKey('new_flag'));

        (new CommandTester(new CreateCommand($this->em(), $cache)))
            ->execute(['--name' => 'new_flag', '--description' => 'desc']);
    }
}
