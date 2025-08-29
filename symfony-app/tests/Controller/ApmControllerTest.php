<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApmControllerTest extends WebTestCase
{
    public function testDashboardLoadsSuccessfully(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Symfony Application');
        $this->assertSelectorTextContains('p', 'APM Integration Example');
    }

    public function testDatabaseConnectionsEndpoint(): void
    {
        $client = static::createClient();
        $client->request('POST', '/test-databases');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('success', $responseData);
        $this->assertArrayHasKey('data', $responseData);
    }

    public function testCrudOperationsEndpoint(): void
    {
        $client = static::createClient();
        $client->request('POST', '/demo-crud');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('success', $responseData);
        $this->assertArrayHasKey('data', $responseData);
    }

    public function testExternalApiEndpoint(): void
    {
        $client = static::createClient();
        $client->request('POST', '/fetch-api-data');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('success', $responseData);
        $this->assertArrayHasKey('data', $responseData);
    }

    public function testQueueOperationsEndpoint(): void
    {
        $client = static::createClient();
        $client->request('POST', '/test-queue');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('success', $responseData);
        $this->assertArrayHasKey('data', $responseData);
    }

    public function testAddQueueDataEndpoint(): void
    {
        $client = static::createClient();
        $client->request('POST', '/add-queue-data', [
            'data' => '{"message": "test", "priority": 1}'
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('success', $responseData);
        $this->assertArrayHasKey('message', $responseData);
    }

    public function testHealthCheckEndpoint(): void
    {
        $client = static::createClient();
        $client->request('GET', '/health');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('status', $responseData);
        $this->assertArrayHasKey('timestamp', $responseData);
        $this->assertArrayHasKey('php_version', $responseData);
        $this->assertArrayHasKey('symfony_version', $responseData);
        $this->assertEquals('ok', $responseData['status']);
    }

    public function testAllRoutesAreRegistered(): void
    {
        $client = static::createClient();

        $routes = [
            ['GET', '/'],
            ['POST', '/test-databases'],
            ['POST', '/demo-crud'],
            ['POST', '/fetch-api-data'],
            ['POST', '/test-queue'],
            ['POST', '/add-queue-data'],
            ['POST', '/read-queue-data'],
            ['POST', '/clear-queue'],
            ['GET', '/health']
        ];

        foreach ($routes as [$method, $route]) {
            $client->request($method, $route);

            // Should not return 404
            $this->assertNotEquals(404, $client->getResponse()->getStatusCode(),
                "Route {$method} {$route} returned 404");
        }
    }
}