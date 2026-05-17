<?php

namespace Devexploris\ShizukuFeatureFlags\Service;

use Devexploris\ShizukuFeatureFlags\DataCollector\FeatureFlagDataCollector;
use Devexploris\ShizukuFeatureFlags\Repository\FeatureFlagRepository;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class FeatureFlagService
{
    private const CACHE_TTL = 300;

    public function __construct(
        private readonly FeatureFlagRepository $repository,
        private readonly ?FeatureFlagDataCollector $collector = null,
        private readonly ?CacheInterface $cache = null,
    ) {}

    // unknown flag = disabled by design
    public function isEnabled(string $name): bool
    {
        $callers = $this->collector !== null ? $this->resolveCallers() : [];

        if ($this->cache !== null) {
            $data = $this->cache->get(self::cacheKey($name), function (ItemInterface $item) use ($name) {
                $item->expiresAfter(self::CACHE_TTL);
                $flag = $this->repository->findOneBy(['name' => $name]);
                if ($flag === null) {
                    return ['found' => false, 'enabled' => false, 'locked' => false];
                }
                return ['found' => true, 'enabled' => $flag->isEnabled(), 'locked' => $flag->isLocked()];
            });
        } else {
            $flag = $this->repository->findOneBy(['name' => $name]);
            $data = $flag === null
                ? ['found' => false, 'enabled' => false, 'locked' => false]
                : ['found' => true, 'enabled' => $flag->isEnabled(), 'locked' => $flag->isLocked()];
        }

        if (!$data['found']) {
            $this->collector?->recordUnknown($name, $callers);
            return false;
        }

        $this->collector?->record($name, $data['enabled'], $data['locked'], $callers);
        return $data['enabled'];
    }

    public static function cacheKey(string $name): string
    {
        return 'shizuku_ff_' . $name;
    }

    /** @return array<int, array{class: string, function: string}> */
    private function resolveCallers(): array
    {
        $callers = [];
        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15) as $frame) {
            if (!isset($frame['class']) || $frame['class'] === self::class) {
                continue;
            }
            try {
                $classFile = (new \ReflectionClass($frame['class']))->getFileName();
            } catch (\ReflectionException) {
                continue;
            }
            if ($classFile === false || str_contains($classFile, '/vendor/')) {
                continue;
            }
            $callers[] = ['class' => $frame['class'], 'function' => $frame['function'] ?? '?'];
        }
        return $callers;
    }
}
