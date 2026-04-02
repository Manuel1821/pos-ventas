<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Redirect;
use App\Core\Request;
use App\Core\View;
use App\Database\Database;
use App\Repositories\QuotationRepository;
use App\Repositories\ShopRepository;
use App\Services\QuotationService;

class QuotationController
{
    private QuotationRepository $quotationRepo;
    private QuotationService $quotationService;
    private ShopRepository $shopRepo;

    public function __construct()
    {
        $this->quotationRepo = new QuotationRepository();
        $this->quotationService = new QuotationService();
        $this->shopRepo = new ShopRepository();
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
        } elseif ($tab === 'sold') {
            $status = 'SOLD';
        }

        $filters = [
            'q' => trim((string) ($request->query['q'] ?? '')),
            'status' => $status,
            'sort' => trim((string) ($request->query['sort'] ?? 'date_desc')),
        ];
        if (!in_array($filters['sort'], ['date_desc', 'date_asc', 'total_desc', 'total_asc'], true)) {
            $filters['sort'] = 'date_desc';
        }

        $history = $this->quotationRepo->listForShop($shopId, $page, $filters);

        View::render('admin/cotizaciones/indice', [
            'pageTitle' => 'Cotizaciones',
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

        View::render('admin/cotizaciones/crear', [
            'pageTitle' => 'Crear cotización',
            'customers' => $customers,
            'sellers' => $sellers,
            'defaultValidFrom' => date('Y-m-d'),
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

        $result = $this->quotationService->create($payload, $shopId, $userId);
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
            Flash::set('danger', 'Cotizacion invalida.');
            Redirect::to('/admin/cotizaciones');
        }

        $q = $this->quotationRepo->findById($id, $shopId);
        if (!$q) {
            Flash::set('danger', 'Cotizacion no encontrada.');
            Redirect::to('/admin/cotizaciones');
        }

        $items = $this->quotationRepo->getItems($id);
        $shopRow = $this->shopRepo->findById($shopId) ?: [];

        View::render('admin/cotizaciones/documento', [
            'pageTitle' => 'Cotización #' . (int) ($q['folio'] ?? 0),
            'quotation' => $q,
            'items' => $items,
            'shopRow' => $shopRow,
            'quotationPrint' => $this->buildQuotationPrintForView($shopRow),
            'shopQuotationMigrationNeeded' => !$this->shopRepo->hasQuotationPrintColumns(),
            'flash' => Flash::consume(),
            'userName' => $this->getUserName(),
            'shopName' => $this->getShopName(),
        ]);
    }

    public function marcarVendida(Request $request): void
    {
        $shopId = Auth::shopId();
        if ($shopId === null) {
            Flash::set('danger', 'Sesion invalida.');
            Redirect::to('/login');
        }

        $id = (int) ($request->routeParams['id'] ?? 0);
        if ($id <= 0) {
            Flash::set('danger', 'Cotizacion invalida.');
            Redirect::to('/admin/cotizaciones');
        }

        if ($this->quotationRepo->markSold($id, $shopId)) {
            Flash::set('success', 'Cotizacion marcada como vendida.');
        } else {
            Flash::set('danger', 'No se pudo actualizar la cotizacion.');
        }
        Redirect::to('/admin/cotizaciones');
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
     * Opciones de impresión de cotización (desde shops o valores por defecto).
     *
     * @param array<string, mixed> $shop
     * @return array{
     *   paper:string,
     *   margin_mm:int,
     *   scale_pct:int,
     *   show_sku:bool,
     *   show_tax_col:bool,
     *   show_signatures:bool,
     *   footer_note:string
     * }
     */
    private function buildQuotationPrintForView(array $shop): array
    {
        if (!$this->shopRepo->hasQuotationPrintColumns()) {
            return [
                'paper' => 'letter',
                'margin_mm' => 10,
                'scale_pct' => 100,
                'show_sku' => true,
                'show_tax_col' => true,
                'show_signatures' => true,
                'footer_note' => '',
            ];
        }

        $paper = strtolower(trim((string) ($shop['quotation_print_paper'] ?? 'letter')));
        if (!in_array($paper, ['letter', 'a4'], true)) {
            $paper = 'letter';
        }

        $mm = (int) ($shop['quotation_print_margin_mm'] ?? 10);
        if (!in_array($mm, [6, 8, 10, 12, 15], true)) {
            $mm = 10;
        }

        $scale = (int) ($shop['quotation_print_scale_pct'] ?? 100);
        if ($scale < 85) {
            $scale = 85;
        }
        if ($scale > 115) {
            $scale = 115;
        }

        return [
            'paper' => $paper,
            'margin_mm' => $mm,
            'scale_pct' => $scale,
            'show_sku' => ((int) ($shop['quotation_print_show_sku'] ?? 1)) === 1,
            'show_tax_col' => ((int) ($shop['quotation_print_show_tax_col'] ?? 1)) === 1,
            'show_signatures' => ((int) ($shop['quotation_print_show_signatures'] ?? 1)) === 1,
            'footer_note' => trim((string) ($shop['quotation_print_footer_note'] ?? '')),
        ];
    }
}
