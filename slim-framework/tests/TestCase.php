<?php

declare(strict_types=1);

namespace Tests;

require_once __DIR__ . '/../_shared/TestFramework/BaseTestCase.php';
require_once __DIR__ . '/../_shared/TestFramework/DatabaseTestTrait.php';
require_once __DIR__ . '/../_shared/TestFramework/HttpTestTrait.php';

use Shared\TestFramework\BaseTestCase;
use Shared\TestFramework\DatabaseTestTrait;
use Shared\TestFramework\HttpTestTrait;

/**
 * Base test case for slim-framework application
 * Extends shared testing framework with app-specific functionality
 */
abstract class TestCase extends BaseTestCase
{
    use DatabaseTestTrait;
    use HttpTestTrait;
    
    protected string $baseUrl;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->baseUrl = $this->getApplicationBaseUrl();
        $this->setupApplicationEnvironment();
    }
    
    protected function tearDown(): void
    {
        $this->cleanupApplicationEnvironment();
        parent::tearDown();
    }
    
    /**
     * Get application base URL
     */
    protected function getApplicationBaseUrl(): string
    {
        $ports = [
            'simple-php' => '8000',
            'laravel-app' => '8004',
            'symfony-app' => '8002',
            'slim-framework' => '8001',
            'codeigniter-app' => '8003',
        ];
        
        $port = $ports['slim-framework'] ?? '8000';
        return "http://localhost:{$port}";
    }
    
    /**
     * Setup application-specific environment
     */
    protected function setupApplicationEnvironment(): void
    {
        // Load environment variables
        if (file_exists(__DIR__ . '/../.env')) {
            $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) {
                    continue;
                }
                list($name, $value) = explode('=', $line, 2);
                $_ENV[trim($name)] = trim($value);
            }
        }
        
        // Setup test database schema
        $this->createTestSchema();
    }
    
    /**
     * Cleanup application-specific environment
     */
    protected function cleanupApplicationEnvironment(): void
    {
        // Cleanup is handled by parent class
    }
    
    /**
     * Assert application is running
     */
    protected function assertApplicationIsRunning(): void
    {
        $response = $this->httpGet($this->baseUrl);
        $this->assertHttpSuccess($response);
    }
}
