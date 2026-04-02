<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Redirect;
use App\Core\View;
use App\Services\SalesService;
use App\Services\CashService;
use App\Repositories\CustomerRepository;
use App\Repositories\ProductRepository;
use App\Repositories\SalesRepository;

class POSController
{
    private CashService $cashService;
    private CustomerRepository $customerRepo;
    private ProductRepository $productRepo;
    private SalesService $salesService;
    private SalesRepository $salesRepo;

    public function __construct()
    {
        $this->cashService = new CashService();
        $this->customerRepo = new CustomerRepository();
        $this->productRepo = new ProductRepository();
        $this->salesService = new SalesService();
        $this->salesRepo = new SalesRepository();
    }

    public function nuevaVenta(Request $request): void
    {
        $shopId = Auth::shopId();
        if ($shopId === null) {
            Flash::set('danger', 'Sesión inválida.');
            Redirect::to('/login');
        }

        $flash = Flash::consume();
        $openSession = $this->cashService->getOpenSession($shopId);
        $publicCustomer = $this->customerRepo->findPublicByShop($shopId);

        View::render('admin/pos/nueva_venta', [
            'pageTitle' => 'Nueva venta',
            'userName' => $this->getUserName(),
            'shopName' => $this->getShopName(),
            'flash' => $flash,
            'hasOpenCash' => $openSession !== null,
            'cashSession' => $openSession,
            'publicCustomer' => $publicCustomer,
        ]);
    }

    public function buscarProductos(Request $request): void
    {
        $shopId = Auth::shopId();
        if ($shopId === null) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            return;
        }

        $query = trim((string) ($request->query['query'] ?? ''));
        $limit = isset($request->query['limit']) ? (int) $request->query['limit'] : 20;
        $limit = max(1, min(50, $limit));

        $items = $this->productRepo->searchForPos($shopId, $query, $limit);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'items' => $items], JSON_UNESCAPED_UNICODE);
    }

    public function buscarClientes(Request $request): void
    {
        $shopId = Auth::shopId();
        if ($shopId === null) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            return;
        }

        $query = trim((string) ($request->query['query'] ?? ''));
        $items = $this->customerRepo->searchForPos($shopId, $query, 20);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'items' => $items], JSON_UNESCAPED_UNICODE);
    }

    public function confirmarVenta(Request $request): void
    {
        $shopId = Auth::shopId();
        $userId = Auth::userId();
        if ($shopId === null || $userId === null) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            return;
        }

        // Permite POST como JSON o form-data.
        $body = $request->body;
        if (empty($body)) {
            $raw = (string) file_get_contents('php://input');
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $body = $decoded;
            }
        }

        $payload = is_array($body) ? $body : [];
        // Si el cliente no viene definido, usamos el cliente genérico (si existe).
        if (!array_key_exists('customer_id', $payload) || $payload['customer_id'] === null || $payload['customer_id'] === '' ) {
            $publicCustomer = $this->customerRepo->findPublicByShop($shopId);
            if ($publicCustomer && !empty($publicCustomer['id'])) {
                $payload['customer_id'] = (int) $publicCustomer['id'];
            }
        }
        $result = $this->salesService->confirmSale($payload, $shopId, $userId);

        if (!($result['success'] ?? false)) {
            http_response_code(422);
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    }

    public function ticket(Request $request): void
    {
        $shopId = Auth::shopId();
        $saleId = (int) ($request->routeParams['id'] ?? 0);
        if ($shopId === null || $saleId <= 0) {
            Flash::set('danger', 'Solicitud inválida.');
            Redirect::to('/admin/dashboard');
        }

        $flash = Flash::consume();
        $saleData = $this->salesRepo->getSaleForTicket($saleId, $shopId);
        if (!$saleData) {
            Flash::set('danger', 'Venta no encontrada.');
            Redirect::to('/admin/dashboard');
        }

        $ticketHtml = $this->salesService->htmlTicketForSale($saleId, $shopId);

        View::render('admin/pos/ticket', [
            'pageTitle' => 'Ticket de venta',
            'userName' => $this->getUserName(),
            'shopName' => $this->getShopName(),
            'flash' => $flash,
            'sale' => $saleData,
            'ticketHtml' => $ticketHtml,
        ]);
    }

    private function getUserName(): string
    {
        $stmt = \App\Database\Database::pdo()->prepare('SELECT first_name, last_name FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => Auth::userId()]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $name = trim((($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')));
        return $name !== '' ? $name : 'Usuario';
    }

    private function getShopName(): string
    {
        $stmt = \App\Database\Database::pdo()->prepare('SELECT name FROM shops WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => Auth::shopId()]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (string) ($row['name'] ?? '');
    }
}

