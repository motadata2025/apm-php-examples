<?php

namespace App\Lib;

use Exception;

/**
 * API Client for CodeIgniter with external HTTP requests
 * Demonstrates external API calls for APM testing with CodeIgniter integration
 */
class ApiClient
{
    private $baseUrl;
    private $timeout;
    private $headers;

    public function __construct(string $baseUrl = '', int $timeout = 30)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
        $this->headers = [
            'Content-Type' => 'application/json',
            'User-Agent' => 'APM-PHP-CodeIgniter-Examples/1.0'
        ];
    }

    /**
     * Make GET request using cURL with CodeIgniter logging
     */
    public function getCI(string $endpoint, array $params = []): array
    {
        $url = $this->buildUrl($endpoint, $params);
        return $this->makeRequestCI('GET', $url);
    }

    /**
     * Make POST request using cURL with CodeIgniter logging
     */
    public function postCI(string $endpoint, array $data = []): array
    {
        $url = $this->buildUrl($endpoint);
        return $this->makeRequestCI('POST', $url, $data);
    }

    /**
     * Make PUT request using cURL with CodeIgniter logging
     */
    public function putCI(string $endpoint, array $data = []): array
    {
        $url = $this->buildUrl($endpoint);
        return $this->makeRequestCI('PUT', $url, $data);
    }

    /**
     * Make DELETE request using cURL with CodeIgniter logging
     */
    public function deleteCI(string $endpoint): array
    {
        $url = $this->buildUrl($endpoint);
        return $this->makeRequestCI('DELETE', $url);
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
     * Make HTTP request using cURL with CodeIgniter integration
     */
    private function makeRequestCI(string $method, string $url, array $data = []): array
    {
        $startTime = microtime(true);
        
        try {
            // Get HTTP client settings from CodeIgniter config if available
            $httpConfig = config('HttpClient') ?? (object)[];
            
            $timeout = $httpConfig->timeout ?? $this->timeout;
            $headers = array_merge($this->headers, $httpConfig->headers ?? []);
            
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
                CURLOPT_SSL_VERIFYPEER => $httpConfig->verifySsl ?? false,
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
            
            // Log the request using CodeIgniter logging
            log_message('info', 'CodeIgniter API Request: ' . $method . ' ' . $url . ' - Status: ' . $httpCode . ' - Duration: ' . round($duration * 1000, 2) . 'ms');
            
            $decodedResponse = json_decode($response, true);
            
            return [
                'success' => $httpCode >= 200 && $httpCode < 300,
                'status_code' => $httpCode,
                'data' => $decodedResponse,
                'raw_response' => $response,
                'duration' => round($duration * 1000, 2),
                'url' => $url,
                'method' => $method,
                'client_type' => 'CodeIgniter cURL'
            ];
            
        } catch (Exception $e) {
            $duration = microtime(true) - $startTime;
            
            log_message('error', 'CodeIgniter API Request Failed: ' . $method . ' ' . $url . ' - Error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'duration' => round($duration * 1000, 2),
                'url' => $url,
                'method' => $method,
                'client_type' => 'CodeIgniter cURL'
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
     * Test external APIs for APM demonstration (CodeIgniter style)
     */
    public function testExternalApis(): array
    {
        $results = [];
        
        // Test using CodeIgniter integration
        $results['codeigniter_integration'] = [
            'jsonplaceholder' => $this->testJsonPlaceholderCI(),
            'httpbin' => $this->testHttpBinCI()
        ];
        
        // Test using standard cURL for comparison
        $results['standard_curl'] = [
            'jsonplaceholder' => $this->testJsonPlaceholder(),
            'httpbin' => $this->testHttpBin()
        ];
        
        return $results;
    }

    /**
     * Test JSONPlaceholder API using CodeIgniter integration
     */
    private function testJsonPlaceholderCI(): array
    {
        try {
            $client = new self('https://jsonplaceholder.typicode.com', 30);
            
            // Get posts
            $posts = $client->getCI('posts', ['_limit' => 5]);
            
            // Get specific post
            $post = $client->getCI('posts/1');
            
            // Create new post
            $newPost = $client->postCI('posts', [
                'title' => 'APM CodeIgniter Test Post',
                'body' => 'This is a test post from APM CodeIgniter Examples',
                'userId' => 1
            ]);
            
            return [
                'service' => 'JSONPlaceholder (CodeIgniter)',
                'tests' => [
                    'get_posts' => $posts,
                    'get_post' => $post,
                    'create_post' => $newPost
                ],
                'status' => 'completed'
            ];
            
        } catch (Exception $e) {
            return [
                'service' => 'JSONPlaceholder (CodeIgniter)',
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Test HTTPBin API using CodeIgniter integration
     */
    private function testHttpBinCI(): array
    {
        try {
            $client = new self('https://httpbin.org', 30);
            
            // Test GET with parameters
            $getTest = $client->getCI('get', ['param1' => 'value1', 'param2' => 'value2']);
            
            // Test POST with data
            $postTest = $client->postCI('post', ['test' => 'data', 'timestamp' => time()]);
            
            // Test delay endpoint
            $delayTest = $client->getCI('delay/2');
            
            return [
                'service' => 'HTTPBin (CodeIgniter)',
                'tests' => [
                    'get_test' => $getTest,
                    'post_test' => $postTest,
                    'delay_test' => $delayTest
                ],
                'status' => 'completed'
            ];
            
        } catch (Exception $e) {
            return [
                'service' => 'HTTPBin (CodeIgniter)',
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
     * Demonstrate CodeIgniter HTTP client features
     */
    public function demonstrateCIFeatures(): array
    {
        try {
            $results = [];
            
            // Test configuration integration
            $httpConfig = config('HttpClient');
            $results['config_available'] = $httpConfig !== null;
            
            // Test logging integration
            $results['logging_integration'] = function_exists('log_message');
            
            // Test basic HTTP functionality
            $httpTest = $this->getCI('https://httpbin.org/get', ['test' => 'codeigniter']);
            $results['http_test'] = $httpTest['success'] ? 'passed' : 'failed';
            
            return [
                'demonstration' => 'CodeIgniter HTTP Client Features',
                'features' => $results,
                'status' => 'completed'
            ];
            
        } catch (Exception $e) {
            return [
                'demonstration' => 'CodeIgniter HTTP Client Features',
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }
}
