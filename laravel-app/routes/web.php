<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApmUiController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// APM UI Routes
Route::get('/', [ApmUiController::class, 'index']);

// API Routes for AJAX calls
Route::post('/api/external-call', [ApmUiController::class, 'externalApi']);
Route::post('/api/db-check', [ApmUiController::class, 'dbCheck']);
Route::post('/api/db-crud', [ApmUiController::class, 'dbCrud']);
Route::post('/api/redis/insert-bulk', [ApmUiController::class, 'redisInsertBulk']);
Route::post('/api/redis/insert-single', [ApmUiController::class, 'redisInsertSingle']);
Route::post('/api/redis/pop', [ApmUiController::class, 'redisReadOne']);
Route::post('/api/redis/clear', [ApmUiController::class, 'redisClear']);
