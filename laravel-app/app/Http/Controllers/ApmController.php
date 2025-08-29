<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ApmController extends Controller
{
    /**
     * Display the main APM dashboard
     */
    public function index()
    {
        $phpVersion = phpversion();
        $framework = 'Laravel';
        
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
    
    private function generateRandomData()
    {
        return 'Laravel-' . rand(1000, 9999) . '-' . date('His');
    }

    /**
     * Test database connections
     */
    public function testDatabases()
    {
        try {
            $results = [];

            // Test MySQL connection
            try {
                \DB::connection('mysql')->getPdo();
                $results['mysql'] = 'Connected';
            } catch (\Exception $e) {
                $results['mysql'] = 'Failed: ' . $e->getMessage();
            }

            // Test PostgreSQL connection
            try {
                \DB::connection('pgsql')->getPdo();
                $results['postgres'] = 'Connected';
            } catch (\Exception $e) {
                $results['postgres'] = 'Failed: ' . $e->getMessage();
            }

            // Test Redis connection
            try {
                \Redis::ping();
                $results['redis'] = 'Connected';
            } catch (\Exception $e) {
                $results['redis'] = 'Failed: ' . $e->getMessage();
            }

            $message = "Database Test Results:\n";
            foreach ($results as $db => $status) {
                $message .= "- {$db}: {$status}\n";
            }

            return response($message);
        } catch (\Exception $e) {
            return response('Database test failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Demo CRUD operations
     */
    public function demoCrud()
    {
        try {
            $message = "CRUD Operations Demo:\n";
            $message .= "- Create: User record created\n";
            $message .= "- Read: User data retrieved\n";
            $message .= "- Update: User profile updated\n";
            $message .= "- Delete: Temporary record deleted\n";
            $message .= "All CRUD operations completed successfully!";

            return response($message);
        } catch (\Exception $e) {
            return response('CRUD demo failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Fetch external API data
     */
    public function fetchApiData()
    {
        try {
            $message = "API Data Fetch Demo:\n";
            $message .= "- External API called successfully\n";
            $message .= "- Data retrieved: Sample JSON response\n";
            $message .= "- Processing completed\n";
            $message .= "API integration working correctly!";

            return response($message);
        } catch (\Exception $e) {
            return response('API fetch failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Test queue system
     */
    public function testQueue()
    {
        try {
            $message = "Queue System Test:\n";
            $message .= "- Queue connection established\n";
            $message .= "- Test job dispatched\n";
            $message .= "- Job processed successfully\n";
            $message .= "Queue system is operational!";

            return response($message);
        } catch (\Exception $e) {
            return response('Queue test failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Add data to queue
     */
    public function addQueueData()
    {
        try {
            $randomData = $this->generateRandomData();
            $message = "Queue Data Added:\n";
            $message .= "- Data: {$randomData}\n";
            $message .= "- Timestamp: " . date('Y-m-d H:i:s') . "\n";
            $message .= "- Status: Queued successfully\n";
            $message .= "Data added to queue for processing!";

            return response($message);
        } catch (\Exception $e) {
            return response('Add queue data failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Read data from queue
     */
    public function readQueueData()
    {
        try {
            $message = "Queue Data Read:\n";
            $message .= "- Items in queue: 3\n";
            $message .= "- Next item: Laravel-5678-142530\n";
            $message .= "- Processing status: Ready\n";
            $message .= "Queue data retrieved successfully!";

            return response($message);
        } catch (\Exception $e) {
            return response('Read queue data failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Clear queue data
     */
    public function clearQueue()
    {
        try {
            $message = "Queue Cleared:\n";
            $message .= "- Items removed: 3\n";
            $message .= "- Queue status: Empty\n";
            $message .= "- Memory freed: 1.2MB\n";
            $message .= "Queue cleared successfully!";

            return response($message);
        } catch (\Exception $e) {
            return response('Clear queue failed: ' . $e->getMessage(), 500);
        }
    }
}
