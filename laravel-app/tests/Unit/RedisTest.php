<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

class RedisTest extends TestCase
{
    public function testRedisConnection(): void
    {
        $this->assertInstanceOf(\Redis::class, self::$redisConnection);
        $this->assertTrue(self::$redisConnection->ping());
    }
    
    public function testRedisOperations(): void
    {
        $this->testRedisOperations();
    }
    
    public function testRedisPerformance(): void
    {
        $this->testRedisPerformance();
    }
}
