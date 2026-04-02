<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Redirect;
use App\Core\Request;
use App\Core\View;
use App\Database\Database;
use App\Repositories\LayawayRepository;
use App\Services\LayawayService;

class LayawayController
{
    private LayawayRepository $layawayRepo;
    private LayawayService $layawayService;

    public function __construct()
    {
        $this->layawayRepo = new LayawayRepository();
        $this->layawayService = new LayawayService();
    }

    public function index(Request $request): void
    {
        $shopId = Auth::shopId();
        if ($shopId === null) {
            Flash::set('danger', 'Sesion invalida.');
            Redirect::to('/login');
        }

        $page = max(1, (int) ($request->query['pagina'] ?? 1));
        $tab = (string) ($request->query['tab'] ?? 'all');
        $status = '';
        if ($tab === 'open') {
            $status = 'OPEN';
        } elseif ($tab === 'paid') {
            $status = 'PAID';
        } elseif ($tab === 'cancelled') {
            $status = 'CANCELLED';
        }

        $filters = [
            'q' => trim((string) ($request->query['q'] ?? '')),
            'status' => $status,
            'sort' => trim((string) ($request->query['sort'] ?? 'date_desc')),
        ];
        if (!in_array($filters['sort'], ['date_desc', 'date_asc', 'total_desc', 'total_asc'], true)) {
            $filters['sort'] = 'date_desc';
        }

        $history = $this->layawayRepo->listForShop($shopId, $page, $filters);

        View::render('admin/apartados/indice', [
            'pageTitle' => 'Apartados',
            'items' => $history['items'],
            'total' => $history['total'],
            'page' => $history['page'],
            'total_pages' => $history['total_pages'],
            'filters' => $filters,
            'tab' => $tab,
            'flash' => Flash::consume(),
            'userName' => $this->getUserName(),
            'shopName' => $this->getShopName(),
        ]);
    }

    public function crear(Request $request): void
    {
        $shopId = Auth::shopId();
        if ($shopId === null) {
            Flash::set('danger', 'Sesion invalida.');
            Redirect::to('/login');
        }

        $customers = Database::fetchAll(
            'SELECT id, name FROM customers WHERE shop_id = :shop_id AND status = "ACTIVE" ORDER BY is_public DESC, name ASC',
            ['shop_id' => $shopId]
        );
        $sellers = Database::fetchAll(
            'SELECT id, TRIM(CONCAT(COALESCE(first_name, ""), " ", COALESCE(last_name, ""))) AS name
             FROM users WHERE shop_id = :shop_id ORDER BY first_name ASC, last_name ASC',
            ['shop_id' => $shopId]
        );

        View::render('admin/apartados/crear', [
            'pageTitle' => 'Crear apartado',
            'customers' => $customers,
            'sellers' => $sellers,
            'defaultStartDate' => date('Y-m-d'),
            'flash' => Flash::consume(),
            'userName' => $this->getUserName(),
            'shopName' => $this->getShopName(),
        ]);
    }

    public function guardar(Request $request): void
    {
        $shopId = Auth::shopId();
        $userId = Auth::userId();
        if ($shopId === null || $userId === null) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'No autorizado'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $body = $request->body;
        if (empty($body)) {
            $raw = (string) file_get_contents('php://input');
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $body = $decoded;
            }
        }
        $payload = is_array($body) ? $body : [];

        $result = $this->layawayService->create($payload, $shopId, $userId);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    }

    public function documento(Request $request): void
    {
        $shopId = Auth::shopId();
        if ($shopId === null) {
            Flash::set('danger', 'Sesion invalida.');
            Redirect::to('/login');
        }

        $id = (int) ($request->routeParams['id'] ?? 0);
        if ($id <= 0) {
            Flash::set('danger', 'Apartado invalido.');
            Redirect::to('/admin/apartados');
        }

        $layaway = $this->layawayRepo->findById($id, $shopId);
        if (!$layaway) {
            Flash::set('danger', 'Apartado no encontrado.');
            Redirect::to('/admin/apartados');
        }

        $items = $this->layawayRepo->getItems($id);
        $payments = $this->layawayRepo->getPayments($id);
        $shopRow = Database::fetch('SELECT name, address, phone FROM shops WHERE id = :id LIMIT 1', ['id' => $shopId]);

        View::render('admin/apartados/documento', [
            'pageTitle' => 'Apartado #' . (int) ($layaway['folio'] ?? 0),
            'layaway' => $layaway,
            'items' => $items,
            'payments' => $payments,
            'shopRow' => $shopRow ?: [],
            'flash' => Flash::consume(),
            'userName' => $this->getUserName(),
            'shopName' => $this->getShopName(),
        ]);
    }

    public function registrarAbono(Request $request): void
    {
        $shopId = Auth::shopId();
        $userId = Auth::userId();
        if ($shopId === null || $userId === null) {
            Flash::set('danger', 'Sesion invalida.');
            Redirect::to('/login');
        }

        $id = (int) ($request->routeParams['id'] ?? 0);
        if ($id <= 0) {
            Flash::set('danger', 'Apartado invalido.');
            Redirect::to('/admin/apartados');
        }

        $amount = (float) ($request->body['amount'] ?? 0);
        $method = (string) ($request->body['payment_method'] ?? 'EFECTIVO');
        $reference = trim((string) ($request->body['reference'] ?? ''));

        $res = $this->layawayService->registerPayment($shopId, $id, $userId, $amount, $method, $reference !== '' ? $reference : null);
        if ($res['success']) {
            Flash::set('success', 'Abono registrado correctamente.');
        } else {
            Flash::set('danger', (string) ($res['error'] ?? 'No se pudo registrar el abono.'));
        }
        Redirect::to('/admin/apartados/documento/' . $id);
    }

    public function cancelar(Request $request): void
    {
        $shopId = Auth::shopId();
        if ($shopId === null) {
            Flash::set('danger', 'Sesion invalida.');
            Redirect::to('/login');
        }

        $id = (int) ($request->routeParams['id'] ?? 0);
        if ($id <= 0) {
            Flash::set('danger', 'Apartado invalido.');
            Redirect::to('/admin/apartados');
        }

        if ($this->layawayRepo->markCancelled($id, $shopId)) {
            Flash::set('success', 'Apartado cancelado.');
        } else {
            Flash::set('danger', 'No se pudo cancelar el apartado.');
        }

        Redirect::to('/admin/apartados/documento/' . $id);
    }

    public function ticket(Request $request): void
    {
        $shopId = Auth::shopId();
        if ($shopId === null) {
            Flash::set('danger', 'Sesion invalida.');
            Redirect::to('/login');
        }

        $id = (int) ($request->routeParams['id'] ?? 0);
        if ($id <= 0) {
            Flash::set('danger', 'Apartado invalido.');
            Redirect::to('/admin/apartados');
        }

        $layaway = $this->layawayRepo->findById($id, $shopId);
        if (!$layaway) {
            Flash::set('danger', 'Apartado no encontrado.');
            Redirect::to('/admin/apartados');
        }

        $items = $this->layawayRepo->getItems($id);
        $payments = $this->layawayRepo->getPayments($id);
        $shopRow = Database::fetch('SELECT name, address, phone FROM shops WHERE id = :id LIMIT 1', ['id' => $shopId]);

        $ticketHtml = $this->buildTicketHtml($layaway, $items, $payments, $shopRow ?: []);

        View::render('admin/pos/ticket', [
            'pageTitle' => 'Ticket de apartado',
            'sale' => ['id' => $id],
            'ticketHtml' => $ticketHtml,
            'flash' => Flash::consume(),
            'userName' => $this->getUserName(),
            'shopName' => $this->getShopName(),
        ]);
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

    /**
     * @param array<string,mixed> $layaway
     * @param array<int,array<string,mixed>> $items
     * @param array<int,array<string,mixed>> $payments
     * @param array<string,mixed> $shop
     */
    private function buildTicketHtml(array $layaway, array $items, array $payments, array $shop): string
    {
        $shopName = trim((string) ($shop['name'] ?? 'POS SaaS'));
        $folio = (int) ($layaway['folio'] ?? 0);
        $createdAt = (string) ($layaway['created_at'] ?? '');
        $customer = trim((string) ($layaway['customer_name'] ?? 'Cliente'));
        $seller = trim((string) ($layaway['seller_name'] ?? $layaway['created_by_name'] ?? 'Usuario'));
        $status = (string) ($layaway['status'] ?? 'OPEN');
        $total = (float) ($layaway['total'] ?? 0);
        $paid = (float) ($layaway['paid_total'] ?? 0);
        $balance = max(0.0, $total - $paid);

        ob_start();
        ?>
        <div class="pos-ticket" style="max-width:80mm;width:100%;margin:0 auto;box-sizing:border-box;font-family:Arial,Helvetica,sans-serif;font-size:11pt;line-height:1.35;color:#000;padding:0.5mm 1mm;">
            <div style="text-align:center;margin-bottom:0.5em;">
                <div style="font-weight:800;font-size:13pt;"><?= htmlspecialchars($shopName, ENT_QUOTES, 'UTF-8') ?></div>
                <div style="font-size:10pt;">Comprobante de apartado</div>
            </div>
            <div style="display:flex;justify-content:space-between;gap:8px;margin-bottom:0.6em;">
                <div>
                    <div><b>Folio:</b> #<?= (int) $folio ?></div>
                    <div><?= htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <div style="text-align:right;">
                    <div><b>Cliente:</b> <?= htmlspecialchars($customer, ENT_QUOTES, 'UTF-8') ?></div>
                    <div><b>Vendedor:</b> <?= htmlspecialchars($seller, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            </div>
            <div style="border-top:1px dashed #999;border-bottom:1px dashed #999;padding:0.4em 0;">
                <?php foreach ($items as $it): ?>
                    <?php
                    $qty = (float) ($it['quantity'] ?? 0);
                    $name = (string) ($it['product_name'] ?? 'Producto');
                    $lineTotal = (float) ($it['line_total'] ?? 0);
                    ?>
                    <div style="margin-bottom:0.35em;">
                        <div style="display:flex;justify-content:space-between;gap:6px;">
                            <div style="font-weight:700;"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></div>
                            <div style="font-weight:700;">$<?= number_format($lineTotal, 2, '.', ',') ?></div>
                        </div>
                        <div style="display:flex;justify-content:space-between;color:#222;">
                            <div><?= number_format($qty, 3, '.', ',') ?> x $<?= number_format((float) ($it['unit_price'] ?? 0), 2, '.', ',') ?></div>
                            <div>IVA <?= number_format((float) ($it['tax_percent'] ?? 0), 2, '.', ',') ?>%</div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div style="margin-top:0.5em;">
                <div style="display:flex;justify-content:space-between;"><span>Subtotal</span><b>$<?= number_format((float) ($layaway['subtotal'] ?? 0), 2, '.', ',') ?></b></div>
                <div style="display:flex;justify-content:space-between;"><span>Impuestos</span><b>$<?= number_format((float) ($layaway['tax_total'] ?? 0), 2, '.', ',') ?></b></div>
                <div style="display:flex;justify-content:space-between;font-size:12pt;font-weight:800;margin-top:0.2em;"><span>Total</span><span>$<?= number_format($total, 2, '.', ',') ?></span></div>
                <div style="display:flex;justify-content:space-between;"><span>Pagado</span><b>$<?= number_format($paid, 2, '.', ',') ?></b></div>
                <div style="display:flex;justify-content:space-between;"><span>Saldo</span><b><?= $balance > 0 ? '$' . number_format($balance, 2, '.', ',') : 'Liquidado' ?></b></div>
                <div style="display:flex;justify-content:space-between;"><span>Estado</span><b><?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?></b></div>
            </div>
            <?php if ($payments !== []): ?>
                <div style="margin-top:0.45em;border-top:1px dashed #999;padding-top:0.35em;">
                    <div style="font-weight:700;margin-bottom:0.2em;">Abonos</div>
                    <?php foreach ($payments as $p): ?>
                        <div style="display:flex;justify-content:space-between;gap:6px;">
                            <span><?= htmlspecialchars((string) ($p['payment_method'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                            <span>$<?= number_format((float) ($p['amount'] ?? 0), 2, '.', ',') ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div style="text-align:center;margin-top:0.65em;font-size:9pt;color:#111;">
                Gracias por su preferencia
            </div>
        </div>
        <?php
        return (string) ob_get_clean();
    }
}

