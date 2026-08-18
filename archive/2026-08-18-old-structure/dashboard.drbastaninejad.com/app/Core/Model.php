<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

abstract class Model
{
    protected string $table;
    protected string $pk = 'id';

    protected function db(): PDO
    {
        return Database::conn();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db()->prepare("SELECT * FROM {$this->table} WHERE {$this->pk} = ? AND deleted_at IS NULL");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $cols = implode(',', array_keys($data));
        $ph = implode(',', array_fill(0, count($data), '?'));
        $stmt = $this->db()->prepare("INSERT INTO {$this->table} ($cols) VALUES ($ph)");
        $stmt->execute(array_values($data));
        return (int)$this->db()->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $set = implode(',', array_map(fn($c) => "$c = ?", array_keys($data)));
        $stmt = $this->db()->prepare("UPDATE {$this->table} SET $set WHERE {$this->pk} = ?");
        $stmt->execute([...array_values($data), $id]);
    }

    public function softDelete(int $id): void
    {
        $stmt = $this->db()->prepare("UPDATE {$this->table} SET deleted_at = UTC_TIMESTAMP() WHERE {$this->pk} = ?");
        $stmt->execute([$id]);
    }
}
