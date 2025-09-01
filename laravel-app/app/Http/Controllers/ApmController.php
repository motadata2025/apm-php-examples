<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;
use Exception;

class ApmController extends Controller
{
    /**
     * Display the main APM dashboard
     */
    public function index()
    {
        $phpVersion = phpversion();
        $framework = 'Laravel ' . app()->version();

        // Detect the actual web server and PHP handler
        $deploymentType = $this->detectWebServerType();

        // Override with environment variable if set (for testing/debugging)
        if (isset($_ENV['DEPLOYMENT_DESC'])) {
            $deploymentType = $_ENV['DEPLOYMENT_DESC'];
        }

        // Generate random data for queue operations
        $randomData = $this->generateRandomData();

        return view('apm.dashboard', compact('phpVersion', 'framework', 'deploymentType', 'randomData'));
    }
    
    private function detectWebServerType()
    {
        $sapi = php_sapi_name();
        $server = $_SERVER['SERVER_SOFTWARE'] ?? '';
        
        if ($sapi === 'cli-server') {
            return 'PHP Built-in Server';
        } elseif (strpos($server, 'Apache') !== false) {
            if (function_exists('apache_get_modules') && in_array('mod_php', apache_get_modules())) {
                return 'Apache with mod_php';
            } else {
                return 'Apache with PHP-FPM';
            }
        } elseif (strpos($server, 'nginx') !== false) {
            return 'Nginx with PHP-FPM';
        } else {
            return 'Unknown Web Server (' . $sapi . ')';
        }
    }
    
    /**
     * Generate random demo data for queue operations (like Simple PHP)
     */
    private function generateRandomData(): array
    {
        $names = ['Alice Johnson', 'Bob Smith', 'Carol Davis', 'David Wilson', 'Eva Brown', 'Frank Miller', 'Grace Lee', 'Henry Taylor'];
        $actions = ['process_order', 'send_notification', 'generate_report', 'backup_data', 'sync_database', 'send_email'];
        $priorities = ['high', 'medium', 'low'];
        $departments = ['Sales', 'Marketing', 'Engineering', 'Support', 'Finance'];

        $randomName = $names[array_rand($names)];
        $randomAction = $actions[array_rand($actions)];
        $randomPriority = $priorities[array_rand($priorities)];
        $randomDepartment = $departments[array_rand($departments)];

        return [
            'id' => uniqid('laravel_task_'),
            'name' => $randomName,
            'email' => strtolower(str_replace(' ', '.', $randomName)) . '@company.com',
            'action' => $randomAction,
            'priority' => $randomPriority,
            'department' => $randomDepartment,
            'created_at' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 minute')),
            'data' => [
                'amount' => rand(100, 10000),
                'reference' => 'REF-' . rand(10000, 99999),
                'notes' => 'Auto-generated Laravel task for ' . $randomAction
            ]
        ];
    }

    /**
     * Comprehensive health check endpoint (like Simple PHP)
     */
    public function healthCheck()
    {
        try {
            $health = [
                'status' => 'healthy',
                'timestamp' => now()->toISOString(),
                'php_version' => phpversion(),
                'laravel_version' => app()->version(),
                'memory_usage' => memory_get_usage(true),
                'uptime' => $this->getUptime(),
                'services' => $this->checkServices()
            ];

            // Determine overall status
            foreach ($health['services'] as $service => $status) {
                if ($status !== 'healthy') {
                    $health['status'] = 'unhealthy';
                    break;
                }
            }

            return response()->json($health, 200, [], JSON_PRETTY_PRINT);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'unhealthy',
                'timestamp' => now()->toISOString(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get system uptime
     */
    private function getUptime(): ?int
    {
        if (file_exists('/proc/uptime')) {
            $uptime = file_get_contents('/proc/uptime');
            $uptime = floatval(explode(' ', $uptime)[0]);
            return round($uptime);
        }
        return null;
    }

    /**
     * Check service health (updated for Laravel ports)
     */
    private function checkServices(): array
    {
        $services = [];

        // Check Redis (Laravel port: 6384)
        try {
            $redis = new \Redis();
            $redis->connect('127.0.0.1', 6384);
            $redis->ping();
            $redis->close();
            $services['redis'] = 'healthy';
        } catch (\Exception $e) {
            $services['redis'] = 'unhealthy';
        }

        // Check MySQL (Laravel port: 3311)
        try {
            $pdo = new \PDO('mysql:host=127.0.0.1;port=3311;dbname=laravel_app_db', 'root', 'rootpassword');
            $pdo->query('SELECT 1');
            $services['mysql'] = 'healthy';
        } catch (\Exception $e) {
            $services['mysql'] = 'unhealthy';
        }

        // Check PostgreSQL (Laravel port: 5437)
        try {
            $pdo = new \PDO('pgsql:host=127.0.0.1;port=5437;dbname=laravel_app_db', 'postgres', 'postgrespassword');
            $pdo->query('SELECT 1');
            $services['postgres'] = 'healthy';
        } catch (\Exception $e) {
            $services['postgres'] = 'unhealthy';
        }

        return $services;
    }

    /**
     * Test database connections (comprehensive like Simple PHP)
     */
    public function testDatabases()
    {
        try {
            $results = [];

            // Test MySQL connection (Laravel port: 3311)
            try {
                $pdo = new \PDO('mysql:host=127.0.0.1;port=3311;dbname=laravel_app_db', 'root', 'rootpassword');
                $pdo->query('SELECT 1');
                $results['mysql'] = 'Connected';
            } catch (\Exception $e) {
                $results['mysql'] = 'Failed: ' . $e->getMessage();
            }

            // Test PostgreSQL connection (Laravel port: 5437)
            try {
                $pdo = new \PDO('pgsql:host=127.0.0.1;port=5437;dbname=laravel_app_db', 'postgres', 'postgrespassword');
                $pdo->query('SELECT 1');
                $results['postgres'] = 'Connected';
            } catch (\Exception $e) {
                $results['postgres'] = 'Failed: ' . $e->getMessage();
            }

            // Test Redis connection (Laravel port: 6384)
            try {
                $redis = new \Redis();
                $redis->connect('127.0.0.1', 6384);
                $redis->ping();
                $redis->close();
                $results['redis'] = 'Connected';
            } catch (\Exception $e) {
                $results['redis'] = 'Failed: ' . $e->getMessage();
            }

            // Test Laravel DB connections
            try {
                DB::connection('mysql')->getPdo();
                DB::connection('mysql')->select('SELECT 1');
                $results['laravel_mysql'] = 'Connected';
            } catch (\Exception $e) {
                $results['laravel_mysql'] = 'Failed: ' . $e->getMessage();
            }

            return response()->json(['success' => true, 'data' => $results]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Demo CRUD operations (comprehensive like Simple PHP)
     */
    public function demoCrud()
    {
        try {
            $results = [];

            // Perform MySQL CRUD operations
            $results['mysql'] = $this->performMySQLCrud();

            // Perform PostgreSQL CRUD operations
            $results['postgres'] = $this->performPostgreSQLCrud();

            return response()->json(['success' => true, 'data' => $results]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Fetch external API data (comprehensive like Simple PHP)
     */
    public function fetchApiData()
    {
        try {
            $results = [];

            // Test JSONPlaceholder API
            $results['jsonplaceholder'] = $this->testJsonPlaceholder();

            // Test HTTPBin API
            $results['httpbin'] = $this->testHttpBin();

            // Test slow API simulation
            $results['slow_api'] = $this->testSlowApi();

            return response()->json(['success' => true, 'data' => $results]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Test JSONPlaceholder API
     */
    private function testJsonPlaceholder(): array
    {
        try {
            $startTime = microtime(true);

            // Get posts
            $postsResponse = Http::timeout(10)->get('https://jsonplaceholder.typicode.com/posts', ['_limit' => 5]);
            $postsData = $postsResponse->json();
            $postsDuration = microtime(true) - $startTime;

            // Get specific post
            $startTime = microtime(true);
            $postResponse = Http::timeout(10)->get('https://jsonplaceholder.typicode.com/posts/1');
            $postData = $postResponse->json();
            $postDuration = microtime(true) - $startTime;

            // Create new post
            $startTime = microtime(true);
            $newPostResponse = Http::timeout(10)->post('https://jsonplaceholder.typicode.com/posts', [
                'title' => 'Laravel APM Test Post',
                'body' => 'This is a test post from Laravel APM Examples',
                'userId' => 1
            ]);
            $newPostData = $newPostResponse->json();
            $newPostDuration = microtime(true) - $startTime;

            return [
                'service' => 'JSONPlaceholder',
                'tests' => [
                    'get_posts' => [
                        'success' => $postsResponse->successful(),
                        'status_code' => $postsResponse->status(),
                        'duration' => round($postsDuration * 1000, 2),
                        'data_count' => count($postsData ?? [])
                    ],
                    'get_post' => [
                        'success' => $postResponse->successful(),
                        'status_code' => $postResponse->status(),
                        'duration' => round($postDuration * 1000, 2),
                        'data' => $postData
                    ],
                    'create_post' => [
                        'success' => $newPostResponse->successful(),
                        'status_code' => $newPostResponse->status(),
                        'duration' => round($newPostDuration * 1000, 2),
                        'data' => $newPostData
                    ]
                ],
                'status' => 'completed'
            ];

        } catch (\Exception $e) {
            return [
                'service' => 'JSONPlaceholder',
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Test HTTPBin API
     */
    private function testHttpBin(): array
    {
        try {
            // Test GET with parameters
            $startTime = microtime(true);
            $getResponse = Http::timeout(10)->get('https://httpbin.org/get', ['param1' => 'value1', 'param2' => 'value2']);
            $getDuration = microtime(true) - $startTime;

            // Test POST with data
            $startTime = microtime(true);
            $postResponse = Http::timeout(10)->post('https://httpbin.org/post', ['test' => 'data', 'timestamp' => time()]);
            $postDuration = microtime(true) - $startTime;

            // Test delay endpoint
            $startTime = microtime(true);
            $delayResponse = Http::timeout(15)->get('https://httpbin.org/delay/2');
            $delayDuration = microtime(true) - $startTime;

            return [
                'service' => 'HTTPBin',
                'tests' => [
                    'get_test' => [
                        'success' => $getResponse->successful(),
                        'status_code' => $getResponse->status(),
                        'duration' => round($getDuration * 1000, 2)
                    ],
                    'post_test' => [
                        'success' => $postResponse->successful(),
                        'status_code' => $postResponse->status(),
                        'duration' => round($postDuration * 1000, 2)
                    ],
                    'delay_test' => [
                        'success' => $delayResponse->successful(),
                        'status_code' => $delayResponse->status(),
                        'duration' => round($delayDuration * 1000, 2)
                    ]
                ],
                'status' => 'completed'
            ];

        } catch (\Exception $e) {
            return [
                'service' => 'HTTPBin',
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Test slow API simulation
     */
    private function testSlowApi(): array
    {
        try {
            // Simulate slow API call
            $startTime = microtime(true);
            sleep(1); // Simulate 1 second delay
            $duration = microtime(true) - $startTime;

            return [
                'service' => 'Slow API Simulation',
                'duration' => round($duration * 1000, 2),
                'status' => 'completed',
                'message' => 'Simulated slow API response from Laravel'
            ];

        } catch (\Exception $e) {
            return [
                'service' => 'Slow API Simulation',
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Test queue system (comprehensive like Simple PHP)
     */
    public function testQueue()
    {
        try {
            $queueName = $this->getQueueName();
            $results = [];

            // Add some demo data using Redis directly (Laravel style)
            $demoData = [
                ['name' => 'Laravel Demo User 1', 'email' => 'demo1@laravel.com', 'action' => 'process_user'],
                ['name' => 'Laravel Demo User 2', 'email' => 'demo2@laravel.com', 'action' => 'send_email'],
                ['name' => 'Laravel Demo User 3', 'email' => 'demo3@laravel.com', 'action' => 'generate_report']
            ];

            $redis = new \Redis();
            $redis->connect('127.0.0.1', 6384);

            $successCount = 0;
            foreach ($demoData as $data) {
                try {
                    $job = [
                        'id' => uniqid('laravel_'),
                        'data' => $data,
                        'created_at' => time(),
                        'status' => 'pending',
                        'queue_name' => $queueName
                    ];

                    // Add to Redis queue
                    $redis->lpush($queueName, json_encode($job));
                    $redis->expire($queueName, 60); // 1 minute TTL
                    $successCount++;
                } catch (\Exception $e) {
                    // Continue with other items
                }
            }

            $redis->close();

            $results['demo_data_added'] = $successCount;
            $results['queue_name'] = $queueName;
            $results['queue_length'] = $redis->llen($queueName) ?? 0;
            $results['message'] = 'Laravel demo queue operations completed';

            return response()->json(['success' => true, 'data' => $results]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Add data to queue
     */
    public function addQueueData()
    {
        try {
            $queueName = $this->getQueueName();

            // Generate batch of 3 randomized records
            $batchData = [];
            for ($i = 0; $i < 3; $i++) {
                $batchData[] = [
                    'id' => rand(1000, 9999),
                    'message' => 'Laravel queue message ' . rand(100, 999),
                    'timestamp' => date('Y-m-d H:i:s'),
                    'priority' => rand(1, 5),
                    'type' => 'laravel_batch',
                    'batch_index' => $i + 1,
                    'queue_name' => $queueName,
                    'ttl' => 60 // 1 minute TTL (non-modifiable)
                ];
            }

            // Add each item to Redis queue directly (Laravel port: 6384)
            $redis = new \Redis();
            $redis->connect('127.0.0.1', 6384);

            $successCount = 0;
            foreach ($batchData as $data) {
                try {
                    $job = [
                        'id' => uniqid('laravel_'),
                        'data' => $data,
                        'created_at' => time(),
                        'status' => 'pending',
                        'queue_name' => $queueName
                    ];

                    $redis->lpush($queueName, json_encode($job));
                    $redis->expire($queueName, 60); // 1 minute TTL
                    $successCount++;
                } catch (\Exception $e) {
                    // Continue with other items if one fails
                }
            }

            $redis->close();

            $message = "Laravel Queue Data Added:\n";
            $message .= "- Queue Name: {$queueName}\n";
            $message .= "- Batch Size: 3 items\n";
            $message .= "- Successfully Added: {$successCount}/3\n";
            $message .= "- Message TTL: 1 minute (non-modifiable)\n";
            $message .= "- Timestamp: " . date('Y-m-d H:i:s') . "\n";
            $message .= "Batch data added to Laravel queue for processing!";

            return response()->json([
                'success' => $successCount > 0,
                'message' => $message,
                'queue_name' => $queueName,
                'batch_data' => $batchData,
                'total_added' => $successCount,
                'message_ttl' => '1 minute (non-modifiable)'
            ]);
        } catch (\Exception $e) {
            return response('Add queue data failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Read data from queue (comprehensive like Simple PHP)
     */
    public function readQueueData()
    {
        try {
            $queueName = $this->getQueueName();
            $allData = Redis::lrange($queueName, 0, -1);

            $results = [];
            foreach ($allData as $jobData) {
                $job = json_decode($jobData, true);
                if ($job) {
                    $results[] = $job;
                }
            }

            return response()->json([
                'success' => true,
                'data' => $results,
                'count' => count($results),
                'queue_name' => $queueName
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Clear queue data (comprehensive like Simple PHP)
     */
    public function clearQueue()
    {
        try {
            $queueName = $this->getQueueName();
            $itemCount = Redis::llen($queueName);

            // Clear the queue
            Redis::del($queueName);

            return response()->json([
                'success' => true,
                'message' => 'Laravel queue cleared successfully',
                'queue_name' => $queueName,
                'items_removed' => $itemCount
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Generate new random data for queue operations
     */
    public function generateNewRandomData()
    {
        try {
            $randomData = $this->generateRandomData();
            return response()->json(['success' => true, 'data' => $randomData]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Debug environment variables and configuration
     */
    public function debugEnv()
    {
        try {
            $envFiles = [
                base_path('.env'),
                base_path('config/app.env')
            ];

            $fileStatus = [];
            foreach ($envFiles as $file) {
                $fileStatus[$file] = file_exists($file) ? 'exists' : 'not found';
            }

            $envVars = [
                'MYSQL_HOST' => env('DB_HOST', 'not set'),
                'MYSQL_PORT' => env('DB_PORT', 'not set'),
                'MYSQL_DATABASE' => env('DB_DATABASE', 'not set'),
                'POSTGRES_HOST' => config('database.connections.pgsql.host', 'not set'),
                'POSTGRES_PORT' => config('database.connections.pgsql.port', 'not set'),
                'REDIS_HOST' => config('database.redis.default.host', 'not set'),
                'REDIS_PORT' => config('database.redis.default.port', 'not set')
            ];

            return response()->json(['success' => true, 'env' => $envVars, 'files' => $fileStatus]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Create database tables
     */
    public function createTables()
    {
        try {
            $results = [];

            // Create MySQL tables
            try {
                DB::connection('mysql')->statement("
                    CREATE TABLE IF NOT EXISTS users (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        name VARCHAR(255) NOT NULL,
                        email VARCHAR(255) UNIQUE NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    )
                ");

                DB::connection('mysql')->statement("
                    CREATE TABLE IF NOT EXISTS posts (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT,
                        title VARCHAR(255) NOT NULL,
                        content TEXT,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                    )
                ");

                $results['mysql'] = 'Tables created successfully';
            } catch (\Exception $e) {
                $results['mysql'] = 'Failed: ' . $e->getMessage();
            }

            // Create PostgreSQL tables
            try {
                DB::connection('pgsql')->statement("
                    CREATE TABLE IF NOT EXISTS users (
                        id SERIAL PRIMARY KEY,
                        name VARCHAR(255) NOT NULL,
                        email VARCHAR(255) UNIQUE NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )
                ");

                DB::connection('pgsql')->statement("
                    CREATE TABLE IF NOT EXISTS posts (
                        id SERIAL PRIMARY KEY,
                        user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
                        title VARCHAR(255) NOT NULL,
                        content TEXT,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )
                ");

                $results['postgres'] = 'Tables created successfully';
            } catch (\Exception $e) {
                $results['postgres'] = 'Failed: ' . $e->getMessage();
            }

            return response()->json(['success' => true, 'data' => $results]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Perform MySQL CRUD operations using direct PDO (Laravel ports)
     */
    private function performMySQLCrud(): array
    {
        try {
            $pdo = new \PDO('mysql:host=127.0.0.1;port=3311;dbname=laravel_app_db', 'root', 'rootpassword');
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            // Create tables if not exist
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) UNIQUE NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ");

            // Insert 2 randomized records
            $user1 = ['name' => 'Laravel User ' . rand(1000, 9999), 'email' => $this->randomEmail('laravel_mysql')];
            $user2 = ['name' => 'Laravel User ' . rand(1000, 9999), 'email' => $this->randomEmail('laravel_mysql')];

            $stmt = $pdo->prepare("INSERT INTO users (name, email) VALUES (?, ?)");
            $stmt->execute([$user1['name'], $user1['email']]);
            $insertId1 = $pdo->lastInsertId();

            $stmt->execute([$user2['name'], $user2['email']]);
            $insertId2 = $pdo->lastInsertId();

            // Update 1 record
            $stmt = $pdo->prepare("UPDATE users SET name = ? WHERE id = ?");
            $stmt->execute([$user1['name'] . ' Updated', $insertId1]);

            // Delete 1 record
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$insertId2]);

            // Get all data
            $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 10");
            $allData = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            return [
                'database' => 'MySQL',
                'operations' => [
                    'inserted' => 2,
                    'updated' => 1,
                    'deleted' => 1
                ],
                'sample_data' => $allData,
                'total_records' => count($allData)
            ];

        } catch (\Exception $e) {
            return [
                'database' => 'MySQL',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Perform PostgreSQL CRUD operations using direct PDO (Laravel ports)
     */
    private function performPostgreSQLCrud(): array
    {
        try {
            $pdo = new \PDO('pgsql:host=127.0.0.1;port=5437;dbname=laravel_app_db', 'postgres', 'postgrespassword');
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            // Create tables if not exist
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS users (
                    id SERIAL PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) UNIQUE NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

            // Insert 2 randomized records
            $user1 = ['name' => 'Laravel PG User ' . rand(1000, 9999), 'email' => $this->randomEmail('laravel_postgres')];
            $user2 = ['name' => 'Laravel PG User ' . rand(1000, 9999), 'email' => $this->randomEmail('laravel_postgres')];

            $stmt = $pdo->prepare("INSERT INTO users (name, email) VALUES (?, ?) RETURNING id");
            $stmt->execute([$user1['name'], $user1['email']]);
            $insertId1 = $stmt->fetchColumn();

            $stmt->execute([$user2['name'], $user2['email']]);
            $insertId2 = $stmt->fetchColumn();

            // Update 1 record
            $stmt = $pdo->prepare("UPDATE users SET name = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$user1['name'] . ' Updated', $insertId1]);

            // Delete 1 record
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$insertId2]);

            // Get all data
            $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 10");
            $allData = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            return [
                'database' => 'PostgreSQL',
                'operations' => [
                    'inserted' => 2,
                    'updated' => 1,
                    'deleted' => 1
                ],
                'sample_data' => $allData,
                'total_records' => count($allData)
            ];

        } catch (\Exception $e) {
            return [
                'database' => 'PostgreSQL',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate random email
     */
    private function randomEmail(string $prefix = 'user'): string
    {
        return $prefix . '_' . rand(1000, 9999) . '_' . time() . '@example.com';
    }

    /**
     * Generate application-specific queue name
     * Format: laravel_{php_version}_{web_server}
     */
    private function getQueueName(): string
    {
        $phpVersion = str_replace('.', '', phpversion()); // e.g., 84 for 8.4
        $webServer = $this->getWebServerType();

        return sprintf('laravel_%s_%s', $phpVersion, $webServer);
    }

    /**
     * Get web server type for queue naming
     */
    private function getWebServerType(): string
    {
        $sapi = php_sapi_name();
        $server = $_SERVER['SERVER_SOFTWARE'] ?? '';

        if ($sapi === 'cli-server') {
            return 'php_builtin';
        } elseif (strpos($server, 'Apache') !== false) {
            if (function_exists('apache_get_modules') && in_array('mod_php', apache_get_modules())) {
                return 'apache_modphp';
            } else {
                return 'apache_fpm';
            }
        } elseif (strpos($server, 'nginx') !== false) {
            return 'nginx_fpm';
        } else {
            return 'unknown';
        }
    }
}
