<?php
/**
 * Simple PHP - Database Connection Test
 * Tests database connections using the corrected port configuration
 */

// Load configuration
$config = [];
if (file_exists('config/app.env')) {
    $lines = file('config/app.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && !str_starts_with($line, '#')) {
            list($key, $value) = explode('=', $line, 2);
            $config[trim($key)] = trim($value);
        }
    }
}

echo "=== Database Connection Test ===\n";
echo "Configuration loaded from config/app.env\n\n";

$results = [];

// Test MySQL Connection
echo "1. Testing MySQL Connection...\n";
echo "   Host: {$config['MYSQL_HOST']}\n";
echo "   Port: {$config['MYSQL_PORT']}\n";
echo "   Database: {$config['MYSQL_DATABASE']}\n";

try {
    $mysql_dsn = "mysql:host={$config['MYSQL_HOST']};port={$config['MYSQL_PORT']};dbname={$config['MYSQL_DATABASE']}";
    $mysql_pdo = new PDO($mysql_dsn, $config['MYSQL_USERNAME'], $config['MYSQL_PASSWORD']);
    $mysql_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $mysql_result = $mysql_pdo->query("SELECT 1 as test")->fetch();
    $results['mysql'] = "✅ Connected successfully (Test query returned: {$mysql_result['test']})";
    echo "   Result: ✅ Connected successfully\n";
} catch (Exception $e) {
    $results['mysql'] = "❌ Failed: " . $e->getMessage();
    echo "   Result: ❌ Failed: " . $e->getMessage() . "\n";
}

echo "\n";

// Test PostgreSQL Connection
echo "2. Testing PostgreSQL Connection...\n";
echo "   Host: {$config['POSTGRES_HOST']}\n";
echo "   Port: {$config['POSTGRES_PORT']}\n";
echo "   Database: {$config['POSTGRES_DATABASE']}\n";

try {
    $postgres_dsn = "pgsql:host={$config['POSTGRES_HOST']};port={$config['POSTGRES_PORT']};dbname={$config['POSTGRES_DATABASE']}";
    $postgres_pdo = new PDO($postgres_dsn, $config['POSTGRES_USERNAME'], $config['POSTGRES_PASSWORD']);
    $postgres_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $postgres_result = $postgres_pdo->query("SELECT 1 as test")->fetch();
    $results['postgres'] = "✅ Connected successfully (Test query returned: {$postgres_result['test']})";
    echo "   Result: ✅ Connected successfully\n";
} catch (Exception $e) {
    $results['postgres'] = "❌ Failed: " . $e->getMessage();
    echo "   Result: ❌ Failed: " . $e->getMessage() . "\n";
}

echo "\n";

// Test Redis Connection
echo "3. Testing Redis Connection...\n";
echo "   Host: {$config['REDIS_HOST']}\n";
echo "   Port: {$config['REDIS_PORT']}\n";

try {
    if (extension_loaded('redis')) {
        $redis = new Redis();
        $redis->connect($config['REDIS_HOST'], $config['REDIS_PORT']);
        $redis->ping();
        $results['redis'] = "✅ Connected successfully (Redis extension)";
        echo "   Result: ✅ Connected successfully (Redis extension)\n";
    } else {
        // Fallback to socket connection test
        $socket = @fsockopen($config['REDIS_HOST'], $config['REDIS_PORT'], $errno, $errstr, 5);
        if ($socket) {
            fclose($socket);
            $results['redis'] = "✅ Connected successfully (Socket test)";
            echo "   Result: ✅ Connected successfully (Socket test)\n";
        } else {
            $results['redis'] = "❌ Failed: $errstr ($errno)";
            echo "   Result: ❌ Failed: $errstr ($errno)\n";
        }
    }
} catch (Exception $e) {
    $results['redis'] = "❌ Failed: " . $e->getMessage();
    echo "   Result: ❌ Failed: " . $e->getMessage() . "\n";
}

echo "\n=== Summary ===\n";
foreach ($results as $service => $result) {
    echo "$service: $result\n";
}

// JSON output for API compatibility
echo "\n=== JSON Output ===\n";
echo json_encode([
    'success' => true,
    'data' => $results,
    'timestamp' => date('Y-m-d H:i:s'),
    'config_ports' => [
        'mysql' => $config['MYSQL_PORT'],
        'postgres' => $config['POSTGRES_PORT'],
        'redis' => $config['REDIS_PORT']
    ]
], JSON_PRETTY_PRINT);
echo "\n";
?>
