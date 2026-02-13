<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use InvalidArgumentException;
use PDO;

class BaseRepository
{
    public function __construct(protected ?PDO $pdo = null)
    {
        $this->pdo ??= Database::connection();
    }

    public function allByTenant(string $table, int $tenantId): array
    {
        $tenantId = $this->assertTenantContext($tenantId);
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
        $tenantId = $this->assertTenantContext($tenantId);
        $sets = implode(',', array_map(fn($k) => "{$k} = :{$k}", array_keys($data)));
        $sql = "UPDATE {$table} SET {$sets} WHERE tenant_id = :tenant_id AND id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([...$data, 'id' => $id, 'tenant_id' => $tenantId]);
    }

    public function find(string $table, int $id, int $tenantId): ?array
    {
        $tenantId = $this->assertTenantContext($tenantId);
        $stmt = $this->pdo->prepare("SELECT * FROM {$table} WHERE tenant_id = :tenant AND id = :id");
        $stmt->execute(['id' => $id, 'tenant' => $tenantId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function delete(string $table, int $id, int $tenantId): void
    {
        $tenantId = $this->assertTenantContext($tenantId);
        $stmt = $this->pdo->prepare("DELETE FROM {$table} WHERE tenant_id = :tenant AND id = :id");
        $stmt->execute(['id' => $id, 'tenant' => $tenantId]);
    }

    private function assertTenantContext(int $tenantId): int
    {
        if ($tenantId < 1) {
            throw new InvalidArgumentException('Tenant context is required.');
        }

        return $tenantId;
    }
}
