<?php

$DB_HOST = getenv('DB_HOST') ?: '127.0.0.1';
$DB_NAME = getenv('DB_NAME') ?: 'preisliste_db';
$DB_USER = getenv('DB_USER') ?: 'peter';
$DB_PASS = getenv('DB_PASS') ?: 'sql666sql';

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die('DB-Verbindung fehlgeschlagen: ' . htmlspecialchars($e->getMessage()));
}
