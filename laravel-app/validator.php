<?php
/**
 * Laravel-app Application Validator
 * Tests database connectivity, Redis connectivity, and HTTP functionality
 * Updated for Laravel compatibility
 */

declare(strict_types=1);

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Set timeout for the entire script
set_time_limit(120);

// Bootstrap Laravel
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\ApmUiController;

class LaravelAppValidator
{
    private array $results = [];
    private float $startTime;
    private ApmUiController $controller;

    public function __construct()
    {
        $this->startTime = microtime(true);
        $this->controller = new ApmUiController();
    }

    public function validateAll(): array
    {
        echo "Starting Laravel APM validation...\n";

        // Test external API
        $this->validateExternalApi();

        // Test database connections
        $this->validateDatabases();

        // Test Redis operations
        $this->validateRedis();

        $this->results['summary'] = [
            'total_duration' => microtime(true) - $this->startTime,
            'php_version' => phpversion(),
            'laravel_version' => app()->version(),
            'timestamp' => date('Y-m-d H:i:s'),
            'overall_success' => $this->isOverallSuccess()
        ];

        return $this->results;
    }

    private function validateExternalApi(): void
    {
        echo "Testing external API...\n";
        try {
            $response = $this->controller->externalApi();
            $data = json_decode($response->getContent(), true);

            $this->results['external_api'] = [
                'success' => $data['ok'] ?? false,
                'details' => $data['details'] ?? [],
                'test_type' => 'external_api_call'
            ];

            echo $data['ok'] ? "✓ External API test passed\n" : "✗ External API test failed\n";
        } catch (Exception $e) {
            $this->results['external_api'] = [
                'success' => false,
                'error' => $e->getMessage(),
                'test_type' => 'external_api_call'
            ];
            echo "✗ External API test failed: " . $e->getMessage() . "\n";
        }
    }

    private function validateDatabases(): void
    {
        echo "Testing database connections...\n";
        try {
            $response = $this->controller->dbCheck();
            $data = json_decode($response->getContent(), true);

            $this->results['databases'] = [
                'success' => $data['ok'] ?? false,
                'details' => $data['details'] ?? [],
                'test_type' => 'database_connections'
            ];

            echo $data['ok'] ? "✓ Database connections test passed\n" : "✗ Database connections test failed\n";

            // Test CRUD operations
            echo "Testing database CRUD operations...\n";
            $crudResponse = $this->controller->dbCrud();
            $crudData = json_decode($crudResponse->getContent(), true);

            $this->results['database_crud'] = [
                'success' => $crudData['ok'] ?? false,
                'details' => $crudData['details'] ?? [],
                'test_type' => 'database_crud'
            ];

            echo $crudData['ok'] ? "✓ Database CRUD test passed\n" : "✗ Database CRUD test failed\n";

        } catch (Exception $e) {
            $this->results['databases'] = [
                'success' => false,
                'error' => $e->getMessage(),
                'test_type' => 'database_connections'
            ];
            echo "✗ Database test failed: " . $e->getMessage() . "\n";
        }
    }

    private function validateRedis(): void
    {
        echo "Testing Redis operations...\n";
        try {
            // Test Redis insert bulk
            $response = $this->controller->redisInsertBulk();
            $data = json_decode($response->getContent(), true);

            $this->results['redis_insert_bulk'] = [
                'success' => $data['ok'] ?? false,
                'details' => $data,
                'test_type' => 'redis_insert_bulk'
            ];

            // Test Redis read one
            $readResponse = $this->controller->redisReadOne();
            $readData = json_decode($readResponse->getContent(), true);

            $this->results['redis_read_one'] = [
                'success' => $readData['ok'] ?? false,
                'details' => $readData,
                'test_type' => 'redis_read_one'
            ];

            // Test Redis clear
            $clearResponse = $this->controller->redisClear();
            $clearData = json_decode($clearResponse->getContent(), true);

            $this->results['redis_clear'] = [
                'success' => $clearData['ok'] ?? false,
                'details' => $clearData,
                'test_type' => 'redis_clear'
            ];

            $allRedisSuccess = ($data['ok'] ?? false) && ($readData['ok'] ?? false) && ($clearData['ok'] ?? false);
            echo $allRedisSuccess ? "✓ Redis operations test passed\n" : "✗ Redis operations test failed\n";

        } catch (Exception $e) {
            $this->results['redis'] = [
                'success' => false,
                'error' => $e->getMessage(),
                'test_type' => 'redis_operations'
            ];
            echo "✗ Redis test failed: " . $e->getMessage() . "\n";
        }
    }

    private function isOverallSuccess(): bool
    {
        foreach ($this->results as $key => $result) {
            if ($key === 'summary') continue;
            if (isset($result['success']) && !$result['success']) {
                return false;
            }
        }
        return true;
    }
}

// Run validator if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $validator = new LaravelAppValidator();
    $results = $validator->validateAll();

    // Output single-line JSON summary to stdout
    $summary = [
        'app' => 'laravel-app',
        'php_version' => phpversion(),
        'laravel_version' => app()->version(),
        'timestamp' => date('Y-m-d H:i:s'),
        'success' => $results['summary']['overall_success'],
        'duration' => $results['summary']['total_duration'],
        'external_api_ok' => $results['external_api']['success'] ?? false,
        'databases_ok' => $results['databases']['success'] ?? false,
        'database_crud_ok' => $results['database_crud']['success'] ?? false,
        'redis_ok' => ($results['redis_insert_bulk']['success'] ?? false) &&
                     ($results['redis_read_one']['success'] ?? false) &&
                     ($results['redis_clear']['success'] ?? false)
    ];

    echo json_encode($summary) . "\n";

    // Save detailed results to file
    $timestamp = date('Y-m-d_H-i-s');
    $detailsFile = __DIR__ . "/augment/validation_results/{$timestamp}-laravel.json";
    file_put_contents($detailsFile, json_encode($results, JSON_PRETTY_PRINT));

    exit($summary['success'] ? 0 : 1);
}
