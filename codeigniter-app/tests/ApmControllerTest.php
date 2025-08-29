<?php

namespace Tests;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ControllerTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;

class ApmControllerTest extends CIUnitTestCase
{
    use ControllerTestTrait;
    use DatabaseTestTrait;

    public function testIndexReturnsSuccessfully()
    {
        $result = $this->controller(\App\Controllers\ApmController::class)
                       ->execute('index');

        $this->assertTrue($result->isOK());
        $this->assertStringContainsString('CodeIgniter Application', $result->getBody());
        $this->assertStringContainsString('APM Integration Example', $result->getBody());
    }

    public function testHealthEndpoint()
    {
        $result = $this->controller(\App\Controllers\ApmController::class)
                       ->execute('health');

        $this->assertTrue($result->isOK());
        $this->assertEquals('application/json', $result->getHeaderLine('Content-Type'));

        $responseBody = $result->getJSON();
        $this->assertArrayHasKey('status', $responseBody);
        $this->assertArrayHasKey('timestamp', $responseBody);
        $this->assertArrayHasKey('php_version', $responseBody);
        $this->assertArrayHasKey('codeigniter_version', $responseBody);
        $this->assertEquals('ok', $responseBody['status']);
    }

    public function testDatabaseConnectionsEndpoint()
    {
        $result = $this->controller(\App\Controllers\ApmController::class)
                       ->execute('testDatabases');

        $this->assertTrue($result->isOK());
        $this->assertEquals('application/json', $result->getHeaderLine('Content-Type'));

        $responseBody = $result->getJSON();
        $this->assertArrayHasKey('success', $responseBody);
        $this->assertArrayHasKey('data', $responseBody);
    }

    public function testCrudOperationsEndpoint()
    {
        $result = $this->controller(\App\Controllers\ApmController::class)
                       ->execute('demoCrud');

        $this->assertTrue($result->isOK());
        $this->assertEquals('application/json', $result->getHeaderLine('Content-Type'));

        $responseBody = $result->getJSON();
        $this->assertArrayHasKey('success', $responseBody);
        $this->assertArrayHasKey('data', $responseBody);
    }

    public function testExternalApiEndpoint()
    {
        $result = $this->controller(\App\Controllers\ApmController::class)
                       ->execute('fetchApiData');

        $this->assertTrue($result->isOK());
        $this->assertEquals('application/json', $result->getHeaderLine('Content-Type'));

        $responseBody = $result->getJSON();
        $this->assertArrayHasKey('success', $responseBody);
        $this->assertArrayHasKey('data', $responseBody);
    }

    public function testQueueOperationsEndpoint()
    {
        $result = $this->controller(\App\Controllers\ApmController::class)
                       ->execute('testQueue');

        $this->assertTrue($result->isOK());
        $this->assertEquals('application/json', $result->getHeaderLine('Content-Type'));

        $responseBody = $result->getJSON();
        $this->assertArrayHasKey('success', $responseBody);
        $this->assertArrayHasKey('data', $responseBody);
    }

    public function testAddQueueDataEndpoint()
    {
        $_POST['data'] = '{"message": "test", "priority": 1}';

        $result = $this->controller(\App\Controllers\ApmController::class)
                       ->execute('addQueueData');

        $this->assertTrue($result->isOK());
        $this->assertEquals('application/json', $result->getHeaderLine('Content-Type'));

        $responseBody = $result->getJSON();
        $this->assertArrayHasKey('success', $responseBody);
        $this->assertArrayHasKey('message', $responseBody);
    }

    public function testRouteConfiguration()
    {
        // Test that routes are properly configured
        $routes = service('routes');
        $this->assertNotEmpty($routes->getRoutes());

        // Test specific routes exist
        $this->assertTrue($routes->reverseRoute('ApmController::index') !== false);
        $this->assertTrue($routes->reverseRoute('ApmController::health') !== false);
    }

    public function testEnvironmentConfiguration()
    {
        // Test that environment is properly configured
        $this->assertNotEmpty(ENVIRONMENT);
        $this->assertTrue(in_array(ENVIRONMENT, ['development', 'testing', 'production']));
    }

    public function testCodeIgniterVersion()
    {
        // Test that CodeIgniter version is available
        $this->assertNotEmpty(\CodeIgniter\CodeIgniter::CI_VERSION);
        $this->assertStringStartsWith('4.', \CodeIgniter\CodeIgniter::CI_VERSION);
    }
}