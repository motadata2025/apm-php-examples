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

// Root route for CLI server compatibility
Route::get('/', function () {
    return '<h1>🚀 Laravel APM Application</h1><p>✅ Laravel Framework Working</p><p>✅ CLI Server Mode Active</p><p><a href="/health">Health Check</a> | <a href="/apm">APM Dashboard</a></p>';
});

// Simple test route to verify Laravel is working
Route::get('/test', function () {
    return '<h1>🎉 Laravel is Working!</h1><p>✅ APP_KEY automation successful</p><p>✅ Service providers loaded</p><p>✅ Routes working</p>';
});

// Main dashboard route
Route::get('/', [ApmController::class, 'index'])->name('apm.dashboard');

// Health check route (comprehensive)
Route::get('/health', [ApmController::class, 'healthCheck'])->name('health');

// AJAX API routes for the dashboard (like Simple PHP)
Route::post('/', function(\Illuminate\Http\Request $request) {
    $controller = new ApmController();
    $action = $request->input('action');

    switch ($action) {
        case 'test_databases':
            return $controller->testDatabases();
        case 'create_tables':
            return $controller->createTables();
        case 'demo_crud':
            return $controller->demoCrud();
        case 'fetch_api_data':
            return $controller->fetchApiData();
        case 'test_queue':
            return $controller->testQueue();
        case 'add_queue_data':
            return $controller->addQueueData();
        case 'read_queue_data':
            return $controller->readQueueData();
        case 'clear_queue':
            return $controller->clearQueue();
        case 'generate_random_data':
            return $controller->generateNewRandomData();
        case 'debug_env':
            return $controller->debugEnv();
        default:
            return response()->json(['success' => false, 'error' => 'Unknown action']);
    }
})->name('apm.ajax');

// API test endpoint
Route::get('/api/test', [ApmController::class, 'fetchApiData'])->name('api.test');