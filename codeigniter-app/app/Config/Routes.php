<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

// APM Testing Routes
$routes->post('apm/test-databases', 'Apm::testDatabases');
$routes->post('apm/demo-crud', 'Apm::demoCrud');
$routes->post('apm/fetch-api-data', 'Apm::fetchApiData');
$routes->post('apm/test-queue', 'Apm::testQueue');
$routes->get('apm/health', 'Apm::health');
