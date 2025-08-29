<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApmController;

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

// Main dashboard route
Route::get('/', [ApmController::class, 'index'])->name('apm.dashboard');

// AJAX API routes for the dashboard
Route::post('/test-databases', [ApmController::class, 'testDatabases'])->name('apm.test-databases');
Route::post('/demo-crud', [ApmController::class, 'demoCrud'])->name('apm.demo-crud');
Route::post('/fetch-api-data', [ApmController::class, 'fetchApiData'])->name('apm.fetch-api-data');
Route::post('/test-queue', [ApmController::class, 'testQueue'])->name('apm.test-queue');
Route::post('/add-queue-data', [ApmController::class, 'addQueueData'])->name('apm.add-queue-data');
Route::post('/read-queue-data', [ApmController::class, 'readQueueData'])->name('apm.read-queue-data');
Route::post('/clear-queue', [ApmController::class, 'clearQueue'])->name('apm.clear-queue');

// Health check route
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'php_version' => phpversion(),
        'laravel_version' => app()->version()
    ]);
})->name('health');