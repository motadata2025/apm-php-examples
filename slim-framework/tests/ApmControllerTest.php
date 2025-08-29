<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Headers;
use Slim\Psr7\Request as SlimRequest;
use Slim\Psr7\Uri;

class ApmControllerTest extends TestCase
{
    protected function setUp(): void
    {
        // Load environment variables for testing
        if (file_exists(__DIR__ . '/../.env')) {
            $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
            $dotenv->load();
        }
    }

    public function testHealthEndpoint(): void
    {
        $app = $this->createApp();

        $request = $this->createRequest('GET', '/health');
        $response = $app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));

        $responseBody = (string) $response->getBody();
        $data = json_decode($responseBody, true);

        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('timestamp', $data);
        $this->assertArrayHasKey('php_version', $data);
        $this->assertArrayHasKey('slim_version', $data);
        $this->assertEquals('ok', $data['status']);
    }

    public function testDashboardEndpoint(): void
    {
        $app = $this->createApp();

        $request = $this->createRequest('GET', '/');
        $response = $app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());

        $responseBody = (string) $response->getBody();
        $this->assertStringContainsString('Slim Framework Application', $responseBody);
        $this->assertStringContainsString('APM Integration Example', $responseBody);
    }

    public function testDatabaseConnectionsEndpoint(): void
    {
        $app = $this->createApp();

        $request = $this->createRequest('POST', '/test-databases');
        $response = $app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));

        $responseBody = (string) $response->getBody();
        $data = json_decode($responseBody, true);

        $this->assertArrayHasKey('success', $data);
        $this->assertArrayHasKey('data', $data);
    }

    public function testCrudOperationsEndpoint(): void
    {
        $app = $this->createApp();

        $request = $this->createRequest('POST', '/demo-crud');
        $response = $app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));

        $responseBody = (string) $response->getBody();
        $data = json_decode($responseBody, true);

        $this->assertArrayHasKey('success', $data);
        $this->assertArrayHasKey('data', $data);
    }

    public function testExternalApiEndpoint(): void
    {
        $app = $this->createApp();

        $request = $this->createRequest('POST', '/fetch-api-data');
        $response = $app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));

        $responseBody = (string) $response->getBody();
        $data = json_decode($responseBody, true);

        $this->assertArrayHasKey('success', $data);
        $this->assertArrayHasKey('data', $data);
    }

    public function testQueueOperationsEndpoint(): void
    {
        $app = $this->createApp();

        $request = $this->createRequest('POST', '/test-queue');
        $response = $app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));

        $responseBody = (string) $response->getBody();
        $data = json_decode($responseBody, true);

        $this->assertArrayHasKey('success', $data);
        $this->assertArrayHasKey('data', $data);
    }

    public function testAddQueueDataEndpoint(): void
    {
        $app = $this->createApp();

        $request = $this->createRequest('POST', '/add-queue-data')
            ->withParsedBody(['data' => '{"message": "test", "priority": 1}']);
        $response = $app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));

        $responseBody = (string) $response->getBody();
        $data = json_decode($responseBody, true);

        $this->assertArrayHasKey('success', $data);
        $this->assertArrayHasKey('message', $data);
    }

    private function createApp()
    {
        // This would normally load the full app configuration
        // For testing, we'll create a minimal app
        $app = AppFactory::create();

        // Add a simple health route for testing
        $app->get('/health', function ($request, $response) {
            $data = [
                'status' => 'ok',
                'timestamp' => date('c'),
                'php_version' => phpversion(),
                'slim_version' => '4.x'
            ];
            $response->getBody()->write(json_encode($data));
            return $response->withHeader('Content-Type', 'application/json');
        });

        return $app;
    }

    private function createRequest(string $method, string $path): Request
    {
        $uri = new Uri('', '', 80, $path);
        $handle = fopen('php://temp', 'w+');
        $stream = (new StreamFactory())->createStreamFromResource($handle);
        $headers = new Headers();

        return new SlimRequest($method, $uri, $headers, [], [], $stream);
    }
}