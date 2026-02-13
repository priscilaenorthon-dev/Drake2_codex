<?php

declare(strict_types=1);

namespace App\Repositories;

final class UserRepository extends BaseRepository
{
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function hasPermission(int $userId, string $permission): bool
    {
        $sql = 'SELECT COUNT(*) FROM user_roles ur
                JOIN role_permissions rp ON rp.role_id = ur.role_id
                JOIN permissions p ON p.id = rp.permission_id
                WHERE ur.user_id = :user_id AND p.name = :permission';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['user_id' => $userId, 'permission' => $permission]);

        return (int) $stmt->fetchColumn() > 0;
    }
}
