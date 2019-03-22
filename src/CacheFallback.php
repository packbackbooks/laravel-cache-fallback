<?php

namespace Fingo\LaravelCacheFallback;

use Exception;
use Illuminate\Cache\CacheManager;
use Illuminate\Support\Facades\Log;
use Predis\CommunicationException;

/**
 * Class CacheFallback
 * @package Fingo\LaravelCacheFallback
 */
class CacheFallback extends CacheManager
{
    /**
     * Dynamically call the default driver instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (config('cache_fallback.fallback_on_call_failure')) {
            return $this->callFallback($method, $parameters);
        }
        return parent::__call($method, $parameters);
    }

    /**
     * Handle fallbacks on __call if exceptions occur
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    private function callFallback($method, $parameters)
    {
        // We have two levels of try-catches since we are catching different exception types
        try {
            try {
                return parent::__call($method, $parameters);
            } catch (CommunicationException $e) {
                // Only retry if we got a connection error, to avoid other errors from doing unwanted retries
                // We are subtracting from $attempts since we already have completed 1 attempt on line 30
                $attempts = config('cache_fallback.attempts_before_fallback') - 1;
                return retry($attempts, function () use ($method, $parameters) {
                    return parent::__call($method, $parameters);
                }, config('cache_fallback.interval_between_attempts'));
            }
        } catch (Exception $e) {
            report($e);

            if ($newDriver = $this->nextDriver($this->getDefaultDriver())) {
                return $this->store($newDriver)->$method(...$parameters);
            }
            // Throw the exception if we have exhaused all our options
            throw $e;
        }
    }

    /**
     * Resolve the given store.
     *
     * @param  string $name
     * @return \Illuminate\Contracts\Cache\Repository
     * @throws Exception
     */
    protected function resolve($name)
    {
        // Handle errors during initalization of the cache store
        try {
            return retry(
                config('cache_fallback.attempts_before_fallback'),
                function () use ($name) {
                    return parent::resolve($name);
                }, config('cache_fallback.interval_between_attempts')
            );
        } catch (Exception $e) {
            report($e);

            if ($newDriver = $this->nextDriver($name)) {
                return $this->resolve($newDriver);
            }
            // Throw the exception if we have exhaused all our options
            throw $e;
        }
    }

    /**
     * Get next driver name based on fallback order
     *
     * @param $driverName
     * @return string|null
     */
    private function nextDriver($driverName)
    {
        $driverOrder = config('cache_fallback.fallback_order');
        if (in_array($driverName, $driverOrder, true) && last($driverOrder) !== $driverName) {
            $nextKey = array_search($driverName, $driverOrder, true) + 1;
            return $driverOrder[$nextKey];
        }
        return null;
    }

    /**
     * Create an instance of the Redis cache driver.
     *
     * @param  array $config
     * @return \Illuminate\Cache\RedisStore
     */
    protected function createRedisDriver(array $config)
    {
        $redisDriver = parent::createRedisDriver($config);
        $redisDriver->connection()->ping();
        return $redisDriver;
    }

    /**
     * Create an instance of the database cache driver.
     *
     * @param  array $config
     * @return \Illuminate\Cache\DatabaseStore
     */
    protected function createDatabaseDriver(array $config)
    {
        $databaseDriver = parent::createDatabaseDriver($config);
        $databaseDriver->get('test');
        return $databaseDriver;
    }
}
