<?php

namespace Devexploris\ShizukuFeatureFlags\Tests\Kernel;

use Devexploris\ShizukuFeatureFlags\ShizukuFeatureFlagsBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class TestKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new DoctrineBundle();
        yield new ShizukuFeatureFlagsBundle();
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'test' => true,
            'secret' => 'test_secret',
        ]);

        $container->extension('doctrine', [
            'dbal' => ['url' => 'sqlite:///:memory:'],
            'orm' => [
                'auto_generate_proxy_classes' => true,
                'mappings' => [
                    'ShizukuFeatureFlags' => [
                        'type' => 'attribute',
                        'is_bundle' => false,
                        'dir' => __DIR__ . '/../../src/Entity',
                        'prefix' => 'Devexploris\ShizukuFeatureFlags\Entity',
                        'alias' => 'ShizukuFeatureFlags',
                    ],
                ],
            ],
        ]);
    }

    protected function configureRoutes(RoutingConfigurator $routes): void {}

    public function getCacheDir(): string
    {
        return sys_get_temp_dir() . '/shizuku_test_cache';
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir() . '/shizuku_test_logs';
    }
}
