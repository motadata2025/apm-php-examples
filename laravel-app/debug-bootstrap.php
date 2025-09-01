<?php

echo "=== Laravel Bootstrap Debug Test ===\n";

try {
    echo "1. Loading autoloader...\n";
    require __DIR__.'/vendor/autoload.php';
    echo "✅ Autoloader loaded successfully\n";

    echo "2. Loading Laravel application...\n";
    $app = require_once __DIR__.'/bootstrap/app.php';
    echo "✅ Laravel application loaded successfully\n";

    echo "3. Testing configuration...\n";
    $config = $app->make('config');
    echo "✅ Configuration service loaded\n";

    echo "4. Testing view configuration...\n";
    $viewPaths = $config->get('view.paths');
    echo "View paths: " . print_r($viewPaths, true) . "\n";

    echo "5. Testing database configuration...\n";
    $dbConfig = $config->get('database.default');
    echo "Default database: " . $dbConfig . "\n";

    echo "6. Testing APP_KEY...\n";
    $appKey = $config->get('app.key');
    echo "APP_KEY configured: " . ($appKey ? "✅ YES" : "❌ NO") . "\n";

    echo "7. Testing route loading...\n";
    $router = $app->make('router');
    echo "✅ Router service loaded\n";

    echo "\n=== Bootstrap test completed successfully ===\n";

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
