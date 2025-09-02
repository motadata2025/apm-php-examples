<?php

declare(strict_types=1);

namespace Shared\TestFramework;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;

/**
 * HTTP testing utilities for comprehensive API and web testing
 * Provides advanced HTTP testing capabilities for all applications
 */
trait HttpTestTrait
{
    protected static ?Client $httpClient = null;
    protected array $httpHistory = [];
    
    /**
     * Get HTTP client for testing
     */
    protected function getHttpClient(): Client
    {
        if (self::$httpClient === null) {
            self::$httpClient = new Client([
                'timeout' => 30,
                'connect_timeout' => 10,
                'verify' => false,
                'http_errors' => false,
            ]);
        }
        
        return self::$httpClient;
    }
    
    /**
     * Make HTTP GET request
     */
    protected function httpGet(string $url, array $headers = []): array
    {
        return $this->makeHttpRequest('GET', $url, [], $headers);
    }
    
    /**
     * Make HTTP POST request
     */
    protected function httpPost(string $url, array $data = [], array $headers = []): array
    {
        return $this->makeHttpRequest('POST', $url, $data, $headers);
    }
    
    /**
     * Make HTTP PUT request
     */
    protected function httpPut(string $url, array $data = [], array $headers = []): array
    {
        return $this->makeHttpRequest('PUT', $url, $data, $headers);
    }
    
    /**
     * Make HTTP DELETE request
     */
    protected function httpDelete(string $url, array $headers = []): array
    {
        return $this->makeHttpRequest('DELETE', $url, [], $headers);
    }
    
    /**
     * Make HTTP request with timing
     */
    protected function makeHttpRequest(string $method, string $url, array $data = [], array $headers = []): array
    {
        $client = $this->getHttpClient();
        $startTime = microtime(true);
        
        $options = [
            'headers' => array_merge([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ], $headers),
        ];
        
        if (!empty($data)) {
            $options['json'] = $data;
        }
        
        try {
            $response = $client->request($method, $url, $options);
            $endTime = microtime(true);
            
            $result = [
                'status_code' => $response->getStatusCode(),
                'headers' => $response->getHeaders(),
                'body' => (string) $response->getBody(),
                'response_time' => $endTime - $startTime,
                'method' => $method,
                'url' => $url,
                'request_data' => $data,
            ];
            
            // Try to decode JSON response
            $jsonBody = json_decode($result['body'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $result['json'] = $jsonBody;
            }
            
            $this->httpHistory[] = $result;
            
            return $result;
            
        } catch (RequestException $e) {
            $endTime = microtime(true);
            
            $result = [
                'status_code' => $e->getResponse() ? $e->getResponse()->getStatusCode() : 0,
                'headers' => $e->getResponse() ? $e->getResponse()->getHeaders() : [],
                'body' => $e->getResponse() ? (string) $e->getResponse()->getBody() : '',
                'response_time' => $endTime - $startTime,
                'method' => $method,
                'url' => $url,
                'request_data' => $data,
                'error' => $e->getMessage(),
            ];
            
            $this->httpHistory[] = $result;
            
            return $result;
        }
    }
    
    /**
     * Assert HTTP status code
     */
    protected function assertHttpStatus(int $expectedStatus, array $response): void
    {
        $this->assertEquals(
            $expectedStatus,
            $response['status_code'],
            "Expected HTTP status {$expectedStatus}, got {$response['status_code']}. Response: " . $response['body']
        );
    }
    
    /**
     * Assert HTTP success (2xx status codes)
     */
    protected function assertHttpSuccess(array $response): void
    {
        $this->assertGreaterThanOrEqual(200, $response['status_code']);
        $this->assertLessThan(300, $response['status_code']);
    }
    
    /**
     * Assert HTTP client error (4xx status codes)
     */
    protected function assertHttpClientError(array $response): void
    {
        $this->assertGreaterThanOrEqual(400, $response['status_code']);
        $this->assertLessThan(500, $response['status_code']);
    }
    
    /**
     * Assert HTTP server error (5xx status codes)
     */
    protected function assertHttpServerError(array $response): void
    {
        $this->assertGreaterThanOrEqual(500, $response['status_code']);
        $this->assertLessThan(600, $response['status_code']);
    }
    
    /**
     * Assert response contains JSON
     */
    protected function assertResponseIsJson(array $response): void
    {
        $this->assertArrayHasKey('json', $response, 'Response is not valid JSON');
        $this->assertIsArray($response['json'], 'Response JSON is not an array');
    }
    
    /**
     * Assert JSON response structure
     */
    protected function assertJsonStructure(array $expectedStructure, array $response): void
    {
        $this->assertResponseIsJson($response);
        $this->assertJsonStructureRecursive($expectedStructure, $response['json']);
    }
    
    /**
     * Recursively assert JSON structure
     */
    private function assertJsonStructureRecursive(array $expectedStructure, array $actualData, string $path = ''): void
    {
        foreach ($expectedStructure as $key => $value) {
            $currentPath = $path ? "{$path}.{$key}" : $key;
            
            if (is_int($key)) {
                // Numeric key means we're checking for the existence of a field
                $this->assertArrayHasKey($value, $actualData, "Missing key '{$value}' at path '{$path}'");
            } elseif (is_array($value)) {
                // Nested structure
                $this->assertArrayHasKey($key, $actualData, "Missing key '{$key}' at path '{$path}'");
                $this->assertJsonStructureRecursive($value, $actualData[$key], $currentPath);
            } else {
                // Simple field check
                $this->assertArrayHasKey($key, $actualData, "Missing key '{$key}' at path '{$path}'");
            }
        }
    }
    
    /**
     * Assert response time is acceptable
     */
    protected function assertResponseTimeAcceptable(array $response, float $maxTime = 1.0): void
    {
        $this->assertLessThanOrEqual(
            $maxTime,
            $response['response_time'],
            "Response time {$response['response_time']}s exceeds maximum {$maxTime}s for {$response['method']} {$response['url']}"
        );
    }
    
    /**
     * Assert response contains header
     */
    protected function assertResponseHasHeader(string $headerName, array $response): void
    {
        $headers = array_change_key_case($response['headers'], CASE_LOWER);
        $headerName = strtolower($headerName);
        
        $this->assertArrayHasKey($headerName, $headers, "Response missing header '{$headerName}'");
    }
    
    /**
     * Assert response header value
     */
    protected function assertResponseHeaderEquals(string $headerName, string $expectedValue, array $response): void
    {
        $this->assertResponseHasHeader($headerName, $response);
        
        $headers = array_change_key_case($response['headers'], CASE_LOWER);
        $headerName = strtolower($headerName);
        $actualValue = is_array($headers[$headerName]) ? $headers[$headerName][0] : $headers[$headerName];
        
        $this->assertEquals($expectedValue, $actualValue, "Header '{$headerName}' value mismatch");
    }
    
    /**
     * Test application health endpoint
     */
    protected function testHealthEndpoint(string $baseUrl): void
    {
        $response = $this->httpGet("{$baseUrl}/health");
        
        $this->assertHttpSuccess($response);
        $this->assertResponseTimeAcceptable($response, 0.5); // Health checks should be fast
        $this->assertResponseIsJson($response);
        
        // Common health check structure
        $this->assertJsonStructure([
            'status',
            'timestamp',
            'services' => [
                'database',
                'redis',
            ],
        ], $response);
        
        $this->assertEquals('ok', $response['json']['status']);
    }
    
    /**
     * Test application endpoints for basic functionality
     */
    protected function testBasicEndpoints(string $baseUrl): void
    {
        // Test home page
        $response = $this->httpGet($baseUrl);
        $this->assertHttpSuccess($response);
        $this->assertResponseTimeAcceptable($response);
        
        // Test API endpoints if they exist
        $apiResponse = $this->httpGet("{$baseUrl}/api");
        if ($apiResponse['status_code'] !== 404) {
            $this->assertHttpSuccess($apiResponse);
            $this->assertResponseIsJson($apiResponse);
        }
    }
    
    /**
     * Test CRUD operations
     */
    protected function testCrudOperations(string $baseUrl, string $resource): void
    {
        // Create
        $createData = ['name' => 'Test Item', 'description' => 'Test Description'];
        $createResponse = $this->httpPost("{$baseUrl}/api/{$resource}", $createData);
        $this->assertHttpStatus(201, $createResponse);
        $this->assertResponseIsJson($createResponse);
        
        $itemId = $createResponse['json']['id'] ?? null;
        $this->assertNotNull($itemId, 'Created item should have an ID');
        
        // Read
        $readResponse = $this->httpGet("{$baseUrl}/api/{$resource}/{$itemId}");
        $this->assertHttpSuccess($readResponse);
        $this->assertResponseIsJson($readResponse);
        
        // Update
        $updateData = ['name' => 'Updated Test Item'];
        $updateResponse = $this->httpPut("{$baseUrl}/api/{$resource}/{$itemId}", $updateData);
        $this->assertHttpSuccess($updateResponse);
        
        // Delete
        $deleteResponse = $this->httpDelete("{$baseUrl}/api/{$resource}/{$itemId}");
        $this->assertHttpSuccess($deleteResponse);
        
        // Verify deletion
        $verifyResponse = $this->httpGet("{$baseUrl}/api/{$resource}/{$itemId}");
        $this->assertHttpStatus(404, $verifyResponse);
    }
    
    /**
     * Get HTTP request history
     */
    protected function getHttpHistory(): array
    {
        return $this->httpHistory;
    }
    
    /**
     * Clear HTTP request history
     */
    protected function clearHttpHistory(): void
    {
        $this->httpHistory = [];
    }
    
    /**
     * Get last HTTP response
     */
    protected function getLastHttpResponse(): ?array
    {
        return end($this->httpHistory) ?: null;
    }
}
