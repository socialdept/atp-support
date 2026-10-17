<?php

namespace SocialDept\AtpSupport\Concerns;

trait HasConfig
{
    /**
     * Get configuration value with fallback for testing.
     */
    protected function getConfig(string $key, mixed $default): mixed
    {
        if (! function_exists('config')) {
            return $default;
        }

        try {
            return config($key, $default);
        } catch (\Throwable) {
            return $default;
        }
    }

    /**
     * Guzzle timeout options for a resolver. A separate connect timeout means
     * an unreachable host fails in seconds instead of holding the caller for
     * the whole request timeout, which matters when resolution runs while a
     * user waits.
     *
     * @return array{timeout: int|float, connect_timeout: int|float}
     */
    protected function timeouts(int|float|null $timeout = null, int|float|null $connectTimeout = null): array
    {
        return [
            'timeout' => $timeout ?? $this->getConfig('atp-support.timeout', 5),
            'connect_timeout' => $connectTimeout ?? $this->getConfig('atp-support.connect_timeout', 2),
        ];
    }
}
