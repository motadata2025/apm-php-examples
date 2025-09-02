<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

class DatabaseTest extends TestCase
{
    public function testMySQLConnection(): void
    {
        $this->testDatabaseConnection(self::$mysqlConnection, 'MySQL');
    }
    
    public function testPostgreSQLConnection(): void
    {
        $this->testDatabaseConnection(self::$postgresConnection, 'PostgreSQL');
    }
    
    public function testMySQLTransaction(): void
    {
        $this->testDatabaseTransaction(self::$mysqlConnection);
    }
    
    public function testPostgreSQLTransaction(): void
    {
        $this->testDatabaseTransaction(self::$postgresConnection);
    }
    
    public function testMySQLPerformance(): void
    {
        $this->testDatabasePerformance(self::$mysqlConnection, 'MySQL');
    }
    
    public function testPostgreSQLPerformance(): void
    {
        $this->testDatabasePerformance(self::$postgresConnection, 'PostgreSQL');
    }
}
