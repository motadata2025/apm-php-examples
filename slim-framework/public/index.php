<?php

/**
 * Slim Framework APM Application Bootstrap
 * Main entry point for the application
 */

use App\AppConfig;
use App\Controllers\ApiController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

// Autoload dependencies
require_once __DIR__ . '/../vendor/autoload.php';

// Initialize configuration
$config = new AppConfig();

// Create Slim app
$app = AppFactory::create();

// Add error middleware
$errorMiddleware = $app->addErrorMiddleware(
    $config->get('app_debug', true), // Display error details
    true, // Log errors
    true  // Log error details
);

// Add routing middleware
$app->addRoutingMiddleware();

// Initialize API controller
$apiController = new ApiController($config);

// Routes
$app->get('/', function (Request $request, Response $response) use ($config) {
    $phpVersion = phpversion();
    $appName = $config->get('app_name', 'Slim Framework App');
    
    $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slim Framework APM Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/main.css">
</head>
<body>
    <div class="container mt-4">
        <header class="text-center mb-5">
            <h1 class="display-4 text-primary">Slim Framework APM Dashboard</h1>
            <p class="lead">Application Performance Monitoring & Testing Interface</p>
        </header>

        <div class="row g-4">
            <!-- Card 1: Application Information -->
            <div class="col-md-4">
                <div class="card h-100 border-primary">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Application Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <strong>Application Type:</strong>
                            <span class="badge bg-primary ms-2">slim-framework</span>
                        </div>
                        <div class="mb-3">
                            <strong>Running PHP Version:</strong>
                            <span class="badge bg-success ms-2" id="php-version">{$phpVersion}</span>
                        </div>
                        <div class="mb-3">
                            <strong>Web Server:</strong>
                            <span class="badge bg-info ms-2">php_cli</span>
                        </div>
                        <div class="mt-4">
                            <button class="btn btn-outline-primary w-100" onclick="testExternalApi()">
                                <i class="spinner-border spinner-border-sm d-none" id="external-spinner"></i>
                                External API Test
                            </button>
                            <div id="external-result" class="mt-2"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 2: Database Operations -->
            <div class="col-md-4">
                <div class="card h-100 border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="card-title mb-0">Database Operations</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-warning" onclick="testDbConnection()">
                                <i class="spinner-border spinner-border-sm d-none" id="db-check-spinner"></i>
                                Connection Check
                            </button>
                            <button class="btn btn-outline-warning" onclick="testDbCrud()">
                                <i class="spinner-border spinner-border-sm d-none" id="db-crud-spinner"></i>
                                DB CRUD Operations
                            </button>
                        </div>
                        <div id="db-result" class="mt-3"></div>
                    </div>
                </div>
            </div>

            <!-- Card 3: Redis Queue Operations -->
            <div class="col-md-4">
                <div class="card h-100 border-success">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">Redis Queue Operations</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-success btn-sm" onclick="redisInsertBulk()">
                                <i class="spinner-border spinner-border-sm d-none" id="redis-bulk-spinner"></i>
                                Insert 3 Items
                            </button>
                            <button class="btn btn-outline-success btn-sm" onclick="redisInsertSingle()">
                                <i class="spinner-border spinner-border-sm d-none" id="redis-single-spinner"></i>
                                Insert 1 + Count
                            </button>
                            <button class="btn btn-outline-success btn-sm" onclick="redisReadSingle()">
                                <i class="spinner-border spinner-border-sm d-none" id="redis-read-spinner"></i>
                                Read 1 Item
                            </button>
                            <button class="btn btn-outline-danger btn-sm" onclick="redisClear()">
                                <i class="spinner-border spinner-border-sm d-none" id="redis-clear-spinner"></i>
                                Clear Queue
                            </button>
                        </div>
                        <div id="redis-result" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Toast container -->
        <div class="toast-container position-fixed bottom-0 end-0 p-3" id="toast-container"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/main.js"></script>
</body>
</html>
HTML;

    $response->getBody()->write($html);
    return $response->withHeader('Content-Type', 'text/html');
});

// API Routes
$app->post('/api/external', [$apiController, 'externalApi']);
$app->post('/api/db/check', [$apiController, 'dbConnectionCheck']);
$app->post('/api/db/crud', [$apiController, 'dbCrud']);
$app->post('/api/redis/insert_bulk', [$apiController, 'redisInsertBulk']);
$app->post('/api/redis/insert_single', [$apiController, 'redisInsertSingle']);
$app->post('/api/redis/read_single', [$apiController, 'redisReadSingle']);
$app->post('/api/redis/clear', [$apiController, 'redisClear']);

// Run the application
$app->run();
