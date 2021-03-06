<?php

namespace Test;

use Fingo\LaravelCacheFallback\CacheFallback;
use Fingo\LaravelCacheFallback\CacheFallbackServiceProvider;
use Mockery;
use Orchestra\Testbench\TestCase;
use Predis\Connection\ConnectionException;

class CacheFallbackTest extends TestCase
{

    protected $application, $connectionException;

    public function setUp(): void
    {
        parent::setUp();
        $this->application = $this->createApplication();
        $this->connectionException = new ConnectionException(
            Mockery::mock('Predis\Connection\AbstractConnection'),
            'message',
            1,
            null
        );
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('cache.default', 'redis');
        $app['config']->set('app.key', 'hh8oYDaXmHZQ6uNhaq7HWtpDDucMtD5C');
        $app['config']->set('cache_fallback.attempts_before_fallback', 3);
        $app['config']->set('cache_fallback.fallback_on_call_failure', true);
    }

    protected function getPackageProviders($app)
    {
        return [CacheFallbackServiceProvider::class];
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testRedisStore()
    {
        $mock = Mockery::mock('overload:Predis\Client');
        $mock->shouldReceive('ping')->andReturn(true);
        $cache = new CacheFallback($this->application);
        $this->assertInstanceOf('Illuminate\Cache\RedisStore', $cache->driver()->getStore());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testRetryCallsAndSucceed()
    {
        $mock = Mockery::mock('overload:Predis\Client');
        $mock->shouldReceive('ping')->andReturn(true);

        $desiredResult = true;

        $mock->shouldReceive('get')
            ->andThrow(new \Exception) // done since we can't do $mock->_throw = true directly
            // This will return two connection exceptions, but then succeed on the third time
            ->andReturn(
                $this->connectionException,
                $this->connectionException,
                serialize($desiredResult)
            )->times(3);

        $cache = new CacheFallback($this->application);

        $this->assertEquals($desiredResult, $cache->get('test'));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testRetryCallsAndFail()
    {
        $mock = Mockery::mock('overload:Predis\Client');
        $mock->shouldReceive('ping')->andReturn(true);

        $mock->shouldReceive('get')
            ->andThrow($this->connectionException)
            ->times(3);

        $cache = new CacheFallback($this->application);

        // Assert null since we moved on to the next driver and don't have it
        $this->assertNull($cache->get('test'));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testMemcacheStore()
    {
        $this->createFailRedis();
        $this->createSuccessMemcached();
        $cache = new CacheFallback($this->application);
        $this->assertInstanceOf('Illuminate\Cache\MemcachedStore', $cache->driver()->getStore());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testFileStore()
    {
        $this->createFailRedis();
        $this->createFailMemcached();
        $this->createFailDb();
        $cache = new CacheFallback($this->application);
        $this->assertInstanceOf('Illuminate\Cache\FileStore', $cache->driver()->getStore());
    }

    private function createSuccessMemcached()
    {
        $mockMemcached = Mockery::mock('overload:Illuminate\Cache\MemcachedConnector');
        $mockMemcached->shouldReceive('connect')->andReturn(true);
    }

    private function createFailMemcached()
    {
        $mockMemcached = Mockery::mock('overload:Illuminate\Cache\MemcachedConnector');
        $mockMemcached->shouldReceive('connect')->andThrow(new \Exception);
    }

    private function createFailRedis()
    {
        $mockRedis = Mockery::mock('overload:Predis\Client');
        $mockRedis->shouldReceive('ping')->andThrow(new \Exception);
    }

    private function createFailDb()
    {
        \DB::shouldReceive('connection')->andThrow(new \Exception);
    }
}
