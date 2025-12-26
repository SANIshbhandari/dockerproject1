<?php
$host = "db";  // service name from docker-compose
$db   = "farm";
$user = "sanish";
$pass = "sanish123456";
$port = "5432";

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$db;";
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected to PostgreSQL successfully!";
} catch (PDOException $e) {
    echo $e->getMessage();
}
?>
