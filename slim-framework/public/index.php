<?php

use App\Controllers\ApmController;
use DI\Container;
use Dotenv\Dotenv;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

require __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Create Container
$container = new Container();

// Set container to create App with on AppFactory
AppFactory::setContainer($container);

// Add Twig-View Renderer
$container->set('view', function () {
    return Twig::create(__DIR__ . '/../templates', ['cache' => false]);
});

// Add Logger
$container->set('logger', function () {
    $logger = new Logger('slim-app');
    $logger->pushHandler(new StreamHandler($_ENV['LOG_PATH'] ?? 'var/logs/app.log', $_ENV['LOG_LEVEL'] ?? 'debug'));
    return $logger;
});

// Add ApmController
$container->set(ApmController::class, function ($container) {
    return new ApmController($container->get('view'), $container->get('logger'));
});

// Create App
$app = AppFactory::create();

// Add Twig-View Middleware
$app->add(TwigMiddleware::createFromContainer($app, 'view'));

// Add error middleware
$app->addErrorMiddleware($_ENV['APP_DEBUG'] ?? true, true, true);

// Add body parsing middleware
$app->addBodyParsingMiddleware();

// Routes
$app->get('/', [ApmController::class, 'dashboard']);
$app->post('/test-databases', [ApmController::class, 'testDatabases']);
$app->post('/demo-crud', [ApmController::class, 'demoCrud']);
$app->post('/fetch-api-data', [ApmController::class, 'fetchApiData']);
$app->post('/test-queue', [ApmController::class, 'testQueue']);
$app->post('/add-queue-data', [ApmController::class, 'addQueueData']);
$app->post('/read-queue-data', [ApmController::class, 'readQueueData']);
$app->post('/clear-queue', [ApmController::class, 'clearQueue']);
$app->get('/health', [ApmController::class, 'health']);

// Run app
$app->run();