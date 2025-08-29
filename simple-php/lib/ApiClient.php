<?php

namespace SimplePhp\Lib;

use Exception;

/**
 * API Client for external HTTP requests
 * Demonstrates external API calls for APM testing
 */
class ApiClient
{
    private $baseUrl;
    private $timeout;
    private $headers;

    public function __construct(string $baseUrl = '', int $timeout = 10)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
        $this->headers = [
            'Content-Type: application/json',
            'User-Agent: APM-PHP-Examples/1.0'
        ];
    }

    /**
     * Make GET request
     */
    public function get(string $endpoint, array $params = []): array
    {
        $url = $this->buildUrl($endpoint, $params);
        return $this->makeRequest('GET', $url);
    }

    /**
     * Make POST request
     */
    public function post(string $endpoint, array $data = []): array
    {
        $url = $this->buildUrl($endpoint);
        return $this->makeRequest('POST', $url, $data);
    }

    /**
     * Make PUT request
     */
    public function put(string $endpoint, array $data = []): array
    {
        $url = $this->buildUrl($endpoint);
        return $this->makeRequest('PUT', $url, $data);
    }

    /**
     * Make DELETE request
     */
    public function delete(string $endpoint): array
    {
        $url = $this->buildUrl($endpoint);
        return $this->makeRequest('DELETE', $url);
    }

    /**
     * Build full URL
     */
    private function buildUrl(string $endpoint, array $params = []): string
    {
        $url = $this->baseUrl ? $this->baseUrl . '/' . ltrim($endpoint, '/') : $endpoint;
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        return $url;
    }

    /**
     * Make HTTP request using cURL
     */
    private function makeRequest(string $method, string $url, array $data = []): array
    {
        $startTime = microtime(true);
        
        try {
            $ch = curl_init();
            
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $this->timeout,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_HTTPHEADER => $this->headers,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3,
                CURLOPT_SSL_VERIFYPEER => false, // For testing only
                CURLOPT_CUSTOMREQUEST => $method
            ]);
            
            if (in_array($method, ['POST', 'PUT']) && !empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            
            curl_close($ch);
            
            $duration = microtime(true) - $startTime;
            
            if ($error) {
                throw new Exception("cURL error: " . $error);
            }
            
            $decodedResponse = json_decode($response, true);
            
            return [
                'success' => $httpCode >= 200 && $httpCode < 300,
                'status_code' => $httpCode,
                'data' => $decodedResponse,
                'raw_response' => $response,
                'duration' => round($duration * 1000, 2), // milliseconds
                'url' => $url,
                'method' => $method
            ];
            
        } catch (Exception $e) {
            $duration = microtime(true) - $startTime;
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'duration' => round($duration * 1000, 2),
                'url' => $url,
                'method' => $method
            ];
        }
    }

    /**
     * Test external APIs for APM demonstration
     */
    public function testExternalApis(): array
    {
        $results = [];
        
        // Test JSONPlaceholder API
        $results['jsonplaceholder'] = $this->testJsonPlaceholder();
        
        // Test HTTPBin API
        $results['httpbin'] = $this->testHttpBin();
        
        // Test fake slow API
        $results['slow_api'] = $this->testSlowApi();
        
        return $results;
    }

    /**
     * Test JSONPlaceholder API
     */
    private function testJsonPlaceholder(): array
    {
        try {
            $client = new self('https://jsonplaceholder.typicode.com');
            
            // Get posts
            $posts = $client->get('posts', ['_limit' => 5]);
            
            // Get specific post
            $post = $client->get('posts/1');
            
            // Create new post
            $newPost = $client->post('posts', [
                'title' => 'APM Test Post',
                'body' => 'This is a test post from APM PHP Examples',
                'userId' => 1
            ]);
            
            return [
                'service' => 'JSONPlaceholder',
                'tests' => [
                    'get_posts' => $posts,
                    'get_post' => $post,
                    'create_post' => $newPost
                ],
                'status' => 'completed'
            ];
            
        } catch (Exception $e) {
            return [
                'service' => 'JSONPlaceholder',
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Test HTTPBin API
     */
    private function testHttpBin(): array
    {
        try {
            $client = new self('https://httpbin.org');
            
            // Test GET with parameters
            $getTest = $client->get('get', ['param1' => 'value1', 'param2' => 'value2']);
            
            // Test POST with data
            $postTest = $client->post('post', ['test' => 'data', 'timestamp' => time()]);
            
            // Test delay endpoint
            $delayTest = $client->get('delay/2');
            
            return [
                'service' => 'HTTPBin',
                'tests' => [
                    'get_test' => $getTest,
                    'post_test' => $postTest,
                    'delay_test' => $delayTest
                ],
                'status' => 'completed'
            ];
            
        } catch (Exception $e) {
            return [
                'service' => 'HTTPBin',
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Test slow API simulation
     */
    private function testSlowApi(): array
    {
        try {
            // Simulate slow API call
            $startTime = microtime(true);
            sleep(1); // Simulate 1 second delay
            $duration = microtime(true) - $startTime;
            
            return [
                'service' => 'Slow API Simulation',
                'duration' => round($duration * 1000, 2),
                'status' => 'completed',
                'message' => 'Simulated slow API response'
            ];
            
        } catch (Exception $e) {
            return [
                'service' => 'Slow API Simulation',
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Add custom header
     */
    public function addHeader(string $header): void
    {
        $this->headers[] = $header;
    }

    /**
     * Set timeout
     */
    public function setTimeout(int $timeout): void
    {
        $this->timeout = $timeout;
    }

    /**
     * Test multiple APIs (alias for testExternalApis)
     */
    public function testMultipleApis(): array
    {
        return $this->testExternalApis();
    }
}
