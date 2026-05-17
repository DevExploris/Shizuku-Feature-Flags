<?php

namespace Devexploris\ShizukuFeatureFlags\Tests\Unit\Service;

use Devexploris\ShizukuFeatureFlags\Entity\FeatureFlag;
use Devexploris\ShizukuFeatureFlags\Repository\FeatureFlagRepository;
use Devexploris\ShizukuFeatureFlags\Service\FeatureFlagService;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class FeatureFlagServiceCacheTest extends TestCase
{
    public function testCacheHitDoesNotQueryRepository(): void
    {
        $repository = $this->createMock(FeatureFlagRepository::class);
        $repository->expects($this->never())->method('findOneBy');

        $cache = $this->createStub(CacheInterface::class);
        $cache->method('get')->willReturn(['found' => true, 'enabled' => true, 'locked' => false]);

        $service = new FeatureFlagService($repository, null, $cache);

        $this->assertTrue($service->isEnabled('my_flag'));
    }

    public function testCacheMissQueriesRepositoryAndCachesResult(): void
    {
        $flag = new FeatureFlag('my_flag', 'desc')->setIsEnabled(true);

        $repository = $this->createMock(FeatureFlagRepository::class);
        $repository->expects($this->once())->method('findOneBy')->willReturn($flag);

        $cache = $this->createStub(CacheInterface::class);
        $cache->method('get')->willReturnCallback(function (string $key, callable $callback) {
            $item = $this->createStub(ItemInterface::class);
            return $callback($item);
        });

        $service = new FeatureFlagService($repository, null, $cache);

        $this->assertTrue($service->isEnabled('my_flag'));
    }

    public function testCacheHitForUnknownFlagReturnsFalse(): void
    {
        $repository = $this->createMock(FeatureFlagRepository::class);
        $repository->expects($this->never())->method('findOneBy');

        $cache = $this->createStub(CacheInterface::class);
        $cache->method('get')->willReturn(['found' => false, 'enabled' => false, 'locked' => false]);

        $service = new FeatureFlagService($repository, null, $cache);

        $this->assertFalse($service->isEnabled('ghost_flag'));
    }

    public function testWithoutCacheFallsBackToRepository(): void
    {
        $flag = new FeatureFlag('my_flag', 'desc')->setIsEnabled(true);

        $repository = $this->createMock(FeatureFlagRepository::class);
        $repository->expects($this->once())->method('findOneBy')->willReturn($flag);

        $service = new FeatureFlagService($repository);

        $this->assertTrue($service->isEnabled('my_flag'));
    }

    public function testCacheKeyFormat(): void
    {
        $this->assertSame('shizuku_ff_my_flag', FeatureFlagService::cacheKey('my_flag'));
    }
}
