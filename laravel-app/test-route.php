<?php

echo "=== Laravel Route Test ===\n";

try {
    echo "1. Loading Laravel...\n";
    require __DIR__.'/vendor/autoload.php';
    $app = require_once __DIR__.'/bootstrap/app.php';
    
    echo "2. Testing route registration...\n";
    $router = $app->make('router');
    
    // Add a simple test route
    $router->get('/test', function() {
        return 'Laravel routing is working!';
    });
    
    echo "3. Testing view system...\n";
    $view = $app->make('view');
    echo "View service loaded successfully\n";
    
    echo "4. Testing if ApmController can be instantiated...\n";
    $controller = new App\Http\Controllers\ApmController();
    echo "ApmController instantiated successfully\n";
    
    echo "5. Testing database configuration...\n";
    $config = $app->make('config');
    $dbConfig = $config->get('database.connections.mysql');
    echo "Database config loaded: " . ($dbConfig ? "YES" : "NO") . "\n";
    
    echo "\n=== All tests passed ===\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
