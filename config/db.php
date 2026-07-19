<?php
// Database connection settings.
//
// Local XAMPP keeps working with the defaults below. For deployment, set these
// as environment variables in the hosting provider instead of editing this file:
// DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS.
//
// Clever Cloud MySQL add-ons are also supported automatically through either
// MYSQL_ADDON_URI or the separate MYSQL_ADDON_HOST / PORT / DB / USER /
// PASSWORD variables. DB_* values take priority if both are present.
// Optional for managed MySQL providers that require a CA file: DB_SSL_CA.

$mysqlUrl = getenv('MYSQL_ADDON_URI') ?: getenv('DATABASE_URL') ?: '';
$mysqlConfig = $mysqlUrl ? parse_url($mysqlUrl) : [];
$mysqlPath = isset($mysqlConfig['path']) ? ltrim($mysqlConfig['path'], '/') : '';

define('DB_HOST', getenv('DB_HOST') ?: getenv('MYSQL_ADDON_HOST') ?: ($mysqlConfig['host'] ?? 'localhost'));
define('DB_PORT', getenv('DB_PORT') ?: getenv('MYSQL_ADDON_PORT') ?: (string)($mysqlConfig['port'] ?? '3306'));
define('DB_NAME', getenv('DB_NAME') ?: getenv('MYSQL_ADDON_DB') ?: ($mysqlPath ?: 'web_feud_ctf'));
define('DB_USER', getenv('DB_USER') ?: getenv('MYSQL_ADDON_USER') ?: ($mysqlConfig['user'] ?? 'root'));
define('DB_PASS', getenv('DB_PASS') ?: getenv('MYSQL_ADDON_PASSWORD') ?: (isset($mysqlConfig['pass']) ? urldecode($mysqlConfig['pass']) : '')); // default XAMPP root password is blank
define('DB_SSL_CA', getenv('DB_SSL_CA') ?: '');

function get_db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        if (DB_SSL_CA !== '') {
            $options[PDO::MYSQL_ATTR_SSL_CA] = DB_SSL_CA;
        }
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}
