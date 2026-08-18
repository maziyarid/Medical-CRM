<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Model — thin base providing a shared PDO accessor.
 */
abstract class Model
{
    protected function db(): \PDO
    {
        return Database::conn();
    }
}
