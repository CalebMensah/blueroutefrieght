<?php
// ══════════════════════════════════════════
//  BlueRoute — Database Configuration
//  includes/db.php
// ══════════════════════════════════════════

define('DB_HOST', 'localhost');
define('DB_NAME', 'blueroute');
define('DB_USER', 'blueuser');        // ← change to your DB user
define('DB_PASS', 'BlueRoute2025!');            // ← change to your DB password
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['success' => false, 'error' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}