<?php

namespace Devexploris\ShizukuFeatureFlags\DataCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

class FeatureFlagDataCollector extends DataCollector
{
    public function record(string $name, bool $enabled, bool $locked, array $callers = []): void
    {
        $this->data['flags'][$name] = ['enabled' => $enabled, 'locked' => $locked, 'callers' => $callers];
    }

    public function recordUnknown(string $name, array $callers = []): void
    {
        $this->data['unknown'][] = ['name' => $name, 'callers' => $callers];
    }

    public function collect(Request $request, Response $response, ?\Throwable $exception = null): void {}

    public function getFlags(): array
    {
        return $this->data['flags'] ?? [];
    }

    public function getUnknownFlags(): array
    {
        return $this->data['unknown'] ?? [];
    }

    public function getEnabledCount(): int
    {
        return count(array_filter($this->data['flags'] ?? [], fn(array $f) => $f['enabled']));
    }

    public function getTotalCount(): int
    {
        return count($this->data['flags'] ?? []);
    }

    public function getUnknownCount(): int
    {
        return count($this->data['unknown'] ?? []);
    }

    public function getLockedCount(): int
    {
        return count(array_filter($this->data['flags'] ?? [], fn(array $f) => $f['locked']));
    }


    public function getName(): string
    {
        return 'shizuku.feature_flags';
    }
}
