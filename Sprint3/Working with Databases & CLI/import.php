<?php

require 'vendor/autoload.php';

use League\Csv\Reader;
use Dotenv\Dotenv;

// Load env
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$startTime = microtime(true);

// Connect using application user (NOT root)
$pdo = new PDO(
    "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8mb4",
    $_ENV['DB_ROOT_USER'],
    $_ENV['DB_ROOT_PASS'],
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);

// Load CSV
$csv = Reader::from('customers.csv', 'r');
$csv->setHeaderOffset(0);
$csv->setEscape('');

$records = $csv->getRecords();

// Prepare SQL (REMOVED csv_index)
$sql = "
INSERT INTO customers (
    customer_id,
    first_name,
    last_name,
    company,
    city,
    country,
    phone_1,
    phone_2,
    email,
    subscription_date,
    website
)
VALUES (
    :customer_id,
    :first_name,
    :last_name,
    :company,
    :city,
    :country,
    :phone_1,
    :phone_2,
    :email,
    :subscription_date,
    :website
)
";

$stmt = $pdo->prepare($sql);

$pdo->beginTransaction();

$count = 0;

try {

    foreach ($records as $record) {

        if (!filter_var($record['Email'], FILTER_VALIDATE_EMAIL)) {
            continue;
        }

        $date = !empty($record['Subscription Date'])
            ? date('Y-m-d H:i:s', strtotime($record['Subscription Date']))
            : null;

        $stmt->execute([
            ':customer_id'       => trim($record['Customer Id']),
            ':first_name'        => trim($record['First Name']),
            ':last_name'         => trim($record['Last Name']),
            ':company'           => trim($record['Company']),
            ':city'              => trim($record['City']),
            ':country'           => trim($record['Country']),
            ':phone_1'           => trim($record['Phone 1']),
            ':phone_2'           => trim($record['Phone 2']),
            ':email'             => trim($record['Email']),
            ':subscription_date' => $date,
            ':website'           => trim($record['Website']),
        ]);

        $count++;
    }

    $pdo->commit();

} catch (Exception $e) {
    $pdo->rollBack();
    die("Import failed: " . $e->getMessage());
}

$executionTime = microtime(true) - $startTime;

echo "Imported {$count} records successfully.\n";
echo "Execution time: " . round($executionTime, 4) . " seconds.\n";
