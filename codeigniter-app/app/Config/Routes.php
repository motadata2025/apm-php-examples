<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
// Main dashboard route
$routes->get('/', 'ApmController::index');

// Health check route (comprehensive)
$routes->get('/health', 'ApmController::healthCheck');
$routes->get("api/metrics", "Health::metrics");

// AJAX API routes for the dashboard (like Simple PHP)
$routes->post('/', 'ApmController::handleAjax');

// Individual API routes for direct access
$routes->post('apm/testDatabases', 'ApmController::testDatabases');
$routes->post('apm/createTables', 'ApmController::createTables');
$routes->post('apm/demoCrud', 'ApmController::demoCrud');
$routes->post('apm/fetchApiData', 'ApmController::fetchApiData');
$routes->post('apm/testQueue', 'ApmController::testQueue');
$routes->post('apm/addQueueData', 'ApmController::addQueueData');
$routes->post('apm/readQueueData', 'ApmController::readQueueData');
$routes->post('apm/clearQueue', 'ApmController::clearQueue');
$routes->post('apm/generateNewRandomData', 'ApmController::generateNewRandomData');
$routes->post('apm/debugEnv', 'ApmController::debugEnv');
$routes->get('apm/healthCheck', 'ApmController::healthCheck');
$routes->get("api/metrics", "Health::metrics");
