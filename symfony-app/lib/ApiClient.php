<?php

namespace App\Lib;

use Exception;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * API Client for Symfony with external HTTP requests
 * Demonstrates external API calls for APM testing using Symfony HTTP client
 */
class ApiClient
{
    private $baseUrl;
    private $timeout;
    private $headers;
    private $httpClient;
    private $logger;

    public function __construct(
        string $baseUrl = '', 
        int $timeout = 30, 
        HttpClientInterface $httpClient = null,
        LoggerInterface $logger = null
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
        $this->headers = [
            'Content-Type' => 'application/json',
            'User-Agent' => 'APM-PHP-Symfony-Examples/1.0'
        ];
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }

    /**
     * Make GET request using Symfony HTTP client
     */
    public function getSymfony(string $endpoint, array $params = []): array
    {
        $url = $this->buildUrl($endpoint, $params);
        return $this->makeRequestSymfony('GET', $url);
    }

    /**
     * Make POST request using Symfony HTTP client
     */
    public function postSymfony(string $endpoint, array $data = []): array
    {
        $url = $this->buildUrl($endpoint);
        return $this->makeRequestSymfony('POST', $url, $data);
    }

    /**
     * Make PUT request using Symfony HTTP client
     */
    public function putSymfony(string $endpoint, array $data = []): array
    {
        $url = $this->buildUrl($endpoint);
        return $this->makeRequestSymfony('PUT', $url, $data);
    }

    /**
     * Make DELETE request using Symfony HTTP client
     */
    public function deleteSymfony(string $endpoint): array
    {
        $url = $this->buildUrl($endpoint);
        return $this->makeRequestSymfony('DELETE', $url);
    }

    /**
     * Make GET request using cURL (for comparison)
     */
    public function get(string $endpoint, array $params = []): array
    {
        $url = $this->buildUrl($endpoint, $params);
        return $this->makeRequest('GET', $url);
    }

    /**
     * Make POST request using cURL (for comparison)
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
     * Make HTTP request using Symfony HTTP client
     */
    private function makeRequestSymfony(string $method, string $url, array $data = []): array
    {
        if (!$this->httpClient) {
            return [
                'success' => false,
                'error' => 'Symfony HTTP client not available',
                'client_type' => 'Symfony HTTP (unavailable)'
            ];
        }

        $startTime = microtime(true);
        
        try {
            $options = [
                'headers' => $this->headers,
                'timeout' => $this->timeout,
            ];

            if (in_array(strtoupper($method), ['POST', 'PUT']) && !empty($data)) {
                $options['json'] = $data;
            }

            $response = $this->httpClient->request($method, $url, $options);
            
            $duration = microtime(true) - $startTime;
            $statusCode = $response->getStatusCode();
            $content = $response->getContent(false); // false = don't throw on error status
            
            // Log the request
            if ($this->logger) {
                $this->logger->info('Symfony API Request', [
                    'method' => $method,
                    'url' => $url,
                    'status' => $statusCode,
                    'duration' => round($duration * 1000, 2)
                ]);
            }
            
            $decodedContent = json_decode($content, true);
            
            return [
                'success' => $statusCode >= 200 && $statusCode < 300,
                'status_code' => $statusCode,
                'data' => $decodedContent,
                'raw_response' => $content,
                'duration' => round($duration * 1000, 2),
                'url' => $url,
                'method' => $method,
                'client_type' => 'Symfony HTTP'
            ];
            
        } catch (Exception $e) {
            $duration = microtime(true) - $startTime;
            
            if ($this->logger) {
                $this->logger->error('Symfony API Request Failed', [
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
                'client_type' => 'Symfony HTTP'
            ];
        }
    }

    /**
     * Make HTTP request using cURL (for comparison)
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
     * Test external APIs for APM demonstration (Symfony style)
     */
    public function testExternalApis(): array
    {
        $results = [];
        
        // Test using Symfony HTTP client
        if ($this->httpClient) {
            $results['symfony_http'] = [
                'jsonplaceholder' => $this->testJsonPlaceholderSymfony(),
                'httpbin' => $this->testHttpBinSymfony()
            ];
        } else {
            $results['symfony_http'] = 'not_available';
        }
        
        // Test using cURL for comparison
        $results['curl'] = [
            'jsonplaceholder' => $this->testJsonPlaceholder(),
            'httpbin' => $this->testHttpBin()
        ];
        
        return $results;
    }

    /**
     * Test JSONPlaceholder API using Symfony HTTP client
     */
    private function testJsonPlaceholderSymfony(): array
    {
        try {
            $client = new self('https://jsonplaceholder.typicode.com', 30, $this->httpClient, $this->logger);
            
            // Get posts
            $posts = $client->getSymfony('posts', ['_limit' => 5]);
            
            // Get specific post
            $post = $client->getSymfony('posts/1');
            
            // Create new post
            $newPost = $client->postSymfony('posts', [
                'title' => 'APM Symfony Test Post',
                'body' => 'This is a test post from APM Symfony Examples',
                'userId' => 1
            ]);
            
            return [
                'service' => 'JSONPlaceholder (Symfony HTTP)',
                'tests' => [
                    'get_posts' => $posts,
                    'get_post' => $post,
                    'create_post' => $newPost
                ],
                'status' => 'completed'
            ];
            
        } catch (Exception $e) {
            return [
                'service' => 'JSONPlaceholder (Symfony HTTP)',
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Test HTTPBin API using Symfony HTTP client
     */
    private function testHttpBinSymfony(): array
    {
        try {
            $client = new self('https://httpbin.org', 30, $this->httpClient, $this->logger);
            
            // Test GET with parameters
            $getTest = $client->getSymfony('get', ['param1' => 'value1', 'param2' => 'value2']);
            
            // Test POST with data
            $postTest = $client->postSymfony('post', ['test' => 'data', 'timestamp' => time()]);
            
            // Test delay endpoint
            $delayTest = $client->getSymfony('delay/2');
            
            return [
                'service' => 'HTTPBin (Symfony HTTP)',
                'tests' => [
                    'get_test' => $getTest,
                    'post_test' => $postTest,
                    'delay_test' => $delayTest
                ],
                'status' => 'completed'
            ];
            
        } catch (Exception $e) {
            return [
                'service' => 'HTTPBin (Symfony HTTP)',
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Test JSONPlaceholder API using cURL
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
     * Test HTTPBin API using cURL
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
     * Demonstrate Symfony HTTP client features
     */
    public function demonstrateSymfonyFeatures(): array
    {
        if (!$this->httpClient) {
            return [
                'demonstration' => 'Symfony HTTP Client Features',
                'status' => 'not_available',
                'error' => 'Symfony HTTP client not injected'
            ];
        }

        try {
            // Test streaming
            $streamResponse = $this->httpClient->request('GET', 'https://httpbin.org/stream/3');
            $streamContent = '';
            foreach ($this->httpClient->stream($streamResponse) as $chunk) {
                $streamContent .= $chunk->getContent();
            }
            
            return [
                'demonstration' => 'Symfony HTTP Client Features',
                'features' => [
                    'streaming' => [
                        'status' => 'completed',
                        'content_length' => strlen($streamContent),
                        'description' => 'HTTP streaming support'
                    ],
                    'dependency_injection' => [
                        'status' => 'available',
                        'description' => 'HTTP client injected via DI'
                    ],
                    'logging_integration' => [
                        'status' => $this->logger ? 'available' : 'not_available',
                        'description' => 'PSR-3 logger integration'
                    ]
                ],
                'status' => 'completed'
            ];
            
        } catch (Exception $e) {
            return [
                'demonstration' => 'Symfony HTTP Client Features',
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }
}
