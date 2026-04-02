<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\CashierCutRepository;
use App\Repositories\CashSessionRepository;

class CashierCutService
{
    private CashSessionRepository $sessionRepo;
    private CashierCutRepository $cutRepo;

    public function __construct()
    {
        $this->sessionRepo = new CashSessionRepository();
        $this->cutRepo = new CashierCutRepository();
    }

    /**
     * @return array<string, string>
     */
    public static function paymentMethodLabels(): array
    {
        return [
            'EFECTIVO' => 'Efectivo',
            'TARJETA_CREDITO' => 'Tarjeta de crédito',
            'TARJETA_DEBITO' => 'Tarjeta de débito',
            'TRANSFERENCIA' => 'Transferencia',
            'CUENTA_CREDITO' => 'Cuenta crédito',
            'OTRO' => 'Otro',
        ];
    }

    /**
     * Resuelve la sesión de caja: por id (si se indica) o la abierta actual.
     */
    public function resolveSession(int $shopId, ?int $sessionId): ?array
    {
        if ($sessionId !== null && $sessionId > 0) {
            return $this->sessionRepo->findById($sessionId, $shopId);
        }
        return $this->sessionRepo->getOpenByShop($shopId);
    }

    /**
     * Genera datos del corte por cajero para una sesión y usuario.
     *
     * @return array{
     *   session: array,
     *   cashier_id: int,
     *   cashier_name: string,
     *   payments_by_method: array<int, array{method:string,label:string,amount:float}>,
     *   sales_count: int,
     *   sales_total: float,
     *   cash_from_pos_sales: float,
     *   manual_in: float,
     *   manual_out: float,
     *   expected_cash_hand: float,
     *   credit_sales_count: int,
     *   credit_sales_total: float,
     *   credit_pending_total: float,
     *   credit_customers: array<int, array{customer_id:int|null, customer_name:string, sales_count:int, total_amount:float, pending_amount:float}>
     * }|null
     */
    public function getCutData(int $shopId, int $cashSessionId, int $cashierUserId): ?array
    {
        $session = $this->sessionRepo->findById($cashSessionId, $shopId);
        if (!$session) {
            return null;
        }
        $name = $this->cutRepo->getCashierName($cashierUserId, $shopId);
        if ($name === null) {
            return null;
        }

        $labels = self::paymentMethodLabels();
        $raw = $this->cutRepo->sumPaymentsByMethodForCashierSession($shopId, $cashSessionId, $cashierUserId);
        $paymentsByMethod = [];
        $cashFromSales = 0.0;
        foreach ($raw as $row) {
            $m = (string) $row['payment_method'];
            $amt = (float) $row['total_amount'];
            $paymentsByMethod[] = [
                'method' => $m,
                'label' => $labels[$m] ?? $m,
                'amount' => round($amt, 2),
            ];
            if ($m === 'EFECTIVO') {
                $cashFromSales = round($amt, 2);
            }
        }

        $sales = $this->cutRepo->sumSalesTotalsForCashierSession($shopId, $cashSessionId, $cashierUserId);
        $credit = $this->cutRepo->sumCreditSalesForCashierSession($shopId, $cashSessionId, $cashierUserId);
        $creditCustomers = $this->cutRepo->listCreditCustomersForCashierSession($shopId, $cashSessionId, $cashierUserId);
        $mov = $this->cutRepo->sumManualMovementsByUserSession($shopId, $cashSessionId, $cashierUserId);
        $manualIn = $mov['in_total'];
        $manualOut = $mov['out_total'];
        $expectedCash = round($cashFromSales + $manualIn - $manualOut, 2);

        return [
            'session' => $session,
            'cashier_id' => $cashierUserId,
            'cashier_name' => $name,
            'payments_by_method' => $paymentsByMethod,
            'sales_count' => $sales['sales_count'],
            'sales_total' => $sales['sales_total'],
            'cash_from_pos_sales' => $cashFromSales,
            'manual_in' => $manualIn,
            'manual_out' => $manualOut,
            'expected_cash_hand' => $expectedCash,
            'credit_sales_count' => $credit['credit_sales_count'],
            'credit_sales_total' => $credit['credit_sales_total'],
            'credit_pending_total' => $credit['credit_pending_total'],
            'credit_customers' => $creditCustomers,
        ];
    }
}
