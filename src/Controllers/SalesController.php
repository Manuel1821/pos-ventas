<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Redirect;
use App\Core\Request;
use App\Core\View;
use App\Repositories\SalesRepository;
use App\Services\SaleAdjustmentService;
use App\Services\SalesService;
use App\Database\Database;

class SalesController
{
    private SalesRepository $salesRepo;
    private SaleAdjustmentService $saleAdjustmentService;
    private SalesService $salesService;

    public function __construct()
    {
        $this->salesRepo = new SalesRepository();
        $this->saleAdjustmentService = new SaleAdjustmentService();
        $this->salesService = new SalesService();
    }

    public function index(Request $request): void
    {
        $shopId = Auth::shopId();
        if ($shopId === null) {
            Flash::set('danger', 'Sesion invalida.');
            Redirect::to('/login');
        }

        $page = max(1, (int) ($request->query['pagina'] ?? 1));
        $filters = [
            'folio' => trim((string) ($request->query['folio'] ?? '')),
            'desde' => trim((string) ($request->query['desde'] ?? '')),
            'hasta' => trim((string) ($request->query['hasta'] ?? '')),
            'customer_id' => (int) ($request->query['customer_id'] ?? 0),
            'user_id' => (int) ($request->query['user_id'] ?? 0),
            'status' => strtoupper(trim((string) ($request->query['status'] ?? ''))),
        ];
        if (!in_array($filters['status'], ['', 'OPEN', 'PAID', 'CANCELLED', 'REFUNDED'], true)) {
            $filters['status'] = '';
        }

        $history = $this->salesRepo->getSalesHistory($shopId, $page, $filters);
        $customers = Database::fetchAll(
            'SELECT id, name
             FROM customers
             WHERE shop_id = :shop_id AND status = "ACTIVE"
             ORDER BY is_public DESC, name ASC',
            ['shop_id' => $shopId]
        );
        $sellers = $this->getSellers($shopId);

        View::render('admin/ventas/indice', [
            'pageTitle' => 'Ventas',
            'items' => $history['items'],
            'total' => $history['total'],
            'page' => $history['page'],
            'total_pages' => $history['total_pages'],
            'filters' => $filters,
            'customers' => $customers,
            'sellers' => $sellers,
            'flash' => Flash::consume(),
            'userName' => $this->getUserName(),
            'shopName' => $this->getShopName(),
        ]);
    }

    public function detalle(Request $request): void
    {
        $shopId = Auth::shopId();
        if ($shopId === null) {
            Flash::set('danger', 'Sesion invalida.');
            Redirect::to('/login');
        }

        $saleId = (int) ($request->routeParams['id'] ?? 0);
        if ($saleId <= 0) {
            Flash::set('danger', 'Venta invalida.');
            Redirect::to('/admin/ventas');
        }

        $detail = $this->salesRepo->getSaleDetail($saleId, $shopId);
        if (!$detail) {
            Flash::set('danger', 'Venta no encontrada.');
            Redirect::to('/admin/ventas');
        }

        View::render('admin/ventas/detalle', [
            'pageTitle' => 'Detalle de venta',
            'detail' => $detail,
            'cancellation' => $this->salesRepo->getCancellationBySale($saleId),
            'returns' => $this->salesRepo->getReturnsBySale($saleId),
            'flash' => Flash::consume(),
            'userName' => $this->getUserName(),
            'shopName' => $this->getShopName(),
        ]);
    }

    public function cancelar(Request $request): void
    {
        $shopId = Auth::shopId();
        $userId = Auth::userId();
        if ($shopId === null || $userId === null) {
            Flash::set('danger', 'Sesion invalida.');
            Redirect::to('/login');
        }

        if (!$this->canManageAdjustments()) {
            Flash::set('danger', 'No tienes permisos para cancelar ventas.');
            Redirect::to('/admin/ventas');
        }

        $saleId = (int) ($request->routeParams['id'] ?? 0);
        if ($saleId <= 0) {
            Flash::set('danger', 'Venta invalida.');
            Redirect::to('/admin/ventas');
        }

        $reason = trim((string) ($request->body['reason'] ?? ''));
        $notes = trim((string) ($request->body['notes'] ?? ''));
        $result = $this->saleAdjustmentService->cancelSale($shopId, $saleId, $userId, $reason, $notes !== '' ? $notes : null);
        if (!($result['success'] ?? false)) {
            Flash::set('danger', (string) ($result['error'] ?? 'No se pudo cancelar la venta.'));
            Redirect::to('/admin/ventas/detalle/' . $saleId);
        }

        Flash::set('success', 'Venta cancelada correctamente.');
        Redirect::to('/admin/ventas/detalle/' . $saleId);
    }

    public function devolver(Request $request): void
    {
        $shopId = Auth::shopId();
        $userId = Auth::userId();
        if ($shopId === null || $userId === null) {
            Flash::set('danger', 'Sesion invalida.');
            Redirect::to('/login');
        }

        if (!$this->canManageAdjustments()) {
            Flash::set('danger', 'No tienes permisos para registrar devoluciones.');
            Redirect::to('/admin/ventas');
        }

        $saleId = (int) ($request->routeParams['id'] ?? 0);
        if ($saleId <= 0) {
            Flash::set('danger', 'Venta invalida.');
            Redirect::to('/admin/ventas');
        }

        $reason = trim((string) ($request->body['reason'] ?? ''));
        $notes = trim((string) ($request->body['notes'] ?? ''));
        $qtyMap = $request->body['return_qty'] ?? [];
        if (!is_array($qtyMap)) {
            $qtyMap = [];
        }
        $result = $this->saleAdjustmentService->returnSaleItems($shopId, $saleId, $userId, $reason, $notes !== '' ? $notes : null, $qtyMap);
        if (!($result['success'] ?? false)) {
            Flash::set('danger', (string) ($result['error'] ?? 'No se pudo registrar la devolucion.'));
            Redirect::to('/admin/ventas/detalle/' . $saleId);
        }

        Flash::set('success', 'Devolucion registrada correctamente.');
        Redirect::to('/admin/ventas/detalle/' . $saleId);
    }

    public function ticket(Request $request): void
    {
        $shopId = Auth::shopId();
        if ($shopId === null) {
            Flash::set('danger', 'Sesion invalida.');
            Redirect::to('/login');
        }

        $saleId = (int) ($request->routeParams['id'] ?? 0);
        if ($saleId <= 0) {
            Flash::set('danger', 'Venta invalida.');
            Redirect::to('/admin/ventas');
        }

        $saleData = $this->salesRepo->getSaleForTicket($saleId, $shopId);
        if (!$saleData) {
            Flash::set('danger', 'Venta no encontrada.');
            Redirect::to('/admin/ventas');
        }

        $ticketHtml = $this->salesService->htmlTicketForSale($saleId, $shopId);

        View::render('admin/pos/ticket', [
            'pageTitle' => 'Ticket de venta',
            'sale' => $saleData,
            'flash' => Flash::consume(),
            'userName' => $this->getUserName(),
            'shopName' => $this->getShopName(),
            'ticketHtml' => $ticketHtml,
        ]);
    }

    /**
     * @return array<int,array{id:int,name:string}>
     */
    private function getSellers(int $shopId): array
    {
        return Database::fetchAll(
            'SELECT id, TRIM(CONCAT(COALESCE(first_name, ""), " ", COALESCE(last_name, ""))) AS name
             FROM users
             WHERE shop_id = :shop_id
             ORDER BY first_name ASC, last_name ASC',
            ['shop_id' => $shopId]
        );
    }

    private function getUserName(): string
    {
        $stmt = Database::pdo()->prepare('SELECT first_name, last_name FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => Auth::userId()]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $name = trim((($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')));
        return $name !== '' ? $name : 'Usuario';
    }

    private function getShopName(): string
    {
        $stmt = Database::pdo()->prepare('SELECT name FROM shops WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => Auth::shopId()]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (string) ($row['name'] ?? '');
    }

    private function canManageAdjustments(): bool
    {
        $roles = Auth::roles();
        foreach ($roles as $role) {
            if (in_array($role, ['admin', 'cajero'], true)) {
                return true;
            }
        }
        return false;
    }
}

