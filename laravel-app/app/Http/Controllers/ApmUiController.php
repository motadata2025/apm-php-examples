<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Post;
use Faker\Factory as Faker;
use Exception;

class ApmUiController extends Controller
{
    private $faker;
    
    public function __construct()
    {
        $this->faker = Faker::create();
    }

    /**
     * Display the main UI page
     */
    public function index()
    {
        return view('apm-ui', [
            'app_type' => 'Laravel',
            'php_version' => phpversion(),
            'web_server' => 'php_cli'
        ]);
    }

    /**
     * Call external API endpoint
     */
    public function externalApi()
    {
        try {
            $startTime = microtime(true);
            $apiUrl = env('EXTERNAL_API_URL');
            
            if (!$apiUrl) {
                return response()->json([
                    'ok' => false,
                    'details' => ['error' => 'EXTERNAL_API_URL not configured']
                ]);
            }

            $response = Http::timeout(20)->get($apiUrl);
            $endTime = microtime(true);
            $elapsedMs = round(($endTime - $startTime) * 1000, 2);
            
            $responseData = $response->json();
            $firstLevelKeys = $responseData ? array_keys($responseData) : [];
            
            return response()->json([
                'ok' => true,
                'details' => [
                    'status' => $response->status(),
                    'response_size' => strlen($response->body()),
                    'elapsed_ms' => $elapsedMs,
                    'first_level_keys' => $firstLevelKeys
                ]
            ]);
            
        } catch (Exception $e) {
            $this->logError('external-api', $e->getMessage());
            return response()->json([
                'ok' => false,
                'details' => ['error' => $e->getMessage()]
            ]);
        }
    }

    /**
     * Check database connections
     */
    public function dbCheck()
    {
        $results = [
            'mysql' => $this->checkDbConnection('mysql'),
            'pg' => $this->checkDbConnection('pgsql')
        ];
        
        return response()->json([
            'ok' => $results['mysql']['ok'] && $results['pg']['ok'],
            'details' => $results
        ]);
    }

    /**
     * Perform CRUD operations on both databases
     */
    public function dbCrud()
    {
        try {
            $mysqlResult = $this->performCrudOperations('mysql');
            $pgResult = $this->performCrudOperations('pgsql');
            
            return response()->json([
                'ok' => $mysqlResult['ok'] && $pgResult['ok'],
                'details' => [
                    'mysql' => $mysqlResult,
                    'pg' => $pgResult
                ]
            ]);
            
        } catch (Exception $e) {
            $this->logError('db-crud', $e->getMessage());
            return response()->json([
                'ok' => false,
                'details' => ['error' => $e->getMessage()]
            ]);
        }
    }

    /**
     * Insert 3 random values into Redis queue
     */
    public function redisInsertBulk()
    {
        try {
            $queueName = $this->getQueueName();
            $redis = $this->getRedisClient();
            
            $messages = [];
            for ($i = 0; $i < 3; $i++) {
                $message = json_encode([
                    'id' => uniqid(),
                    'timestamp' => now()->toISOString(),
                    'payload' => $this->faker->sentence()
                ]);
                $messages[] = $message;
                $redis->lpush($queueName, $message);
            }
            
            $queueLength = $redis->llen($queueName);
            
            return response()->json([
                'ok' => true,
                'queue_name' => $queueName,
                'length_after' => $queueLength,
                'inserted_messages' => $messages
            ]);
            
        } catch (Exception $e) {
            $this->logError('redis-insert-bulk', $e->getMessage());
            return response()->json([
                'ok' => false,
                'details' => ['error' => $e->getMessage()]
            ]);
        }
    }

    /**
     * Insert 1 random value into Redis queue
     */
    public function redisInsertSingle()
    {
        try {
            $queueName = $this->getQueueName();
            $redis = $this->getRedisClient();
            
            $message = json_encode([
                'id' => uniqid(),
                'timestamp' => now()->toISOString(),
                'payload' => $this->faker->sentence()
            ]);
            
            $redis->lpush($queueName, $message);
            $queueLength = $redis->llen($queueName);
            
            return response()->json([
                'ok' => true,
                'queue_name' => $queueName,
                'length_after' => $queueLength,
                'inserted_message' => $message
            ]);
            
        } catch (Exception $e) {
            $this->logError('redis-insert-single', $e->getMessage());
            return response()->json([
                'ok' => false,
                'details' => ['error' => $e->getMessage()]
            ]);
        }
    }

    /**
     * Read single message from Redis queue
     */
    public function redisReadOne()
    {
        try {
            $queueName = $this->getQueueName();
            $redis = $this->getRedisClient();
            
            $message = $redis->rpop($queueName);
            $queueLength = $redis->llen($queueName);
            
            return response()->json([
                'ok' => true,
                'queue_name' => $queueName,
                'length_after' => $queueLength,
                'popped_message' => $message
            ]);
            
        } catch (Exception $e) {
            $this->logError('redis-read-one', $e->getMessage());
            return response()->json([
                'ok' => false,
                'details' => ['error' => $e->getMessage()]
            ]);
        }
    }

    /**
     * Clear Redis queue
     */
    public function redisClear()
    {
        try {
            $queueName = $this->getQueueName();
            $redis = $this->getRedisClient();
            
            $clearedCount = $redis->llen($queueName);
            $redis->del($queueName);
            
            return response()->json([
                'ok' => true,
                'queue_name' => $queueName,
                'length_after' => 0,
                'cleared_count' => $clearedCount
            ]);
            
        } catch (Exception $e) {
            $this->logError('redis-clear', $e->getMessage());
            return response()->json([
                'ok' => false,
                'details' => ['error' => $e->getMessage()]
            ]);
        }
    }

    /**
     * Check database connection
     */
    private function checkDbConnection($dbType)
    {
        try {
            $connection = $this->createDbConnection($dbType);
            DB::connection($connection)->getPdo();

            return [
                'ok' => true,
                'msg' => "Connected to {$dbType} successfully"
            ];
        } catch (Exception $e) {
            return [
                'ok' => false,
                'msg' => "Failed to connect to {$dbType}: " . $e->getMessage()
            ];
        }
    }

    /**
     * Create dynamic database connection
     */
    private function createDbConnection($dbType)
    {
        $connectionName = $dbType . '_remote';

        if ($dbType === 'mysql') {
            // Handle username mismatch between Docker Compose and .env
            $username = env('DB_MYSQL_USERNAME');
            $password = env('DB_MYSQL_PASSWORD');

            // If the env username doesn't work, try the Docker Compose version
            if ($username === 'laravel_app_user') {
                $username = 'laravel-app_user';
            }
            if ($password === 'laravel_app_password') {
                $password = 'laravel-app_password';
            }

            $config = [
                'driver' => 'mysql',
                'host' => env('DB_MYSQL_HOST'),
                'port' => env('DB_MYSQL_PORT'),
                'database' => env('DB_MYSQL_DATABASE'),
                'username' => $username,
                'password' => $password,
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => true,
                'engine' => null,
            ];
        } else {
            $config = [
                'driver' => 'pgsql',
                'host' => env('DB_PGSQL_HOST'),
                'port' => env('DB_PGSQL_PORT'),
                'database' => env('DB_PGSQL_DATABASE'),
                'username' => env('DB_PGSQL_USERNAME'),
                'password' => env('DB_PGSQL_PASSWORD'),
                'charset' => 'utf8',
                'prefix' => '',
                'schema' => 'public',
                'sslmode' => 'prefer',
            ];
        }

        config(['database.connections.' . $connectionName => $config]);

        return $connectionName;
    }

    /**
     * Perform CRUD operations on a database
     */
    private function performCrudOperations($dbType)
    {
        try {
            $connection = $this->createDbConnection($dbType);

            // Ensure tables exist
            $this->ensureTablesExist($connection, $dbType);

            // Create user with unique email
            $uniqueEmail = $this->faker->unique()->email;
            $userName = $this->faker->name;

            $userId = DB::connection($connection)->table('users')->insertGetId([
                'name' => $userName,
                'email' => $uniqueEmail,
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create post for the user
            $postTitle = $this->faker->sentence;
            $postId = DB::connection($connection)->table('posts')->insertGetId([
                'user_id' => $userId,
                'title' => $postTitle,
                'content' => $this->faker->paragraph,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Read back the records
            $user = DB::connection($connection)->table('users')->find($userId);
            $post = DB::connection($connection)->table('posts')->find($postId);

            // Update user name
            $updatedName = $userName . '_' . time();
            $updatedRows = DB::connection($connection)->table('users')
                ->where('id', $userId)
                ->update(['name' => $updatedName, 'updated_at' => now()]);

            // Delete the post (but keep user)
            $deletedRows = DB::connection($connection)->table('posts')->where('id', $postId)->delete();

            // Get final counts
            $userCount = DB::connection($connection)->table('users')->count();
            $postCount = DB::connection($connection)->table('posts')->count();

            return [
                'ok' => true,
                'created_user_id' => $userId,
                'created_post_id' => $postId,
                'updated_rows' => $updatedRows,
                'deleted_rows' => $deletedRows,
                'final_user_count' => $userCount,
                'final_post_count' => $postCount
            ];

        } catch (Exception $e) {
            $this->logError('db-crud-' . $dbType, $e->getMessage());
            return [
                'ok' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Ensure database tables exist
     */
    private function ensureTablesExist($connection, $dbType)
    {
        $schema = DB::connection($connection)->getSchemaBuilder();

        // Check if users table exists, create if not
        if (!$schema->hasTable('users')) {
            $schema->create('users', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
            });
        } else {
            // Table exists, check if it has the columns we need
            if (!$schema->hasColumn('users', 'email_verified_at')) {
                $schema->table('users', function ($table) {
                    $table->timestamp('email_verified_at')->nullable();
                });
            }
            if (!$schema->hasColumn('users', 'password')) {
                $schema->table('users', function ($table) {
                    $table->string('password')->default('');
                });
            }
            if (!$schema->hasColumn('users', 'remember_token')) {
                $schema->table('users', function ($table) {
                    $table->rememberToken();
                });
            }
        }

        // Check if posts table exists, create if not
        if (!$schema->hasTable('posts')) {
            $schema->create('posts', function ($table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('title');
                $table->text('content');
                $table->timestamps();

                // Add foreign key constraint if users table exists
                if ($table->getConnection()->getSchemaBuilder()->hasTable('users')) {
                    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                }
            });
        }
    }

    /**
     * Get Redis queue name
     */
    private function getQueueName()
    {
        $appName = env('APP_NAME', 'laravel-app');
        $phpVersion = str_replace('.', '_', phpversion());
        return $appName . '_' . $phpVersion;
    }

    /**
     * Get Redis client
     */
    private function getRedisClient()
    {
        try {
            // Try Laravel's Redis facade first
            return Redis::connection();
        } catch (Exception $e) {
            // Fallback to direct Redis connection
            $host = env('REDIS_HOST', '127.0.0.1');
            $port = env('REDIS_PORT', 6379);
            $password = env('REDIS_PASSWORD', null);

            if (class_exists('\Predis\Client')) {
                $config = ['host' => $host, 'port' => $port];
                if ($password) {
                    $config['password'] = $password;
                }
                return new \Predis\Client($config);
            } elseif (extension_loaded('redis')) {
                $redis = new \Redis();
                $redis->connect($host, $port);
                if ($password) {
                    $redis->auth($password);
                }
                return $redis;
            } else {
                throw new Exception('No Redis client available. Install predis/predis or php-redis extension.');
            }
        }
    }

    /**
     * Log error to file
     */
    private function logError($operation, $message)
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $logFile = base_path("augment/logs/{$operation}-{$timestamp}.log");

        $logContent = [
            'timestamp' => now()->toISOString(),
            'operation' => $operation,
            'error' => $message,
            'php_version' => phpversion(),
            'laravel_version' => app()->version()
        ];

        file_put_contents($logFile, json_encode($logContent, JSON_PRETTY_PRINT));
        Log::error("APM UI Error in {$operation}: {$message}");
    }
}
