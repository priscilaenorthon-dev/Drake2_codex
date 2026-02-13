<?php

declare(strict_types=1);

require __DIR__ . '/../config/bootstrap.php';

$pdo = App\Core\Database::connection();

foreach (glob(__DIR__ . '/../database/seeds/*.sql') as $file) {
    echo "Running seed: {$file}\n";
    $pdo->exec(file_get_contents($file));
}

echo "Seeds concluídos.\n";
