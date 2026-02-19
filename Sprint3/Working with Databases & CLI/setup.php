<?php

require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// Load env
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$startTime = microtime(true);

// Connect as root
$dsn = "mysql:host={$_ENV['DB_HOST']};charset=utf8mb4";

$pdo = new PDO(
    $dsn,
    $_ENV['DB_ROOT_USER'],
    $_ENV['DB_ROOT_PASS'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// Create database
$pdo->exec("
    CREATE DATABASE IF NOT EXISTS {$_ENV['DB_NAME']}
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci
");

// Create user
$pdo->exec("
    CREATE USER IF NOT EXISTS '{$_ENV['DB_USER']}'@'localhost'
    IDENTIFIED BY '{$_ENV['DB_PASS']}'
");

// Grant privileges
$pdo->exec("
    GRANT ALL PRIVILEGES
    ON {$_ENV['DB_NAME']}.* 
    TO '{$_ENV['DB_USER']}'@'localhost'
");

$pdo->exec("FLUSH PRIVILEGES");

// Use DB
$pdo->exec("USE {$_ENV['DB_NAME']}");

// Create table (REMOVED csv_index)
$pdo->exec("
    CREATE TABLE IF NOT EXISTS customers (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        customer_id VARCHAR(50) NOT NULL,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        company VARCHAR(150),
        city VARCHAR(100),
        country VARCHAR(100),
        phone_1 VARCHAR(30),
        phone_2 VARCHAR(30),
        email VARCHAR(190) NOT NULL,
        subscription_date DATETIME,
        website VARCHAR(255),

        UNIQUE KEY unique_email (email),
        INDEX idx_customer_id (customer_id),
        INDEX idx_country (country)
    ) ENGINE=InnoDB
");

$executionTime = microtime(true) - $startTime;

echo "Setup completed in " . round($executionTime, 4) . " seconds.\n";
