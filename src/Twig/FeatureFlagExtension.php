<?php

namespace Devexploris\ShizukuFeatureFlags\Twig;

use Devexploris\ShizukuFeatureFlags\Service\FeatureFlagService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class FeatureFlagExtension extends AbstractExtension
{
    public function __construct(private readonly FeatureFlagService $service) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('feature', $this->service->isEnabled(...)),
        ];
    }
}
