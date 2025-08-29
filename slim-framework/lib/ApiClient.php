<?php

namespace App\Lib;

use Exception;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * API Client for Slim Framework with external HTTP requests
 * Demonstrates external API calls for APM testing with Slim integration
 */
class ApiClient
{
    private $baseUrl;
    private $timeout;
    private $headers;
    private $container;
    private $logger;

    public function __construct(
        string $baseUrl = '', 
        int $timeout = 30, 
        ContainerInterface $container = null
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
        $this->headers = [
            'Content-Type' => 'application/json',
            'User-Agent' => 'APM-PHP-Slim-Examples/1.0'
        ];
        $this->container = $container;
        
        // Get logger from container if available
        if ($this->container && $this->container->has('logger')) {
            $this->logger = $this->container->get('logger');
        }
    }

    /**
     * Make GET request using cURL with Slim container settings
     */
    public function getSlim(string $endpoint, array $params = []): array
    {
        $url = $this->buildUrl($endpoint, $params);
        return $this->makeRequestSlim('GET', $url);
    }

    /**
     * Make POST request using cURL with Slim container settings
     */
    public function postSlim(string $endpoint, array $data = []): array
    {
        $url = $this->buildUrl($endpoint);
        return $this->makeRequestSlim('POST', $url, $data);
    }

    /**
     * Make PUT request using cURL with Slim container settings
     */
    public function putSlim(string $endpoint, array $data = []): array
    {
        $url = $this->buildUrl($endpoint);
        return $this->makeRequestSlim('PUT', $url, $data);
    }

    /**
     * Make DELETE request using cURL with Slim container settings
     */
    public function deleteSlim(string $endpoint): array
    {
        $url = $this->buildUrl($endpoint);
        return $this->makeRequestSlim('DELETE', $url);
    }

    /**
     * Make GET request using cURL (standard)
     */
    public function get(string $endpoint, array $params = []): array
    {
        $url = $this->buildUrl($endpoint, $params);
        return $this->makeRequest('GET', $url);
    }

    /**
     * Make POST request using cURL (standard)
     */
    public function post(string $endpoint, array $data = []): array
    {
        $url = $this->buildUrl($endpoint);
        return $this->makeRequest('POST', $url, $data);
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
     * Make HTTP request using cURL with Slim container integration
     */
    private function makeRequestSlim(string $method, string $url, array $data = []): array
    {
        $startTime = microtime(true);
        
        try {
            // Get HTTP client settings from Slim container if available
            $httpSettings = [];
            if ($this->container && $this->container->has('settings')) {
                $settings = $this->container->get('settings');
                $httpSettings = $settings['http_client'] ?? [];
            }
            
            $timeout = $httpSettings['timeout'] ?? $this->timeout;
            $headers = array_merge($this->headers, $httpSettings['headers'] ?? []);
            
            $ch = curl_init();
            
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_HTTPHEADER => array_map(
                    fn($key, $value) => "$key: $value",
                    array_keys($headers),
                    $headers
                ),
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3,
                CURLOPT_SSL_VERIFYPEER => $httpSettings['verify_ssl'] ?? false,
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
            
            // Log the request using Slim logger
            if ($this->logger) {
                $this->logger->info('Slim API Request', [
                    'method' => $method,
                    'url' => $url,
                    'status' => $httpCode,
                    'duration' => round($duration * 1000, 2)
                ]);
            }
            
            $decodedResponse = json_decode($response, true);
            
            return [
                'success' => $httpCode >= 200 && $httpCode < 300,
                'status_code' => $httpCode,
                'data' => $decodedResponse,
                'raw_response' => $response,
                'duration' => round($duration * 1000, 2),
                'url' => $url,
                'method' => $method,
                'client_type' => 'Slim cURL'
            ];
            
        } catch (Exception $e) {
            $duration = microtime(true) - $startTime;
            
            if ($this->logger) {
                $this->logger->error('Slim API Request Failed', [
                    'method' => $method,
                    'url' => $url,
                    'error' => $e->getMessage(),
                    'duration' => round($duration * 1000, 2)
                ]);
            }
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'duration' => round($duration * 1000, 2),
                'url' => $url,
                'method' => $method,
                'client_type' => 'Slim cURL'
            ];
        }
    }

    /**
     * Make HTTP request using cURL (standard implementation)
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
                CURLOPT_HTTPHEADER => array_map(
                    fn($key, $value) => "$key: $value",
                    array_keys($this->headers),
                    $this->headers
                ),
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3,
                CURLOPT_SSL_VERIFYPEER => false,
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
                'duration' => round($duration * 1000, 2),
                'url' => $url,
                'method' => $method,
                'client_type' => 'cURL'
            ];
            
        } catch (Exception $e) {
            $duration = microtime(true) - $startTime;
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'duration' => round($duration * 1000, 2),
                'url' => $url,
                'method' => $method,
                'client_type' => 'cURL'
            ];
        }
    }

    /**
     * Test external APIs for APM demonstration (Slim style)
     */
    public function testExternalApis(): array
    {
        $results = [];
        
        // Test using Slim integration
        $results['slim_integration'] = [
            'jsonplaceholder' => $this->testJsonPlaceholderSlim(),
            'httpbin' => $this->testHttpBinSlim()
        ];
        
        // Test using standard cURL for comparison
        $results['standard_curl'] = [
            'jsonplaceholder' => $this->testJsonPlaceholder(),
            'httpbin' => $this->testHttpBin()
        ];
        
        return $results;
    }

    /**
     * Test JSONPlaceholder API using Slim integration
     */
    private function testJsonPlaceholderSlim(): array
    {
        try {
            $client = new self('https://jsonplaceholder.typicode.com', 30, $this->container);
            
            // Get posts
            $posts = $client->getSlim('posts', ['_limit' => 5]);
            
            // Get specific post
            $post = $client->getSlim('posts/1');
            
            // Create new post
            $newPost = $client->postSlim('posts', [
                'title' => 'APM Slim Test Post',
                'body' => 'This is a test post from APM Slim Examples',
                'userId' => 1
            ]);
            
            return [
                'service' => 'JSONPlaceholder (Slim)',
                'tests' => [
                    'get_posts' => $posts,
                    'get_post' => $post,
                    'create_post' => $newPost
                ],
                'status' => 'completed'
            ];
            
        } catch (Exception $e) {
            return [
                'service' => 'JSONPlaceholder (Slim)',
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Test HTTPBin API using Slim integration
     */
    private function testHttpBinSlim(): array
    {
        try {
            $client = new self('https://httpbin.org', 30, $this->container);
            
            // Test GET with parameters
            $getTest = $client->getSlim('get', ['param1' => 'value1', 'param2' => 'value2']);
            
            // Test POST with data
            $postTest = $client->postSlim('post', ['test' => 'data', 'timestamp' => time()]);
            
            // Test delay endpoint
            $delayTest = $client->getSlim('delay/2');
            
            return [
                'service' => 'HTTPBin (Slim)',
                'tests' => [
                    'get_test' => $getTest,
                    'post_test' => $postTest,
                    'delay_test' => $delayTest
                ],
                'status' => 'completed'
            ];
            
        } catch (Exception $e) {
            return [
                'service' => 'HTTPBin (Slim)',
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Test JSONPlaceholder API using standard cURL
     */
    private function testJsonPlaceholder(): array
    {
        try {
            $client = new self('https://jsonplaceholder.typicode.com');
            
            $posts = $client->get('posts', ['_limit' => 3]);
            $post = $client->get('posts/1');
            
            return [
                'service' => 'JSONPlaceholder (cURL)',
                'tests' => [
                    'get_posts' => $posts,
                    'get_post' => $post
                ],
                'status' => 'completed'
            ];
            
        } catch (Exception $e) {
            return [
                'service' => 'JSONPlaceholder (cURL)',
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Test HTTPBin API using standard cURL
     */
    private function testHttpBin(): array
    {
        try {
            $client = new self('https://httpbin.org');
            
            $getTest = $client->get('get', ['param1' => 'value1']);
            $postTest = $client->post('post', ['test' => 'data']);
            
            return [
                'service' => 'HTTPBin (cURL)',
                'tests' => [
                    'get_test' => $getTest,
                    'post_test' => $postTest
                ],
                'status' => 'completed'
            ];
            
        } catch (Exception $e) {
            return [
                'service' => 'HTTPBin (cURL)',
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Demonstrate Slim Framework HTTP client features
     */
    public function demonstrateSlimFeatures(): array
    {
        try {
            $results = [];
            
            // Test container integration
            $results['container_available'] = $this->container !== null;
            $results['logger_available'] = $this->logger !== null;
            
            // Test settings integration
            if ($this->container && $this->container->has('settings')) {
                $settings = $this->container->get('settings');
                $results['settings_available'] = true;
                $results['http_client_settings'] = isset($settings['http_client']);
            } else {
                $results['settings_available'] = false;
            }
            
            // Test basic HTTP functionality
            $httpTest = $this->getSlim('https://httpbin.org/get', ['test' => 'slim']);
            $results['http_test'] = $httpTest['success'] ? 'passed' : 'failed';
            
            return [
                'demonstration' => 'Slim Framework HTTP Client Features',
                'features' => $results,
                'status' => 'completed'
            ];
            
        } catch (Exception $e) {
            return [
                'demonstration' => 'Slim Framework HTTP Client Features',
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }
}
