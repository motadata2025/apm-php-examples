<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

class BasicTest extends TestCase
{
    public function testPhpVersion(): void
    {
        $this->assertGreaterThanOrEqual('8.1', PHP_VERSION);
    }
    
    public function testRequiredExtensions(): void
    {
        $requiredExtensions = ['pdo', 'redis', 'curl', 'json', 'mbstring', 'openssl'];
        
        foreach ($requiredExtensions as $extension) {
            $this->assertTrue(
                extension_loaded($extension),
                "Required extension '{$extension}' is not loaded"
            );
        }
    }
    
    public function testEnvironmentConfiguration(): void
    {
        $this->assertNotEmpty($_ENV['APP_ENV'] ?? '', 'APP_ENV should be set');
    }
}
