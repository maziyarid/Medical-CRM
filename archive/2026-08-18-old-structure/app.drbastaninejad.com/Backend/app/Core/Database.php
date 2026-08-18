<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Database — thin PDO singleton
 *
 * Reads DB_HOST / DB_PORT / DB_NAME / DB_USER / DB_PASS from $_ENV (loaded by
 * public/index.php via .env).  All queries must use utf8mb4 / UTC / InnoDB.
 */
final class Database
{
    private static ?\PDO $conn = null;

    private function __construct() {}

    public static function conn(): \PDO
    {
        if (self::$conn === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $_ENV['DB_HOST'] ?? '127.0.0.1',
                $_ENV['DB_PORT'] ?? '3306',
                $_ENV['DB_NAME'] ?? 'medical_crm'
            );
            self::$conn = new \PDO($dsn, $_ENV['DB_USER'] ?? '', $_ENV['DB_PASS'] ?? '', [
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            self::$conn->exec("SET time_zone = '+00:00'");
        }
        return self::$conn;
    }

    /** Reset singleton — test use only */
    public static function reset(): void
    {
        self::$conn = null;
    }
}
