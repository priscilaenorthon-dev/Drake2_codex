<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

class BaseRepository
{
    public function __construct(protected ?PDO $pdo = null)
    {
        $this->pdo ??= Database::connection();
    }

    public function allByTenant(string $table, int $tenantId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$table} WHERE tenant_id = :tenant ORDER BY id DESC");
        $stmt->execute(['tenant' => $tenantId]);

        return $stmt->fetchAll();
    }

    public function create(string $table, array $data): int
    {
        $columns = implode(',', array_keys($data));
        $params = ':' . implode(',:', array_keys($data));
        $stmt = $this->pdo->prepare("INSERT INTO {$table} ({$columns}) VALUES ({$params})");
        $stmt->execute($data);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(string $table, int $id, int $tenantId, array $data): void
    {
        $sets = implode(',', array_map(fn($k) => "{$k} = :{$k}", array_keys($data)));
        $sql = "UPDATE {$table} SET {$sets} WHERE id = :id AND tenant_id = :tenant_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([...$data, 'id' => $id, 'tenant_id' => $tenantId]);
    }

    public function find(string $table, int $id, int $tenantId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$table} WHERE id = :id AND tenant_id = :tenant");
        $stmt->execute(['id' => $id, 'tenant' => $tenantId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function delete(string $table, int $id, int $tenantId): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$table} WHERE id = :id AND tenant_id = :tenant");
        $stmt->execute(['id' => $id, 'tenant' => $tenantId]);
    }
}
