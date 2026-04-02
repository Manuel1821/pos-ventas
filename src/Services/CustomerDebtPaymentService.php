<?php

declare(strict_types=1);

namespace App\Services;

use App\Database\Database;
use App\Repositories\CashMovementRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\SalesRepository;

class CustomerDebtPaymentService
{
    private SalesRepository $salesRepo;
    private CustomerRepository $customerRepo;
    private CashMovementRepository $cashMovementRepo;

    public function __construct()
    {
        $this->salesRepo = new SalesRepository();
        $this->customerRepo = new CustomerRepository();
        $this->cashMovementRepo = new CashMovementRepository();
    }

    /**
     * @return array{success:bool, error?:string, applied_total?:float}
     */
    public function registerPayment(
        int $shopId,
        int $customerId,
        int $userId,
        string $action,
        float $amountInput,
        string $paymentMethod,
        ?string $observaciones = null
    ): array {
        $action = strtolower(trim($action));
        if (!in_array($action, ['abono', 'liquidar'], true)) {
            return ['success' => false, 'error' => 'Acción no válida.'];
        }

        $method = strtoupper(trim($paymentMethod));
        $allowed = ['EFECTIVO', 'TRANSFERENCIA', 'TARJETA_DEBITO', 'TARJETA_CREDITO', 'OTRO'];
        if (!in_array($method, $allowed, true)) {
            return ['success' => false, 'error' => 'Seleccione una forma de pago válida.'];
        }

        $debt = $this->customerRepo->getOpenDebtDetailsByCustomer($shopId, $customerId);
        $totalDebt = (float) ($debt['total_debt'] ?? 0);
        if ($totalDebt <= 0.0) {
            return ['success' => false, 'error' => 'Este cliente no tiene saldo pendiente.'];
        }

        $targetAmount = 0.0;
        if ($action === 'liquidar') {
            $targetAmount = round($totalDebt, 2);
        } else {
            $targetAmount = round($amountInput, 2);
            if ($targetAmount <= 0.0) {
                return ['success' => false, 'error' => 'Indique un importe mayor a cero.'];
            }
            if ($targetAmount - 0.01 > $totalDebt) {
                return ['success' => false, 'error' => 'El abono no puede ser mayor al saldo pendiente.'];
            }
        }

        if ($method === 'EFECTIVO') {
            $cashSvc = new CashService();
            if (!$cashSvc->hasOpenSession($shopId)) {
                return ['success' => false, 'error' => 'Debe haber una caja abierta para cobrar en efectivo.'];
            }
        }

        $customer = $this->customerRepo->findById($customerId, $shopId);
        if (!$customer) {
            return ['success' => false, 'error' => 'Cliente no encontrado.'];
        }
        $customerName = trim((string) ($customer['name'] ?? 'Cliente'));

        $obsStore = null;
        if ($observaciones !== null) {
            $t = trim($observaciones);
            if ($t !== '') {
                $obsStore = function_exists('mb_substr') ? mb_substr($t, 0, 500) : substr($t, 0, 500);
            }
        }

        // Texto fijo en sale_payments (reparto interno por ventas; no se muestra al usuario por nota).
        $refLine = $action === 'liquidar' ? 'Liquidación deuda' : 'Abono deuda';

        try {
            $appliedTotal = Database::transaction(function () use (
                $shopId,
                $customerId,
                $userId,
                $targetAmount,
                $method,
                $refLine,
                $customerName,
                $action,
                $obsStore
            ) {
                $remaining = $targetAmount;
                $cashEfectivo = 0.0;
                $folios = [];
                $appliedSum = 0.0;

                while ($remaining > 0.01) {
                    $sales = $this->salesRepo->lockOpenDebtSalesForCustomer($shopId, $customerId);
                    if ($sales === []) {
                        if ($appliedSum > 0.01) {
                            break;
                        }
                        throw new \RuntimeException('No hay ventas pendientes para registrar el pago.');
                    }
                    $first = $sales[0];
                    $saleId = (int) ($first['id'] ?? 0);
                    if ($saleId <= 0) {
                        throw new \RuntimeException('Error al leer la venta pendiente.');
                    }
                    $res = $this->salesRepo->applyDebtPaymentToSale(
                        $saleId,
                        $shopId,
                        $remaining,
                        $method,
                        $refLine
                    );
                    if ($res === null) {
                        throw new \RuntimeException('No se pudo aplicar el pago a la venta.');
                    }
                    $ap = round((float) ($res['applied'] ?? 0), 2);
                    if ($ap <= 0.001) {
                        throw new \RuntimeException('No se pudo aplicar el importe indicado.');
                    }
                    $appliedSum = round($appliedSum + $ap, 2);
                    $remaining = round($remaining - $ap, 2);
                    $folios[] = (int) ($first['folio'] ?? 0);
                    if ($method === 'EFECTIVO') {
                        $cashEfectivo = round($cashEfectivo + $ap, 2);
                    }
                }

                if ($appliedSum <= 0.01) {
                    throw new \RuntimeException('No se registró ningún cobro.');
                }

                $settlementType = $action === 'liquidar' ? 'LIQUIDACION' : 'ABONO';
                $this->customerRepo->insertDebtSettlement(
                    $shopId,
                    $customerId,
                    $settlementType,
                    $appliedSum,
                    $method,
                    $obsStore,
                    $userId
                );

                if ($method === 'EFECTIVO' && $cashEfectivo > 0.01) {
                    $cashSvc = new CashService();
                    $session = $cashSvc->getOpenSession($shopId);
                    if (!$session) {
                        throw new \RuntimeException('No hay caja abierta.');
                    }
                    $folioStr = implode(', ', array_unique(array_filter($folios)));
                    $note = 'Cobro deuda cliente ' . $customerName
                        . ($folioStr !== '' ? ' (folios: ' . $folioStr . ')' : '');
                    $this->cashMovementRepo->create(
                        $shopId,
                        (int) $session['id'],
                        'IN',
                        $cashEfectivo,
                        $note,
                        $userId
                    );
                }

                return $appliedSum;
            });
        } catch (\RuntimeException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'No se pudo registrar el pago.'];
        }

        return ['success' => true, 'applied_total' => $appliedTotal];
    }
}
