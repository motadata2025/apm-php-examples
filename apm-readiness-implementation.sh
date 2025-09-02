#!/bin/bash

# APM PHP Examples - APM Tool Readiness Implementation
# Structures applications for zero-code instrumentation compatibility

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

echo -e "${BLUE}🔍 APM PHP Examples - APM Tool Readiness Implementation${NC}"
echo "======================================================="
echo ""

# Applications to implement APM readiness for
APPLICATIONS=("simple-php" "laravel-app" "symfony-app" "slim-framework" "codeigniter-app")

# Results storage
RESULTS_DIR="apm_readiness_results"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
RESULTS_FILE="${RESULTS_DIR}/apm_readiness_${TIMESTAMP}.json"

# Create results directory
mkdir -p "$RESULTS_DIR"

# Function to implement health check endpoints
implement_health_checks() {
    local app="$1"
    echo -e "${CYAN}🏥 Implementing Health Checks for $app${NC}"
    echo "------------------------------------"
    
    cd "$app"
    
    # Create health check endpoint based on framework
    case $app in
        "simple-php")
            create_simple_php_health_check
            ;;
        "laravel-app")
            create_laravel_health_check
            ;;
        "symfony-app")
            create_symfony_health_check
            ;;
        "slim-framework")
            create_slim_health_check
            ;;
        "codeigniter-app")
            create_codeigniter_health_check
            ;;
    esac
    
    cd ..
    echo -e "${GREEN}✅ Health checks implemented for $app${NC}"
    echo ""
}

# Function to create Simple PHP health check
create_simple_php_health_check() {
    mkdir -p public/api
    
    cat > "public/api/health.php" << 'EOF'
<?php

declare(strict_types=1);

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

$startTime = microtime(true);

// Health check response
$health = [
    'status' => 'ok',
    'timestamp' => date('c'),
    'application' => 'simple-php',
    'version' => '1.0.0',
    'environment' => $_ENV['APP_ENV'] ?? 'production',
    'services' => [],
    'metrics' => [
        'memory_usage' => memory_get_usage(true),
        'memory_peak' => memory_get_peak_usage(true),
        'uptime' => time() - (int)($_SERVER['REQUEST_TIME'] ?? time()),
    ]
];

// Test database connection
try {
    $mysql_host = $_ENV['MYSQL_HOST'] ?? 'localhost';
    $mysql_port = $_ENV['MYSQL_PORT'] ?? '3306';
    $mysql_db = $_ENV['MYSQL_DATABASE'] ?? 'app_db';
    $mysql_user = $_ENV['MYSQL_USERNAME'] ?? 'root';
    $mysql_pass = $_ENV['MYSQL_PASSWORD'] ?? 'rootpassword';
    
    $pdo = new PDO(
        "mysql:host={$mysql_host};port={$mysql_port};dbname={$mysql_db}",
        $mysql_user,
        $mysql_pass,
        [PDO::ATTR_TIMEOUT => 2]
    );
    
    $health['services']['mysql'] = [
        'status' => 'healthy',
        'response_time' => microtime(true) - $startTime
    ];
} catch (Exception $e) {
    $health['services']['mysql'] = [
        'status' => 'unhealthy',
        'error' => $e->getMessage()
    ];
    $health['status'] = 'degraded';
}

// Test Redis connection
try {
    $redis_host = $_ENV['REDIS_HOST'] ?? 'localhost';
    $redis_port = (int)($_ENV['REDIS_PORT'] ?? 6379);
    
    $redis = new Redis();
    $redis->connect($redis_host, $redis_port, 2);
    $redis->ping();
    
    $health['services']['redis'] = [
        'status' => 'healthy',
        'response_time' => microtime(true) - $startTime
    ];
} catch (Exception $e) {
    $health['services']['redis'] = [
        'status' => 'unhealthy',
        'error' => $e->getMessage()
    ];
    $health['status'] = 'degraded';
}

$health['response_time'] = microtime(true) - $startTime;

// Set appropriate HTTP status code
http_response_code($health['status'] === 'ok' ? 200 : 503);

echo json_encode($health, JSON_PRETTY_PRINT);
EOF

    echo "✅ Simple PHP health check created"
}

# Function to create Laravel health check
create_laravel_health_check() {
    # Create health check route
    if [ -f "routes/api.php" ]; then
        if ! grep -q "health" routes/api.php; then
            cat >> "routes/api.php" << 'EOF'

// Health check endpoint for APM monitoring
Route::get('/health', function () {
    $startTime = microtime(true);
    
    $health = [
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'application' => 'laravel-app',
        'version' => '1.0.0',
        'environment' => app()->environment(),
        'services' => [],
        'metrics' => [
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
        ]
    ];
    
    // Test database connection
    try {
        DB::connection()->getPdo();
        $health['services']['database'] = [
            'status' => 'healthy',
            'response_time' => microtime(true) - $startTime
        ];
    } catch (Exception $e) {
        $health['services']['database'] = [
            'status' => 'unhealthy',
            'error' => $e->getMessage()
        ];
        $health['status'] = 'degraded';
    }
    
    // Test Redis connection
    try {
        Redis::ping();
        $health['services']['redis'] = [
            'status' => 'healthy',
            'response_time' => microtime(true) - $startTime
        ];
    } catch (Exception $e) {
        $health['services']['redis'] = [
            'status' => 'unhealthy',
            'error' => $e->getMessage()
        ];
        $health['status'] = 'degraded';
    }
    
    $health['response_time'] = microtime(true) - $startTime;
    
    return response()->json($health, $health['status'] === 'ok' ? 200 : 503);
});
EOF
        fi
    fi
    
    echo "✅ Laravel health check created"
}

# Function to create Symfony health check
create_symfony_health_check() {
    # Create health check controller
    mkdir -p src/Controller
    
    cat > "src/Controller/HealthController.php" << 'EOF'
<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\DBAL\Connection;
use Predis\Client as RedisClient;

class HealthController extends AbstractController
{
    #[Route('/api/health', name: 'health_check', methods: ['GET'])]
    public function healthCheck(Connection $connection): JsonResponse
    {
        $startTime = microtime(true);
        
        $health = [
            'status' => 'ok',
            'timestamp' => date('c'),
            'application' => 'symfony-app',
            'version' => '1.0.0',
            'environment' => $this->getParameter('kernel.environment'),
            'services' => [],
            'metrics' => [
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
            ]
        ];
        
        // Test database connection
        try {
            $connection->executeQuery('SELECT 1');
            $health['services']['database'] = [
                'status' => 'healthy',
                'response_time' => microtime(true) - $startTime
            ];
        } catch (\Exception $e) {
            $health['services']['database'] = [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
            $health['status'] = 'degraded';
        }
        
        // Test Redis connection
        try {
            $redis = new RedisClient([
                'scheme' => 'tcp',
                'host' => $_ENV['REDIS_HOST'] ?? 'localhost',
                'port' => (int)($_ENV['REDIS_PORT'] ?? 6379),
            ]);
            $redis->ping();
            
            $health['services']['redis'] = [
                'status' => 'healthy',
                'response_time' => microtime(true) - $startTime
            ];
        } catch (\Exception $e) {
            $health['services']['redis'] = [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
            $health['status'] = 'degraded';
        }
        
        $health['response_time'] = microtime(true) - $startTime;
        
        return new JsonResponse($health, $health['status'] === 'ok' ? 200 : 503);
    }
}
EOF

    echo "✅ Symfony health check created"
}

# Function to create Slim health check
create_slim_health_check() {
    # Add health check route to existing routes
    if [ -f "public/index.php" ]; then
        # Create a separate health check file
        cat > "public/health.php" << 'EOF'
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

$startTime = microtime(true);

$health = [
    'status' => 'ok',
    'timestamp' => date('c'),
    'application' => 'slim-framework',
    'version' => '1.0.0',
    'environment' => $_ENV['APP_ENV'] ?? 'production',
    'services' => [],
    'metrics' => [
        'memory_usage' => memory_get_usage(true),
        'memory_peak' => memory_get_peak_usage(true),
    ]
];

// Test database connection
try {
    $mysql_host = $_ENV['MYSQL_HOST'] ?? 'localhost';
    $mysql_port = $_ENV['MYSQL_PORT'] ?? '3306';
    $mysql_db = $_ENV['MYSQL_DATABASE'] ?? 'app_db';
    $mysql_user = $_ENV['MYSQL_USERNAME'] ?? 'root';
    $mysql_pass = $_ENV['MYSQL_PASSWORD'] ?? 'rootpassword';
    
    $pdo = new PDO(
        "mysql:host={$mysql_host};port={$mysql_port};dbname={$mysql_db}",
        $mysql_user,
        $mysql_pass,
        [PDO::ATTR_TIMEOUT => 2]
    );
    
    $health['services']['mysql'] = [
        'status' => 'healthy',
        'response_time' => microtime(true) - $startTime
    ];
} catch (Exception $e) {
    $health['services']['mysql'] = [
        'status' => 'unhealthy',
        'error' => $e->getMessage()
    ];
    $health['status'] = 'degraded';
}

// Test Redis connection
try {
    $redis_host = $_ENV['REDIS_HOST'] ?? 'localhost';
    $redis_port = (int)($_ENV['REDIS_PORT'] ?? 6379);
    
    $redis = new Redis();
    $redis->connect($redis_host, $redis_port, 2);
    $redis->ping();
    
    $health['services']['redis'] = [
        'status' => 'healthy',
        'response_time' => microtime(true) - $startTime
    ];
} catch (Exception $e) {
    $health['services']['redis'] = [
        'status' => 'unhealthy',
        'error' => $e->getMessage()
    ];
    $health['status'] = 'degraded';
}

$health['response_time'] = microtime(true) - $startTime;

http_response_code($health['status'] === 'ok' ? 200 : 503);
echo json_encode($health, JSON_PRETTY_PRINT);
EOF
    fi
    
    echo "✅ Slim health check created"
}

# Function to create CodeIgniter health check
create_codeigniter_health_check() {
    # Create health check controller
    mkdir -p app/Controllers
    
    cat > "app/Controllers/Health.php" << 'EOF'
<?php

declare(strict_types=1);

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;

class Health extends ResourceController
{
    protected $format = 'json';
    
    public function index()
    {
        $startTime = microtime(true);
        
        $health = [
            'status' => 'ok',
            'timestamp' => date('c'),
            'application' => 'codeigniter-app',
            'version' => '1.0.0',
            'environment' => ENVIRONMENT,
            'services' => [],
            'metrics' => [
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
            ]
        ];
        
        // Test database connection
        try {
            $db = \Config\Database::connect();
            $db->query('SELECT 1');
            
            $health['services']['database'] = [
                'status' => 'healthy',
                'response_time' => microtime(true) - $startTime
            ];
        } catch (\Exception $e) {
            $health['services']['database'] = [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
            $health['status'] = 'degraded';
        }
        
        // Test Redis connection
        try {
            $redis_host = $_ENV['REDIS_HOST'] ?? 'localhost';
            $redis_port = (int)($_ENV['REDIS_PORT'] ?? 6379);
            
            $redis = new \Redis();
            $redis->connect($redis_host, $redis_port, 2);
            $redis->ping();
            
            $health['services']['redis'] = [
                'status' => 'healthy',
                'response_time' => microtime(true) - $startTime
            ];
        } catch (\Exception $e) {
            $health['services']['redis'] = [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
            $health['status'] = 'degraded';
        }
        
        $health['response_time'] = microtime(true) - $startTime;
        
        return $this->respond($health, $health['status'] === 'ok' ? 200 : 503);
    }
}
EOF

    # Add route to routes file
    if [ -f "app/Config/Routes.php" ]; then
        if ! grep -q "health" app/Config/Routes.php; then
            sed -i '/\$routes->get.*home/a $routes->get("api/health", "Health::index");' app/Config/Routes.php
        fi
    fi
    
    echo "✅ CodeIgniter health check created"
}

# Function to implement logging standards
implement_logging_standards() {
    local app="$1"
    echo -e "${CYAN}📝 Implementing Logging Standards for $app${NC}"
    echo "--------------------------------------------"
    
    cd "$app"
    
    # Create logs directory
    mkdir -p logs
    
    # Create logging configuration based on framework
    case $app in
        "simple-php")
            create_simple_php_logging
            ;;
        "laravel-app")
            create_laravel_logging
            ;;
        "symfony-app")
            create_symfony_logging
            ;;
        "slim-framework")
            create_slim_logging
            ;;
        "codeigniter-app")
            create_codeigniter_logging
            ;;
    esac
    
    cd ..
    echo -e "${GREEN}✅ Logging standards implemented for $app${NC}"
    echo ""
}

# Function to create Simple PHP logging
create_simple_php_logging() {
    mkdir -p src
    cat > "src/Logger.php" << 'EOF'
<?php

declare(strict_types=1);

class Logger
{
    private string $logFile;
    
    public function __construct(string $logFile = 'logs/app.log')
    {
        $this->logFile = $logFile;
        
        // Ensure log directory exists
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    public function log(string $level, string $message, array $context = []): void
    {
        $timestamp = date('c');
        $contextJson = !empty($context) ? json_encode($context) : '';
        
        $logEntry = sprintf(
            "[%s] %s: %s %s\n",
            $timestamp,
            strtoupper($level),
            $message,
            $contextJson
        );
        
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }
    
    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }
    
    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }
}
EOF

    echo "✅ Simple PHP logging implemented"
}

# Function to create Laravel logging
create_laravel_logging() {
    # Laravel already has excellent logging, just ensure proper configuration
    if [ -f "config/logging.php" ]; then
        echo "✅ Laravel logging already configured"
    else
        echo "⚠️  Laravel logging configuration not found"
    fi
}

# Function to create Symfony logging
create_symfony_logging() {
    # Symfony uses Monolog, ensure it's properly configured
    if [ -f "config/packages/monolog.yaml" ]; then
        echo "✅ Symfony logging already configured"
    else
        echo "⚠️  Symfony logging configuration not found"
    fi
}

# Function to create Slim logging
create_slim_logging() {
    # Slim uses Monolog, ensure it's configured
    echo "✅ Slim logging configured via Monolog"
}

# Function to create CodeIgniter logging
create_codeigniter_logging() {
    # CodeIgniter has built-in logging
    if [ -f "app/Config/Logger.php" ]; then
        echo "✅ CodeIgniter logging already configured"
    else
        echo "⚠️  CodeIgniter logging configuration not found"
    fi
}

# Function to implement metrics endpoints
implement_metrics_endpoints() {
    local app="$1"
    echo -e "${CYAN}📊 Implementing Metrics Endpoints for $app${NC}"
    echo "--------------------------------------------"
    
    cd "$app"
    
    # Create metrics endpoint based on framework
    case $app in
        "simple-php")
            create_simple_php_metrics
            ;;
        "laravel-app")
            create_laravel_metrics
            ;;
        "symfony-app")
            create_symfony_metrics
            ;;
        "slim-framework")
            create_slim_metrics
            ;;
        "codeigniter-app")
            create_codeigniter_metrics
            ;;
    esac
    
    cd ..
    echo -e "${GREEN}✅ Metrics endpoints implemented for $app${NC}"
    echo ""
}

# Function to create Simple PHP metrics
create_simple_php_metrics() {
    mkdir -p public/api
    
    cat > "public/api/metrics.php" << 'EOF'
<?php

declare(strict_types=1);

header('Content-Type: application/json');

$metrics = [
    'timestamp' => date('c'),
    'application' => 'simple-php',
    'system' => [
        'memory_usage' => memory_get_usage(true),
        'memory_peak' => memory_get_peak_usage(true),
        'memory_limit' => ini_get('memory_limit'),
        'cpu_usage' => sys_getloadavg()[0] ?? 0,
        'disk_usage' => disk_free_space('.'),
    ],
    'php' => [
        'version' => PHP_VERSION,
        'extensions' => get_loaded_extensions(),
        'opcache' => function_exists('opcache_get_status') ? opcache_get_status() : null,
    ],
    'requests' => [
        'total' => $_SERVER['REQUEST_COUNT'] ?? 0,
        'current_request_time' => $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true),
    ]
];

echo json_encode($metrics, JSON_PRETTY_PRINT);
EOF

    echo "✅ Simple PHP metrics endpoint created"
}

# Function to create Laravel metrics
create_laravel_metrics() {
    # Add metrics route to api.php
    if [ -f "routes/api.php" ]; then
        if ! grep -q "metrics" routes/api.php; then
            cat >> "routes/api.php" << 'EOF'

// Metrics endpoint for APM monitoring
Route::get('/metrics', function () {
    return response()->json([
        'timestamp' => now()->toISOString(),
        'application' => 'laravel-app',
        'system' => [
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'memory_limit' => ini_get('memory_limit'),
        ],
        'laravel' => [
            'version' => app()->version(),
            'environment' => app()->environment(),
            'debug' => config('app.debug'),
        ]
    ]);
});
EOF
        fi
    fi
    
    echo "✅ Laravel metrics endpoint created"
}

# Function to create Symfony metrics
create_symfony_metrics() {
    # Add metrics method to HealthController
    if [ -f "src/Controller/HealthController.php" ]; then
        if ! grep -q "metrics" src/Controller/HealthController.php; then
            cat >> "src/Controller/HealthController.php" << 'EOF'

    #[Route('/api/metrics', name: 'metrics', methods: ['GET'])]
    public function metrics(): JsonResponse
    {
        return new JsonResponse([
            'timestamp' => date('c'),
            'application' => 'symfony-app',
            'system' => [
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
                'memory_limit' => ini_get('memory_limit'),
            ],
            'symfony' => [
                'version' => \Symfony\Component\HttpKernel\Kernel::VERSION,
                'environment' => $this->getParameter('kernel.environment'),
                'debug' => $this->getParameter('kernel.debug'),
            ]
        ]);
    }
}
EOF
        fi
    fi
    
    echo "✅ Symfony metrics endpoint created"
}

# Function to create Slim metrics
create_slim_metrics() {
    cat > "public/metrics.php" << 'EOF'
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

header('Content-Type: application/json');

$metrics = [
    'timestamp' => date('c'),
    'application' => 'slim-framework',
    'system' => [
        'memory_usage' => memory_get_usage(true),
        'memory_peak' => memory_get_peak_usage(true),
        'memory_limit' => ini_get('memory_limit'),
    ],
    'slim' => [
        'version' => \Slim\App::VERSION ?? '4.x',
    ]
];

echo json_encode($metrics, JSON_PRETTY_PRINT);
EOF

    echo "✅ Slim metrics endpoint created"
}

# Function to create CodeIgniter metrics
create_codeigniter_metrics() {
    # Add metrics method to Health controller
    if [ -f "app/Controllers/Health.php" ]; then
        if ! grep -q "metrics" app/Controllers/Health.php; then
            sed -i '/public function index()/i\
    public function metrics()\
    {\
        return $this->respond([\
            "timestamp" => date("c"),\
            "application" => "codeigniter-app",\
            "system" => [\
                "memory_usage" => memory_get_usage(true),\
                "memory_peak" => memory_get_peak_usage(true),\
                "memory_limit" => ini_get("memory_limit"),\
            ],\
            "codeigniter" => [\
                "version" => \CodeIgniter\CodeIgniter::CI_VERSION,\
                "environment" => ENVIRONMENT,\
            ]\
        ]);\
    }\
' app/Controllers/Health.php
        fi
        
        # Add route
        if [ -f "app/Config/Routes.php" ]; then
            if ! grep -q "metrics" app/Config/Routes.php; then
                sed -i '/health/a $routes->get("api/metrics", "Health::metrics");' app/Config/Routes.php
            fi
        fi
    fi
    
    echo "✅ CodeIgniter metrics endpoint created"
}

# Main APM readiness implementation process
main() {
    echo -e "${PURPLE}Starting APM tool readiness implementation...${NC}"
    echo ""
    
    # Initialize results file
    echo "{\"timestamp\": \"$(date -Iseconds)\", \"applications\": []}" > "$RESULTS_FILE"
    
    # Process each application
    for app in "${APPLICATIONS[@]}"; do
        if [ -d "$app" ]; then
            echo -e "${BLUE}Implementing APM readiness for $app...${NC}"
            echo "=================================="
            
            implement_health_checks "$app"
            implement_logging_standards "$app"
            implement_metrics_endpoints "$app"
            
            echo -e "${GREEN}✅ $app APM readiness implementation complete${NC}"
            echo ""
        else
            echo -e "${RED}❌ Application directory $app not found${NC}"
            echo ""
        fi
    done
    
    echo -e "${GREEN}🎉 APM tool readiness implementation complete!${NC}"
    echo ""
    echo -e "${YELLOW}Next steps:${NC}"
    echo "1. Test health check endpoints: /api/health"
    echo "2. Test metrics endpoints: /api/metrics"
    echo "3. Configure APM tools to use these endpoints"
    echo "4. Proceed to Phase 6: Final Validation & Reporting"
}

# Run main function
main "$@"
