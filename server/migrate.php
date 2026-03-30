<?php
/**
 * Simple migration runner.
 * Usage: php migrate.php
 *
 * Migrations are SQL files in /migrations/ named like:
 *   20260329_001_create_users.sql
 *
 * Executed migrations are tracked in a `migrations` table.
 */

$config = require __DIR__ . '/config.php';
$db = $config['db'];

$pdo = new PDO(
    "mysql:host={$db['host']};dbname={$db['name']};charset=utf8mb4",
    $db['user'],
    $db['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// Create migrations table if it doesn't exist
$pdo->exec("
    CREATE TABLE IF NOT EXISTS migrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        filename VARCHAR(255) NOT NULL UNIQUE,
        executed_ts TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

// Get already executed migrations
$executed = $pdo->query("SELECT filename FROM migrations")->fetchAll(PDO::FETCH_COLUMN);

// Get all migration files
$files = glob(__DIR__ . '/migrations/*.sql');
sort($files);

$pending = array_filter($files, function ($file) use ($executed) {
    return !in_array(basename($file), $executed);
});

if (empty($pending)) {
    echo "Nothing to migrate.\n";
    exit(0);
}

foreach ($pending as $file) {
    $filename = basename($file);
    $sql = file_get_contents($file);

    try {
        $pdo->exec($sql);
        $pdo->prepare("INSERT INTO migrations (filename) VALUES (?)")->execute([$filename]);
        echo "OK  $filename\n";
    } catch (PDOException $e) {
        echo "ERR $filename — {$e->getMessage()}\n";
        exit(1);
    }
}

echo "\nDone. " . count($pending) . " migration(s) executed.\n";
