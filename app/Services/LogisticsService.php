<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class LogisticsService
{
    /** @var array<string, array<int, string>> */
    private const ALLOWED_TRANSITIONS = [
        'solicitado' => ['em_cotacao', 'cancelado'],
        'em_cotacao' => ['aprovado', 'cancelado'],
        'aprovado' => ['emitido', 'cancelado'],
        'emitido' => ['embarcado', 'cancelado'],
        'embarcado' => ['concluido', 'cancelado'],
        'concluido' => [],
        'cancelado' => [],
    ];

    public function canEmbark(bool $requiresCompliance, bool $hasValidCompliance, bool $hasOpenImpediments): bool
    {
        if (!$requiresCompliance) {
            return !$hasOpenImpediments;
        }

        return $hasValidCompliance && !$hasOpenImpediments;
    }

    public function assertValidTransition(string $currentStatus, string $nextStatus): void
    {
        if (!isset(self::ALLOWED_TRANSITIONS[$currentStatus])) {
            throw new RuntimeException('Status operacional atual inválido.');
        }

        if (!in_array($nextStatus, self::ALLOWED_TRANSITIONS[$currentStatus], true)) {
            throw new RuntimeException(sprintf('Transição inválida: %s -> %s.', $currentStatus, $nextStatus));
        }
    }
}
