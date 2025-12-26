<?php
function getDBConnection() {
    static $pdo;
    if ($pdo) return $pdo;

    $host = 'db'; // match docker-compose service name
    $db   = 'farm_management';
    $user = 'sanish';
    $pass = 'sanish123456';
    $port = '5432';

    $dsn = "pgsql:host=$host;port=$port;dbname=$db;";
    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        return $pdo;
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}
