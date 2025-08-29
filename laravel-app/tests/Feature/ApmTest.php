<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApmTest extends TestCase
{
    /**
     * Test the main dashboard loads successfully.
     */
    public function test_dashboard_loads_successfully(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Laravel Application');
        $response->assertSee('APM Integration Example');
    }

    /**
     * Test database connections endpoint.
     */
    public function test_database_connections_endpoint(): void
    {
        $response = $this->post('/test-databases');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data'
        ]);
    }

    /**
     * Test CRUD operations endpoint.
     */
    public function test_crud_operations_endpoint(): void
    {
        $response = $this->post('/demo-crud');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data'
        ]);
    }

    /**
     * Test external API endpoint.
     */
    public function test_external_api_endpoint(): void
    {
        $response = $this->post('/fetch-api-data');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data'
        ]);
    }

    /**
     * Test queue operations endpoint.
     */
    public function test_queue_operations_endpoint(): void
    {
        $response = $this->post('/test-queue');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data'
        ]);
    }

    /**
     * Test add queue data endpoint.
     */
    public function test_add_queue_data_endpoint(): void
    {
        $response = $this->post('/add-queue-data', [
            'data' => '{"message": "test", "priority": 1}'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message'
        ]);
    }

    /**
     * Test health check endpoint.
     */
    public function test_health_check_endpoint(): void
    {
        $response = $this->get('/health');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'timestamp',
            'php_version',
            'laravel_version'
        ]);

        $response->assertJson([
            'status' => 'ok'
        ]);
    }

    /**
     * Test that all required routes are registered.
     */
    public function test_all_routes_are_registered(): void
    {
        $routes = [
            '/',
            '/test-databases',
            '/demo-crud',
            '/fetch-api-data',
            '/test-queue',
            '/add-queue-data',
            '/read-queue-data',
            '/clear-queue',
            '/health'
        ];

        foreach ($routes as $route) {
            $method = $route === '/' || $route === '/health' ? 'get' : 'post';
            $response = $this->$method($route);

            // Should not return 404
            $this->assertNotEquals(404, $response->getStatusCode(), "Route {$route} returned 404");
        }
    }
}