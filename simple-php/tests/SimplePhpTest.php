<?php

namespace SimplePhp\Tests;

use PHPUnit\Framework\TestCase;
use Shared\Utils\DatabaseConnection;
use Shared\Utils\UserModel;
use Shared\Utils\ApiClient;
use Shared\Utils\QueueManager;

class SimplePhpTest extends TestCase
{
    public function testPhpVersion()
    {
        $this->assertGreaterThanOrEqual('8.1', phpversion());
    }

    public function testDatabaseConnectionUtility()
    {
        $this->assertTrue(class_exists('Shared\Utils\DatabaseConnection'));
        $this->assertTrue(method_exists('Shared\Utils\DatabaseConnection', 'randomEmail'));

        $email = DatabaseConnection::randomEmail('test');
        $this->assertStringContainsString('test', $email);
        $this->assertStringContainsString('@example.com', $email);
    }

    public function testUserModelExists()
    {
        $this->assertTrue(class_exists('Shared\Utils\UserModel'));

        $userModel = new UserModel();
        $this->assertInstanceOf(UserModel::class, $userModel);
    }

    public function testApiClientExists()
    {
        $this->assertTrue(class_exists('Shared\Utils\ApiClient'));

        $apiClient = new ApiClient();
        $this->assertInstanceOf(ApiClient::class, $apiClient);
    }

    public function testQueueManagerExists()
    {
        $this->assertTrue(class_exists('Shared\Utils\QueueManager'));

        $queueManager = new QueueManager();
        $this->assertInstanceOf(QueueManager::class, $queueManager);
    }

    public function testIndexPageExists()
    {
        $indexPath = __DIR__ . '/../public/index.php';
        $this->assertFileExists($indexPath);

        $content = file_get_contents($indexPath);
        $this->assertStringContainsString('Simple PHP Application', $content);
        $this->assertStringContainsString('APM Integration Example', $content);
    }

    public function testComposerJsonExists()
    {
        $composerPath = __DIR__ . '/../composer.json';
        $this->assertFileExists($composerPath);

        $composer = json_decode(file_get_contents($composerPath), true);
        $this->assertEquals('apm-php-examples/simple-php', $composer['name']);
        $this->assertArrayHasKey('require', $composer);
        $this->assertArrayHasKey('autoload', $composer);
    }

    public function testDockerfileExists()
    {
        $dockerfilePath = __DIR__ . '/../Dockerfile';
        $this->assertFileExists($dockerfilePath);

        $content = file_get_contents($dockerfilePath);
        $this->assertStringContainsString('FROM php:', $content);
        $this->assertStringContainsString('apache', $content);
    }

    public function testMakefileExists()
    {
        $makefilePath = __DIR__ . '/../Makefile';
        $this->assertFileExists($makefilePath);

        $content = file_get_contents($makefilePath);
        $this->assertStringContainsString('setup', $content);
        $this->assertStringContainsString('start', $content);
        $this->assertStringContainsString('test', $content);
    }
}