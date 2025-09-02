<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function testBasicAssertion(): void
    {
        $this->assertTrue(true);
    }
    
    public function testPhpVersion(): void
    {
        $this->assertGreaterThanOrEqual('8.1', PHP_VERSION);
    }
}
