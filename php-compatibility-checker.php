<?php
/**
 * PHP Version Compatibility Checker
 * Purpose: Verify PHP 8.1-8.4 compatibility for all APM applications
 */

class PHPCompatibilityChecker
{
    private const SUPPORTED_VERSIONS = ['8.1', '8.2', '8.3', '8.4'];
    private const REQUIRED_EXTENSIONS = [
        'core' => ['json', 'mbstring', 'curl', 'openssl'],
        'database' => ['pdo', 'pdo_mysql', 'pdo_sqlite'],
        'cache' => ['redis'],
        'optional' => ['gd', 'zip', 'xml']
    ];

    public function checkCompatibility(): array
    {
        $results = [
            'php_version' => $this->checkPHPVersion(),
            'thread_safety' => $this->checkThreadSafety(),
            'extensions' => $this->checkExtensions(),
            'applications' => $this->checkApplications(),
            'overall_status' => 'unknown'
        ];

        $results['overall_status'] = $this->determineOverallStatus($results);
        return $results;
    }

    private function checkPHPVersion(): array
    {
        $version = PHP_VERSION;
        $majorMinor = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
        
        return [
            'full_version' => $version,
            'major_minor' => $majorMinor,
            'supported' => in_array($majorMinor, self::SUPPORTED_VERSIONS),
            'status' => in_array($majorMinor, self::SUPPORTED_VERSIONS) ? 'compatible' : 'unsupported',
            'message' => $this->getVersionMessage($majorMinor)
        ];
    }

    private function checkThreadSafety(): array
    {
        $isThreadSafe = defined('ZEND_THREAD_SAFE') && ZEND_THREAD_SAFE;
        
        return [
            'is_thread_safe' => $isThreadSafe,
            'build_type' => $isThreadSafe ? 'ZTS' : 'NTS',
            'status' => 'compatible', // Both NTS and ZTS are supported
            'message' => $isThreadSafe ? 
                'Zend Thread Safe (ZTS) build detected - Compatible' : 
                'Non-Thread Safe (NTS) build detected - Compatible'
        ];
    }

    private function checkExtensions(): array
    {
        $results = [];
        
        foreach (self::REQUIRED_EXTENSIONS as $category => $extensions) {
            $results[$category] = [];
            foreach ($extensions as $extension) {
                $loaded = extension_loaded($extension);
                $results[$category][$extension] = [
                    'loaded' => $loaded,
                    'status' => $loaded ? 'available' : 'missing',
                    'required' => $category !== 'optional'
                ];
            }
        }
        
        return $results;
    }

    private function checkApplications(): array
    {
        $applications = ['simple-php', 'laravel-app', 'symfony-app', 'slim-framework', 'codeigniter-app'];
        $results = [];
        
        foreach ($applications as $app) {
            $results[$app] = $this->checkSingleApplication($app);
        }
        
        return $results;
    }

    private function checkSingleApplication(string $app): array
    {
        $appPath = __DIR__ . '/' . $app;
        $publicPath = $appPath . '/public';
        $indexPath = $publicPath . '/index.php';
        
        $result = [
            'exists' => is_dir($appPath),
            'public_dir' => is_dir($publicPath),
            'index_file' => is_file($indexPath),
            'syntax_valid' => false,
            'status' => 'unknown'
        ];
        
        if ($result['index_file']) {
            // Check PHP syntax
            $output = [];
            $returnCode = 0;
            exec("php -l \"$indexPath\" 2>&1", $output, $returnCode);
            $result['syntax_valid'] = $returnCode === 0;
        }
        
        $result['status'] = $this->determineAppStatus($result);
        return $result;
    }

    private function getVersionMessage(string $version): string
    {
        if (in_array($version, self::SUPPORTED_VERSIONS)) {
            return "PHP $version is fully supported";
        }
        
        if (version_compare($version, '8.1', '<')) {
            return "PHP $version is too old. Minimum required: PHP 8.1";
        }
        
        if (version_compare($version, '8.4', '>')) {
            return "PHP $version is newer than tested versions. May work but not guaranteed";
        }
        
        return "PHP $version compatibility unknown";
    }

    private function determineAppStatus(array $result): string
    {
        if (!$result['exists']) return 'missing';
        if (!$result['public_dir']) return 'invalid_structure';
        if (!$result['index_file']) return 'missing_index';
        if (!$result['syntax_valid']) return 'syntax_error';
        return 'compatible';
    }

    private function determineOverallStatus(array $results): string
    {
        if (!$results['php_version']['supported']) {
            return 'incompatible';
        }
        
        // Check for missing required extensions
        foreach (['core', 'database'] as $category) {
            foreach ($results['extensions'][$category] as $ext => $info) {
                if ($info['required'] && !$info['loaded']) {
                    return 'missing_extensions';
                }
            }
        }
        
        // Check application status
        foreach ($results['applications'] as $app => $info) {
            if ($info['status'] !== 'compatible') {
                return 'application_issues';
            }
        }
        
        return 'fully_compatible';
    }

    public function displayResults(array $results): void
    {
        echo "🔧 PHP COMPATIBILITY CHECK RESULTS\n";
        echo "==================================\n\n";
        
        // PHP Version
        echo "📋 PHP Version Information:\n";
        echo "  Version: {$results['php_version']['full_version']}\n";
        echo "  Status: " . ($results['php_version']['supported'] ? '✅' : '❌') . " {$results['php_version']['message']}\n";
        echo "  Build: {$results['thread_safety']['build_type']} - {$results['thread_safety']['message']}\n\n";
        
        // Extensions
        echo "📦 PHP Extensions:\n";
        foreach ($results['extensions'] as $category => $extensions) {
            echo "  $category:\n";
            foreach ($extensions as $name => $info) {
                $icon = $info['loaded'] ? '✅' : ($info['required'] ? '❌' : '⚠️');
                echo "    $icon $name - {$info['status']}\n";
            }
        }
        echo "\n";
        
        // Applications
        echo "🚀 Application Compatibility:\n";
        foreach ($results['applications'] as $app => $info) {
            $icon = $info['status'] === 'compatible' ? '✅' : '❌';
            echo "  $icon $app - {$info['status']}\n";
        }
        echo "\n";
        
        // Overall Status
        $statusIcon = $results['overall_status'] === 'fully_compatible' ? '✅' : '❌';
        echo "🎯 Overall Status: $statusIcon {$results['overall_status']}\n";
    }
}

// Run the compatibility check
$checker = new PHPCompatibilityChecker();
$results = $checker->checkCompatibility();
$checker->displayResults($results);

// Exit with appropriate code
exit($results['overall_status'] === 'fully_compatible' ? 0 : 1);
?>
