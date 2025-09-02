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
        'version' => \Slim\App::VERSION,
    ]
];

echo json_encode($metrics, JSON_PRETTY_PRINT);
