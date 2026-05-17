<?php

namespace Devexploris\ShizukuFeatureFlags\Tests\Unit\Twig;

use Devexploris\ShizukuFeatureFlags\Service\FeatureFlagService;
use Devexploris\ShizukuFeatureFlags\Twig\FeatureFlagExtension;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\TwigFunction;

class FeatureFlagExtensionTest extends TestCase
{
    public function testGetFunctionsRegistersFeatureFunction(): void
    {
        $service = $this->createStub(FeatureFlagService::class);
        $extension = new FeatureFlagExtension($service);

        $functions = $extension->getFunctions();

        $this->assertCount(1, $functions);
        $this->assertInstanceOf(TwigFunction::class, $functions[0]);
        $this->assertSame('feature', $functions[0]->getName());
    }

    public function testFeatureFunctionDelegatesToService(): void
    {
        $service = $this->createMock(FeatureFlagService::class);
        $service->expects($this->exactly(2))
            ->method('isEnabled')
            ->willReturnMap([
                ['enabled_flag', true],
                ['disabled_flag', false],
            ]);

        $extension = new FeatureFlagExtension($service);
        $callable = $extension->getFunctions()[0]->getCallable();

        $this->assertTrue($callable('enabled_flag'));
        $this->assertFalse($callable('disabled_flag'));
    }

    private function buildTwig(FeatureFlagService $service): Environment
    {
        $twig = new Environment(new ArrayLoader());
        $twig->addExtension(new FeatureFlagExtension($service));
        return $twig;
    }

    public function testTemplateRendersContentWhenFeatureEnabled(): void
    {
        $service = $this->createStub(FeatureFlagService::class);
        $service->method('isEnabled')->willReturn(true);

        $twig = $this->buildTwig($service);
        $twig->createTemplate('{% if feature("my_flag") %}enabled{% endif %}');

        $result = $twig->createTemplate('{% if feature("my_flag") %}enabled{% endif %}')->render();

        $this->assertSame('enabled', $result);
    }

    public function testTemplateRendersNothingWhenFeatureDisabled(): void
    {
        $service = $this->createStub(FeatureFlagService::class);
        $service->method('isEnabled')->willReturn(false);

        $result = $this->buildTwig($service)
            ->createTemplate('{% if feature("my_flag") %}enabled{% endif %}')
            ->render();

        $this->assertSame('', $result);
    }

    public function testTemplateRendersNothingForUnknownFlagWithoutException(): void
    {
        $service = $this->createStub(FeatureFlagService::class);
        $service->method('isEnabled')->willReturn(false);

        $result = $this->buildTwig($service)
            ->createTemplate('{% if feature("unknown_flag") %}enabled{% endif %}')
            ->render();

        $this->assertSame('', $result);
    }
}
