<?php

namespace App\Lib;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * API Client for Laravel with external HTTP requests
 * Demonstrates external API calls for APM testing using Laravel HTTP client
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
            'User-Agent' => 'APM-PHP-Laravel-Examples/1.0'
        ];
    }

    /**
     * Make GET request using Laravel HTTP client
     */
    public function getLaravel(string $endpoint, array $params = []): array
    {
        $url = $this->buildUrl($endpoint, $params);
        return $this->makeRequestLaravel('GET', $url);
    }

    /**
     * Make POST request using Laravel HTTP client
     */
    public function postLaravel(string $endpoint, array $data = []): array
    {
        $url = $this->buildUrl($endpoint);
        return $this->makeRequestLaravel('POST', $url, $data);
    }

    /**
     * Make PUT request using Laravel HTTP client
     */
    public function putLaravel(string $endpoint, array $data = []): array
    {
        $url = $this->buildUrl($endpoint);
        return $this->makeRequestLaravel('PUT', $url, $data);
    }

    /**
     * Make DELETE request using Laravel HTTP client
     */
    public function deleteLaravel(string $endpoint): array
    {
        $url = $this->buildUrl($endpoint);
        return $this->makeRequestLaravel('DELETE', $url);
    }

    /**
     * Make GET request using cURL
     */
    public function get(string $endpoint, array $params = []): array
    {
        $url = $this->buildUrl($endpoint, $params);
        return $this->makeRequest('GET', $url);
    }

    /**
     * Make POST request using cURL
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
     * Make HTTP request using Laravel HTTP client
     */
    private function makeRequestLaravel(string $method, string $url, array $data = []): array
    {
        $startTime = microtime(true);
        
        try {
            $request = Http::withHeaders($this->headers)
                ->timeout($this->timeout);
            
            $response = match(strtoupper($method)) {
                'GET' => $request->get($url),
                'POST' => $request->post($url, $data),
                'PUT' => $request->put($url, $data),
                'DELETE' => $request->delete($url),
                default => throw new Exception("Unsupported HTTP method: $method")
            };
            
            $duration = microtime(true) - $startTime;
            
            // Log the request
            Log::info('API Request', [
                'method' => $method,
                'url' => $url,
                'status' => $response->status(),
                'duration' => round($duration * 1000, 2)
            ]);
            
            return [
                'success' => $response->successful(),
                'status_code' => $response->status(),
                'data' => $response->json(),
                'raw_response' => $response->body(),
                'duration' => round($duration * 1000, 2),
                'url' => $url,
                'method' => $method,
                'client_type' => 'Laravel HTTP'
            ];
            
        } catch (Exception $e) {
            $duration = microtime(true) - $startTime;
            
            Log::error('API Request Failed', [
                'method' => $method,
                'url' => $url,
                'error' => $e->getMessage(),
                'duration' => round($duration * 1000, 2)
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'duration' => round($duration * 1000, 2),
                'url' => $url,
                'method' => $method,
                'client_type' => 'Laravel HTTP'
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
     * Test external APIs for APM demonstration (Laravel style)
     */
    public function testExternalApis(): array
    {
        $results = [];
        
        // Test using Laravel HTTP client
        $results['laravel_http'] = [
            'jsonplaceholder' => $this->testJsonPlaceholderLaravel(),
            'httpbin' => $this->testHttpBinLaravel()
        ];
        
        // Test using cURL for comparison
        $results['curl'] = [
            'jsonplaceholder' => $this->testJsonPlaceholder(),
            'httpbin' => $this->testHttpBin()
        ];
        
        return $results;
    }

    /**
     * Test JSONPlaceholder API using Laravel HTTP client
     */
    private function testJsonPlaceholderLaravel(): array
    {
        try {
            $client = new self('https://jsonplaceholder.typicode.com');
            
            // Get posts
            $posts = $client->getLaravel('posts', ['_limit' => 5]);
            
            // Get specific post
            $post = $client->getLaravel('posts/1');
            
            // Create new post
            $newPost = $client->postLaravel('posts', [
                'title' => 'APM Laravel Test Post',
                'body' => 'This is a test post from APM Laravel Examples',
                'userId' => 1
            ]);
            
            return [
                'service' => 'JSONPlaceholder (Laravel HTTP)',
                'tests' => [
                    'get_posts' => $posts,
                    'get_post' => $post,
                    'create_post' => $newPost
                ],
                'status' => 'completed'
            ];
            
        } catch (Exception $e) {
            return [
                'service' => 'JSONPlaceholder (Laravel HTTP)',
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Test HTTPBin API using Laravel HTTP client
     */
    private function testHttpBinLaravel(): array
    {
        try {
            $client = new self('https://httpbin.org');
            
            // Test GET with parameters
            $getTest = $client->getLaravel('get', ['param1' => 'value1', 'param2' => 'value2']);
            
            // Test POST with data
            $postTest = $client->postLaravel('post', ['test' => 'data', 'timestamp' => time()]);
            
            // Test delay endpoint
            $delayTest = $client->getLaravel('delay/2');
            
            return [
                'service' => 'HTTPBin (Laravel HTTP)',
                'tests' => [
                    'get_test' => $getTest,
                    'post_test' => $postTest,
                    'delay_test' => $delayTest
                ],
                'status' => 'completed'
            ];
            
        } catch (Exception $e) {
            return [
                'service' => 'HTTPBin (Laravel HTTP)',
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
     * Demonstrate Laravel HTTP client features
     */
    public function demonstrateLaravelFeatures(): array
    {
        try {
            // Test with retry
            $retryTest = Http::retry(3, 100)->get('https://httpbin.org/status/500');
            
            // Test with timeout
            $timeoutTest = Http::timeout(5)->get('https://httpbin.org/delay/3');
            
            // Test with fake responses (for testing)
            Http::fake([
                'example.com/*' => Http::response(['fake' => 'response'], 200)
            ]);
            
            $fakeTest = Http::get('https://example.com/test');
            
            return [
                'demonstration' => 'Laravel HTTP Client Features',
                'features' => [
                    'retry' => [
                        'status' => $retryTest->status(),
                        'description' => 'Automatic retry on failure'
                    ],
                    'timeout' => [
                        'status' => $timeoutTest->status(),
                        'description' => 'Request timeout handling'
                    ],
                    'fake' => [
                        'data' => $fakeTest->json(),
                        'description' => 'HTTP faking for testing'
                    ]
                ],
                'status' => 'completed'
            ];
            
        } catch (Exception $e) {
            return [
                'demonstration' => 'Laravel HTTP Client Features',
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }
}
