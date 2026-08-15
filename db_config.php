<?php
/**
 * Database Configuration for Microservice System (Port 81)
 */

if (!extension_loaded('pdo_mysql')) {
    if (php_sapi_name() !== 'cli' && !headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error' => 'The "pdo_mysql" PHP extension is not enabled in this PHP environment. If testing outside Docker in Laragon, enable "pdo_mysql" in Laragon -> PHP -> Extensions -> pdo_mysql. If in Docker, run: docker compose up -d --build'
        ]);
        exit;
    }
}

$db_host = getenv('DB_HOST') ?: (getenv('DOCKER_ENV') ? 'mysql' : '127.0.0.1');
// Fallback check: if running inside Docker bridge network, 'mysql' is resolved
if ($db_host === '127.0.0.1' && gethostbyname('mysql') !== 'mysql') {
    $db_host = 'mysql';
}

$db_name = getenv('DB_NAME') ?: 'hotel_db';
$db_user = getenv('DB_USER') ?: 'hotel_user';
$db_pass = getenv('DB_PASS') ?: 'hotel_password';
$db_port = getenv('DB_PORT') ?: '3306';

try {
    $pdo = new PDO("mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    try {
        $pdo = new PDO("mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4", 'root', 'rootpassword', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (PDOException $ex) {
        if (php_sapi_name() !== 'cli' && !headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error' => 'Database connection failed: ' . $ex->getMessage() . '. Host: ' . $db_host
            ]);
            exit;
        }
        die("Database Connection Error: " . $ex->getMessage());
    }
}
