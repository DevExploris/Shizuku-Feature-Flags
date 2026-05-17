<?php

namespace Devexploris\ShizukuFeatureFlags\Tests\Unit\DataCollector;

use Devexploris\ShizukuFeatureFlags\DataCollector\FeatureFlagDataCollector;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FeatureFlagDataCollectorTest extends TestCase
{
    private FeatureFlagDataCollector $collector;

    protected function setUp(): void
    {
        $this->collector = new FeatureFlagDataCollector();
        $this->collector->collect(new Request(), new Response());
    }

    public function testRecordStoresExpectedKeys(): void
    {
        $this->collector->record('my_flag', true, false, [
            ['class' => 'App\Controller\TestController', 'function' => 'index'],
        ]);

        $flags = $this->collector->getFlags();

        $this->assertArrayHasKey('my_flag', $flags);
        $this->assertArrayHasKey('enabled', $flags['my_flag']);
        $this->assertArrayHasKey('locked', $flags['my_flag']);
        $this->assertArrayHasKey('callers', $flags['my_flag']);
        $this->assertArrayNotHasKey('class', $flags['my_flag']);
        $this->assertArrayNotHasKey('file', $flags['my_flag']);
    }

    public function testRecordUnknownStoresExpectedKeys(): void
    {
        $this->collector->recordUnknown('ghost_flag', [
            ['class' => 'App\Service\MyService', 'function' => 'doSomething'],
        ]);

        $unknown = $this->collector->getUnknownFlags();

        $this->assertCount(1, $unknown);
        $this->assertArrayHasKey('name', $unknown[0]);
        $this->assertArrayHasKey('callers', $unknown[0]);
        $this->assertArrayNotHasKey('class', $unknown[0]);
        $this->assertArrayNotHasKey('file', $unknown[0]);
        $this->assertSame('ghost_flag', $unknown[0]['name']);
    }

    public function testCallersChainIsPreserved(): void
    {
        $callers = [
            ['class' => 'App\Service\MyService', 'function' => 'doSomething'],
            ['class' => 'App\Controller\TestController', 'function' => 'index'],
        ];

        $this->collector->record('chained_flag', false, false, $callers);

        $flags = $this->collector->getFlags();
        $this->assertCount(2, $flags['chained_flag']['callers']);
        $this->assertSame('App\Service\MyService', $flags['chained_flag']['callers'][0]['class']);
        $this->assertSame('App\Controller\TestController', $flags['chained_flag']['callers'][1]['class']);
    }

    public function testCounters(): void
    {
        $this->collector->record('flag_a', true, false);
        $this->collector->record('flag_b', false, true);
        $this->collector->recordUnknown('flag_c');

        $this->assertSame(2, $this->collector->getTotalCount());
        $this->assertSame(1, $this->collector->getEnabledCount());
        $this->assertSame(1, $this->collector->getLockedCount());
        $this->assertSame(1, $this->collector->getUnknownCount());
    }
}
