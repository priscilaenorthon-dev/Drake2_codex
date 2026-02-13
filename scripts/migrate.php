<?php

declare(strict_types=1);

require __DIR__ . '/../config/bootstrap.php';

$pdo = App\Core\Database::connection();

foreach (glob(__DIR__ . '/../database/migrations/*.sql') as $file) {
    echo "Running migration: {$file}\n";
    $pdo->exec(file_get_contents($file));
}

echo "Migrations concluídas.\n";
