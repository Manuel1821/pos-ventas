<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\CashMovementRepository;
use App\Repositories\CashSessionRepository;

class CashService
{
    private CashSessionRepository $sessionRepo;
    private CashMovementRepository $movementRepo;

    public function __construct()
    {
        $this->sessionRepo = new CashSessionRepository();
        $this->movementRepo = new CashMovementRepository();
    }

    /**
     * Verifica si la tienda tiene una caja abierta.
     */
    public function hasOpenSession(int $shopId): bool
    {
        return $this->sessionRepo->getOpenByShop($shopId) !== null;
    }

    /**
     * Obtiene la sesión abierta de la tienda.
     */
    public function getOpenSession(int $shopId): ?array
    {
        return $this->sessionRepo->getOpenByShop($shopId);
    }

    /**
     * Abre una nueva sesión de caja. Falla si ya hay una abierta.
     */
    public function openSession(int $shopId, int $userId, float $initialAmount): array
    {
        if ($this->hasOpenSession($shopId)) {
            return ['success' => false, 'error' => 'Ya existe una caja abierta. Debe cerrarla antes de abrir otra.'];
        }
        if ($initialAmount < 0) {
            return ['success' => false, 'error' => 'El monto inicial no puede ser negativo.'];
        }
        $sessionId = $this->sessionRepo->create($shopId, $userId, $initialAmount);
        return ['success' => true, 'session_id' => $sessionId];
    }

    /**
     * Resumen de la caja actual: sesión + totales (inicial, ingresos, retiros, ventas, esperado).
     */
    public function getCurrentSummary(int $shopId): ?array
    {
        $session = $this->sessionRepo->getOpenByShop($shopId);
        if (!$session) {
            return null;
        }
        $totals = $this->movementRepo->sumBySession((int) $session['id']);
        $salesPaid = $this->movementRepo->sumSalesPaidBySession((int) $session['id']);
        $initial = (float) ($session['initial_amount'] ?? 0);
        $totalIn = $totals['IN'];
        $totalOut = $totals['OUT'];
        $expected = $initial + $salesPaid + $totalIn - $totalOut;

        return [
            'session' => $session,
            'initial_amount' => $initial,
            'total_ins' => $totalIn,
            'total_outs' => $totalOut,
            'sales_paid' => $salesPaid,
            'expected_amount' => round($expected, 2),
        ];
    }

    /**
     * Lista movimientos de la sesión abierta.
     */
    public function getCurrentMovements(int $shopId): array
    {
        $session = $this->sessionRepo->getOpenByShop($shopId);
        if (!$session) {
            return [];
        }
        return $this->movementRepo->getBySession((int) $session['id'], $shopId);
    }

    /**
     * Registra un ingreso o retiro manual.
     */
    public function addMovement(int $shopId, int $userId, string $type, float $amount, ?string $note): array
    {
        $session = $this->sessionRepo->getOpenByShop($shopId);
        if (!$session) {
            return ['success' => false, 'error' => 'No hay una caja abierta. Abra una sesión primero.'];
        }
        if (!in_array($type, ['IN', 'OUT'], true)) {
            return ['success' => false, 'error' => 'Tipo de movimiento no válido.'];
        }
        if ($amount <= 0) {
            return ['success' => false, 'error' => 'El monto debe ser mayor a cero.'];
        }
        $this->movementRepo->create(
            $shopId,
            (int) $session['id'],
            $type,
            $amount,
            $note ?? '',
            $userId
        );
        return ['success' => true];
    }

    /**
     * Cierra la sesión de caja: calcula esperado, registra contado y diferencia.
     */
    public function closeSession(
        int $shopId,
        int $userId,
        ?float $countedAmount,
        ?string $observations
    ): array {
        $session = $this->sessionRepo->getOpenByShop($shopId);
        if (!$session) {
            return ['success' => false, 'error' => 'No hay una caja abierta para cerrar.'];
        }
        $summary = $this->getCurrentSummary($shopId);
        if (!$summary) {
            return ['success' => false, 'error' => 'No se pudo calcular el resumen de caja.'];
        }
        $expected = $summary['expected_amount'];
        $ok = $this->sessionRepo->close(
            (int) $session['id'],
            $shopId,
            $expected,
            $countedAmount,
            $userId,
            $observations
        );
        if (!$ok) {
            return ['success' => false, 'error' => 'No se pudo cerrar la sesión.'];
        }
        return ['success' => true];
    }

    /**
     * Historial de sesiones con filtros.
     */
    public function getHistory(int $shopId, int $page = 1, array $filters = []): array
    {
        return $this->sessionRepo->listHistory($shopId, $page, $filters);
    }

    /**
     * Detalle de una sesión por id (para historial).
     */
    public function getSessionDetail(int $sessionId, int $shopId): ?array
    {
        $session = $this->sessionRepo->findById($sessionId, $shopId);
        if (!$session) {
            return null;
        }
        $movements = $this->movementRepo->getBySession($sessionId, $shopId);
        $totals = $this->movementRepo->sumBySession($sessionId);
        $salesPaid = $this->movementRepo->sumSalesPaidBySession($sessionId);
        $initial = (float) ($session['initial_amount'] ?? 0);
        $expected = $initial + $salesPaid + $totals['IN'] - $totals['OUT'];

        return [
            'session' => $session,
            'movements' => $movements,
            'initial_amount' => $initial,
            'total_ins' => $totals['IN'],
            'total_outs' => $totals['OUT'],
            'sales_paid' => $salesPaid,
            'expected_amount' => round($expected, 2),
        ];
    }
}
