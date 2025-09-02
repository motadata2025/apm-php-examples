
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
