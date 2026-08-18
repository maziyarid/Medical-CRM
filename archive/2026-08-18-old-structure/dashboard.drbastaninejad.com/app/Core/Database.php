<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

final class Database
{
    private static ?PDO $instance = null;

    public static function conn(): PDO
    {
        if (self::$instance === null) {
            // In test environments BASE_PATH may not be defined; fall back to $_ENV.
            if (defined('BASE_PATH') && file_exists(BASE_PATH . '/config/database.php')) {
                $cfg = require BASE_PATH . '/config/database.php';
            } else {
                // ENV key names match config/database.php: DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS
                $cfg = [
                    'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
                    'port' => $_ENV['DB_PORT'] ?? '3306',
                    'name' => $_ENV['DB_NAME']     ?? 'mazcrm_test',
                    'user' => $_ENV['DB_USER']     ?? 'root',
                    'pass' => $_ENV['DB_PASS']     ?? '',
                ];
            }
            $dsn = "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['name']};charset=utf8mb4";
            self::$instance = new PDO($dsn, $cfg['user'], $cfg['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '+00:00'",
            ]);
        }
        return self::$instance;
    }

    /**
     * Reset the singleton — used in tests so each test suite gets a fresh
     * connection state without carrying over transactions or state.
     */
    public static function reset(): void
    {
        self::$instance = null;
    }
}
