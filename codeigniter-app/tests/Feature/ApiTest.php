<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class ApiTest extends TestCase
{
    public function testApiEndpoint(): void
    {
        $response = $this->httpGet($this->baseUrl . '/api');
        
        // API might not exist in all apps, so we check for 200 or 404
        $this->assertTrue(
            in_array($response['status_code'], [200, 404]),
            'API endpoint should return 200 or 404'
        );
        
        if ($response['status_code'] === 200) {
            $this->assertResponseIsJson($response);
        }
    }
    
    public function testApiPerformance(): void
    {
        $response = $this->httpGet($this->baseUrl . '/api');
        
        if ($response['status_code'] === 200) {
            $this->assertResponseTimeAcceptable($response, 0.5);
        }
    }
}
