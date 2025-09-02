<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class HttpTest extends TestCase
{
    public function testApplicationHomePage(): void
    {
        $response = $this->httpGet($this->baseUrl);
        
        $this->assertHttpSuccess($response);
        $this->assertResponseTimeAcceptable($response, 1.0);
        $this->assertNotEmpty($response['body']);
    }
    
    public function testHealthEndpoint(): void
    {
        $this->testHealthEndpoint($this->baseUrl);
    }
    
    public function testBasicEndpoints(): void
    {
        $this->testBasicEndpoints($this->baseUrl);
    }
    
    public function testErrorHandling(): void
    {
        $response = $this->httpGet($this->baseUrl . '/nonexistent-page');
        $this->assertHttpStatus(404, $response);
    }
}
