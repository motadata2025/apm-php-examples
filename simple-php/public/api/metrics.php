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
