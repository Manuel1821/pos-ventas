<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Redirect;
use App\Core\Request;
use App\Core\View;
use App\Database\Database;
use App\Repositories\ReportRepository;

class ReportController
{
    private ReportRepository $reportRepo;

    private const TABS = [
        'ventas_periodo',
        'ventas_metodo_pago',
        'caja',
        'gastos',
        'utilidad',
        'inventario',
    ];

    public function __construct()
    {
        $this->reportRepo = new ReportRepository();
    }

    public function index(Request $request): void
    {
        $shopId = $this->requireShopId();
        $tab = $this->normalizeTab((string) ($request->query['reporte'] ?? 'ventas_periodo'));
        $filters = $this->collectFilters($request);
        $reportData = $this->runReport($shopId, $tab, $filters);

        View::render('admin/reportes/indice', [
            'pageTitle' => 'Reportes',
            'tab' => $tab,
            'tabs' => self::TABS,
            'filters' => $filters,
            'reportData' => $reportData,
            'users' => $this->reportRepo->listUsers($shopId),
            'cashSessions' => $this->reportRepo->listCashSessions($shopId),
            'expenseCategories' => $this->reportRepo->listExpenseCategories($shopId),
            'productCategories' => $this->reportRepo->listProductCategories($shopId),
            'paymentMethods' => ['EFECTIVO', 'TARJETA', 'TRANSFERENCIA', 'MIXTO', 'OTRO'],
            'flash' => Flash::consume(),
            'userName' => $this->getUserName(),
            'shopName' => $this->getShopName(),
        ]);
    }

    public function export(Request $request): void
    {
        $shopId = $this->requireShopId();
        $tab = $this->normalizeTab((string) ($request->query['reporte'] ?? 'ventas_periodo'));
        $filters = $this->collectFilters($request);
        $reportData = $this->runReport($shopId, $tab, $filters);

        $csv = $this->buildCsv($tab, $reportData);
        $filename = 'reporte_' . $tab . '_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo "\xEF\xBB\xBF";
        echo $csv;
    }

    /**
     * @param array<string,mixed> $filters
     * @return array<string,mixed>
     */
    private function runReport(int $shopId, string $tab, array $filters): array
    {
        return match ($tab) {
            'ventas_metodo_pago' => $this->reportRepo->salesByPaymentMethod($shopId, [
                'desde' => (string) $filters['desde'],
                'hasta' => (string) $filters['hasta'],
                'user_id' => (int) $filters['user_id'],
                'cash_session_id' => (int) $filters['cash_session_id'],
                'payment_method' => (string) $filters['payment_method'],
            ]),
            'caja' => $this->reportRepo->cashSessionsReport($shopId, [
                'desde' => (string) $filters['desde'],
                'hasta' => (string) $filters['hasta'],
                'user_id' => (int) $filters['user_id'],
                'cash_session_id' => (int) $filters['cash_session_id'],
            ]),
            'gastos' => $this->reportRepo->expensesReport($shopId, [
                'desde' => (string) $filters['desde'],
                'hasta' => (string) $filters['hasta'],
                'expense_category_id' => (int) $filters['expense_category_id'],
                'supplier' => (string) $filters['supplier'],
                'payment_method' => (string) $filters['payment_method'],
                'user_id' => (int) $filters['user_id'],
            ]),
            'utilidad' => $this->reportRepo->basicProfitReport($shopId, [
                'desde' => (string) $filters['desde'],
                'hasta' => (string) $filters['hasta'],
                'user_id' => (int) $filters['user_id'],
                'cash_session_id' => (int) $filters['cash_session_id'],
                'expense_category_id' => (int) $filters['expense_category_id'],
                'supplier' => (string) $filters['supplier'],
                'payment_method' => (string) $filters['payment_method'],
            ]),
            'inventario' => $this->reportRepo->inventoryCurrentReport($shopId, [
                'category_id' => (int) $filters['category_id'],
                'status' => (string) $filters['status'],
                'availability' => (string) $filters['availability'],
                'q' => (string) $filters['q'],
            ]),
            default => $this->reportRepo->salesByPeriod($shopId, [
                'desde' => (string) $filters['desde'],
                'hasta' => (string) $filters['hasta'],
                'user_id' => (int) $filters['user_id'],
                'cash_session_id' => (int) $filters['cash_session_id'],
            ]),
        };
    }

    /**
     * @return array<string,mixed>
     */
    private function collectFilters(Request $request): array
    {
        $desde = trim((string) ($request->query['desde'] ?? ''));
        $hasta = trim((string) ($request->query['hasta'] ?? ''));
        if (!$this->isValidDate($desde)) {
            $desde = '';
        }
        if (!$this->isValidDate($hasta)) {
            $hasta = '';
        }
        if ($desde !== '' && $hasta !== '' && strtotime($desde) > strtotime($hasta)) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        $paymentMethod = strtoupper(trim((string) ($request->query['payment_method'] ?? '')));
        if (!in_array($paymentMethod, ['', 'EFECTIVO', 'TARJETA', 'TRANSFERENCIA', 'MIXTO', 'OTRO'], true)) {
            $paymentMethod = '';
        }

        $status = strtoupper(trim((string) ($request->query['status'] ?? '')));
        if (!in_array($status, ['', 'ACTIVE', 'INACTIVE'], true)) {
            $status = '';
        }

        $availability = strtoupper(trim((string) ($request->query['availability'] ?? '')));
        if (!in_array($availability, ['', 'IN_STOCK', 'OUT_OF_STOCK'], true)) {
            $availability = '';
        }

        return [
            'desde' => $desde,
            'hasta' => $hasta,
            'user_id' => max(0, (int) ($request->query['user_id'] ?? 0)),
            'cash_session_id' => max(0, (int) ($request->query['cash_session_id'] ?? 0)),
            'expense_category_id' => max(0, (int) ($request->query['expense_category_id'] ?? 0)),
            'category_id' => max(0, (int) ($request->query['category_id'] ?? 0)),
            'payment_method' => $paymentMethod,
            'supplier' => trim((string) ($request->query['supplier'] ?? '')),
            'q' => trim((string) ($request->query['q'] ?? '')),
            'status' => $status,
            'availability' => $availability,
        ];
    }

    private function normalizeTab(string $tab): string
    {
        return in_array($tab, self::TABS, true) ? $tab : 'ventas_periodo';
    }

    /**
     * @param array<string,mixed> $reportData
     */
    private function buildCsv(string $tab, array $reportData): string
    {
        $rows = [];
        $rows[] = ['Reporte', $tab];
        $rows[] = ['Generado', date('Y-m-d H:i:s')];
        $rows[] = [];

        $summary = (array) ($reportData['summary'] ?? []);
        if ($summary !== []) {
            $rows[] = ['Resumen'];
            foreach ($summary as $key => $value) {
                $rows[] = [(string) $key, (string) $value];
            }
            $rows[] = [];
        }

        $items = (array) ($reportData['items'] ?? []);
        if ($items !== []) {
            $headers = array_keys((array) $items[0]);
            $rows[] = $headers;
            foreach ($items as $item) {
                $line = [];
                foreach ($headers as $header) {
                    $line[] = (string) ((array) $item)[$header];
                }
                $rows[] = $line;
            }
        } else {
            $rows[] = ['Sin registros para exportar'];
        }

        $fp = fopen('php://temp', 'r+');
        if ($fp === false) {
            return '';
        }
        foreach ($rows as $row) {
            fputcsv($fp, $row);
        }
        rewind($fp);
        $csv = (string) stream_get_contents($fp);
        fclose($fp);
        return $csv;
    }

    private function requireShopId(): int
    {
        $shopId = Auth::shopId();
        if ($shopId === null) {
            Flash::set('danger', 'Sesion invalida.');
            Redirect::to('/login');
        }
        return $shopId;
    }

    private function isValidDate(string $date): bool
    {
        if ($date === '') {
            return false;
        }
        $dt = \DateTime::createFromFormat('Y-m-d', $date);
        return $dt instanceof \DateTime && $dt->format('Y-m-d') === $date;
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
}

