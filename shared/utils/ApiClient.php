<?php

namespace Shared\Utils;

/**
 * API Client for external HTTP requests
 * Provides methods for calling external APIs like JSONPlaceholder and JokeAPI
 */
class ApiClient
{
    private $timeout;
    private $userAgent;

    public function __construct(int $timeout = 30)
    {
        $this->timeout = $timeout;
        $this->userAgent = 'APM-PHP-Examples/1.0';
    }

    /**
     * Make HTTP GET request using cURL
     */
    public function get(string $url, array $headers = []): array
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_USERAGENT => $this->userAgent,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_SSL_VERIFYPEER => false, // For development only
            CURLOPT_HTTPHEADER => $this->formatHeaders($headers)
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            throw new \Exception("cURL Error: {$error}");
        }

        if ($httpCode >= 400) {
            throw new \Exception("HTTP Error {$httpCode}: {$response}");
        }

        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("JSON Decode Error: " . json_last_error_msg());
        }

        return [
            'status_code' => $httpCode,
            'data' => $decoded,
            'raw_response' => $response
        ];
    }

    /**
     * Make HTTP POST request using cURL
     */
    public function post(string $url, array $data = [], array $headers = []): array
    {
        $ch = curl_init();

        $defaultHeaders = ['Content-Type: application/json'];
        $headers = array_merge($defaultHeaders, $headers);

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_USERAGENT => $this->userAgent,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => $this->formatHeaders($headers)
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            throw new \Exception("cURL Error: {$error}");
        }

        if ($httpCode >= 400) {
            throw new \Exception("HTTP Error {$httpCode}: {$response}");
        }

        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("JSON Decode Error: " . json_last_error_msg());
        }

        return [
            'status_code' => $httpCode,
            'data' => $decoded,
            'raw_response' => $response
        ];
    }

    /**
     * Fetch posts from JSONPlaceholder API
     */
    public function fetchJsonPlaceholderPosts(int $limit = 10): array
    {
        $url = "https://jsonplaceholder.typicode.com/posts";
        $response = $this->get($url);

        // Limit the results
        $posts = array_slice($response['data'], 0, $limit);

        return [
            'source' => 'JSONPlaceholder',
            'count' => count($posts),
            'posts' => $posts
        ];
    }

    /**
     * Fetch a random joke from JokeAPI
     */
    public function fetchRandomJoke(): array
    {
        $url = "https://sv443.net/jokeapi/v2/joke/Any?blacklistFlags=nsfw,religious,political,racist,sexist,explicit";
        $response = $this->get($url);

        return [
            'source' => 'JokeAPI',
            'joke' => $response['data']
        ];
    }

    /**
     * Fetch user data from JSONPlaceholder
     */
    public function fetchJsonPlaceholderUsers(int $limit = 5): array
    {
        $url = "https://jsonplaceholder.typicode.com/users";
        $response = $this->get($url);

        // Limit the results
        $users = array_slice($response['data'], 0, $limit);

        return [
            'source' => 'JSONPlaceholder',
            'count' => count($users),
            'users' => $users
        ];
    }

    /**
     * Test multiple API endpoints
     */
    public function testMultipleApis(): array
    {
        $results = [];

        try {
            $results['posts'] = $this->fetchJsonPlaceholderPosts(3);
        } catch (\Exception $e) {
            $results['posts'] = ['error' => $e->getMessage()];
        }

        try {
            $results['joke'] = $this->fetchRandomJoke();
        } catch (\Exception $e) {
            $results['joke'] = ['error' => $e->getMessage()];
        }

        try {
            $results['users'] = $this->fetchJsonPlaceholderUsers(2);
        } catch (\Exception $e) {
            $results['users'] = ['error' => $e->getMessage()];
        }

        return $results;
    }

    /**
     * Format headers for cURL
     */
    private function formatHeaders(array $headers): array
    {
        $formatted = [];
        foreach ($headers as $key => $value) {
            if (is_numeric($key)) {
                $formatted[] = $value;
            } else {
                $formatted[] = "{$key}: {$value}";
            }
        }
        return $formatted;
    }
}