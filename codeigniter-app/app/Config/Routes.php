<?php

declare(strict_types=1);

/**
 * This file is part of CodeIgniter 4 framework.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('ApmController');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
$routes->setAutoRoute(false);

/*
 * --------------------------------------------------------------------
 * Route Definitions
 * --------------------------------------------------------------------
 */

// Dashboard route
$routes->get('/', 'ApmController::index');

// API routes
$routes->post('api/external', 'ApmController::externalApi');
$routes->post('api/db/connection', 'ApmController::dbConnectionCheck');
$routes->post('api/db/crud', 'ApmController::dbCrud');
$routes->post('api/redis/insert-batch', 'ApmController::redisInsertBatch');
$routes->post('api/redis/insert-one', 'ApmController::redisInsertOne');
$routes->post('api/redis/pop', 'ApmController::redisPop');
$routes->post('api/redis/clear', 'ApmController::redisClear');

/*
 * --------------------------------------------------------------------
 * Additional Routing
 * --------------------------------------------------------------------
 *
 * There will often be times that you need additional routing and you
 * need it to be able to override any defaults in this file. Environment
 * based routes is one such time. require() additional route files here
 * to make that happen.
 *
 * You will have access to the $routes object within that file without
 * needing to reload it.
 */
if (is_file(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
    require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}
