<?php

namespace Devexploris\ShizukuFeatureFlags;

use Devexploris\ShizukuFeatureFlags\Command\CreateCommand;
use Devexploris\ShizukuFeatureFlags\Command\DeleteCommand;
use Devexploris\ShizukuFeatureFlags\Command\DisableCommand;
use Devexploris\ShizukuFeatureFlags\Command\EnableCommand;
use Devexploris\ShizukuFeatureFlags\Command\ListCommand;
use Devexploris\ShizukuFeatureFlags\Command\LockCommand;
use Devexploris\ShizukuFeatureFlags\DataCollector\FeatureFlagDataCollector;
use Devexploris\ShizukuFeatureFlags\Repository\FeatureFlagRepository;
use Devexploris\ShizukuFeatureFlags\Service\FeatureFlagService;
use Devexploris\ShizukuFeatureFlags\Twig\FeatureFlagExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

class ShizukuFeatureFlagsBundle extends AbstractBundle
{
    public function loadExtension(array $config, ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        $cache = service('cache.app')->nullOnInvalid();

        $configurator->services()
            ->set(FeatureFlagRepository::class)->autowire()
            ->set(FeatureFlagService::class)->autowire()
                ->arg('$cache', $cache)
            ->set(ListCommand::class)->autowire()->tag('console.command')
            ->set(CreateCommand::class)->autowire()->tag('console.command')
                ->arg('$cache', $cache)
            ->set(EnableCommand::class)->autowire()->tag('console.command')
                ->arg('$cache', $cache)
            ->set(DisableCommand::class)->autowire()->tag('console.command')
                ->arg('$cache', $cache)
            ->set(LockCommand::class)->autowire()->tag('console.command')
                ->arg('$cache', $cache)
            ->set(DeleteCommand::class)->autowire()->tag('console.command')
                ->arg('$cache', $cache)
            ->set(FeatureFlagDataCollector::class)->autowire()->tag('data_collector', [
                'template' => '@ShizukuFeatureFlags/data_collector/shizuku.html.twig',
                'id' => 'shizuku.feature_flags',
            ])
            ->set(FeatureFlagExtension::class)->autowire()->tag('twig.extension');
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $builder->prependExtensionConfig('twig', [
            'paths' => [__DIR__ . '/../templates' => 'ShizukuFeatureFlags'],
        ]);
    }
}
