<?php

namespace Devexploris\ShizukuFeatureFlags\Tests\Unit\Service;

use Devexploris\ShizukuFeatureFlags\DataCollector\FeatureFlagDataCollector;
use Devexploris\ShizukuFeatureFlags\Entity\FeatureFlag;
use Devexploris\ShizukuFeatureFlags\Repository\FeatureFlagRepository;
use Devexploris\ShizukuFeatureFlags\Service\FeatureFlagService;
use PHPUnit\Framework\TestCase;

class FeatureFlagServiceTest extends TestCase
{
    public function testIsEnabledReturnsFalseWhenFlagNotFound(): void
    {
        $repository = $this->createMock(FeatureFlagRepository::class);
        $repository->expects($this->once())->method('findOneBy')->willReturn(null);

        $service = new FeatureFlagService($repository);

        $this->assertFalse($service->isEnabled('unknown_flag'));
    }

    public function testIsEnabledReturnsTrueWhenFlagIsEnabled(): void
    {
        $flag = new FeatureFlag('know_flag', 'flag test description')->setIsEnabled(true);

        $repository = $this->createMock(FeatureFlagRepository::class);
        $repository->expects($this->once())->method('findOneBy')->willReturn($flag);

        $service = new FeatureFlagService($repository);

        $this->assertTrue($service->isEnabled('know_flag'));
    }

    public function testIsEnabledReturnsFalseWhenFlagIsDisabled(): void
    {
        $flag = new FeatureFlag('know_flag', 'flag test description');

        $repository = $this->createMock(FeatureFlagRepository::class);
        $repository->expects($this->once())->method('findOneBy')->willReturn($flag);

        $service = new FeatureFlagService($repository);

        $this->assertFalse($service->isEnabled('know_flag'));
    }

    public function testCollectorRecordIsCalledWithCallersArray(): void
    {
        $flag = new FeatureFlag('my_flag', 'desc')->setIsEnabled(true);

        $repository = $this->createStub(FeatureFlagRepository::class);
        $repository->method('findOneBy')->willReturn($flag);

        $collector = $this->createMock(FeatureFlagDataCollector::class);
        $collector->expects($this->once())
            ->method('record')
            ->with(
                'my_flag',
                true,
                false,
                $this->callback(fn(array $callers) => is_array($callers)
                    && count($callers) > 0
                    && isset($callers[0]['class'], $callers[0]['function'])
                )
            );

        $service = new FeatureFlagService($repository, $collector);
        $service->isEnabled('my_flag');
    }

    public function testCollectorRecordUnknownIsCalledWithCallersArray(): void
    {
        $repository = $this->createStub(FeatureFlagRepository::class);
        $repository->method('findOneBy')->willReturn(null);

        $collector = $this->createMock(FeatureFlagDataCollector::class);
        $collector->expects($this->once())
            ->method('recordUnknown')
            ->with(
                'ghost_flag',
                $this->callback(fn(array $callers) => is_array($callers)
                    && count($callers) > 0
                    && isset($callers[0]['class'], $callers[0]['function'])
                )
            );

        $service = new FeatureFlagService($repository, $collector);
        $service->isEnabled('ghost_flag');
    }

    public function testVendorFramesAreExcludedFromCallers(): void
    {
        $flag = new FeatureFlag('my_flag', 'desc')->setIsEnabled(true);

        $repository = $this->createStub(FeatureFlagRepository::class);
        $repository->method('findOneBy')->willReturn($flag);

        $collector = $this->createMock(FeatureFlagDataCollector::class);
        $collector->expects($this->once())
            ->method('record')
            ->with(
                'my_flag',
                true,
                false,
                $this->callback(function (array $callers) {
                    foreach ($callers as $caller) {
                        if (str_contains($caller['class'] ?? '', 'PHPUnit')) {
                            return false;
                        }
                    }
                    return true;
                })
            );

        $service = new FeatureFlagService($repository, $collector);
        $service->isEnabled('my_flag');
    }
}
