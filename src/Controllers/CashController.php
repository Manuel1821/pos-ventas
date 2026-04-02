<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Redirect;
use App\Core\Request;
use App\Core\View;
use App\Repositories\ReportRepository;
use App\Services\CashierCutService;
use App\Services\CashService;

class CashController
{
    private CashService $cashService;

    public function __construct()
    {
        $this->cashService = new CashService();
    }

    /**
     * Corte por cajero: ventas por método de pago y efectivo esperado según el POS (sesión actual o histórica).
     */
    public function corteCajero(Request $request): void
    {
        $shopId = Auth::shopId();
        if ($shopId === null) {
            Flash::set('danger', 'Sesión inválida.');
            Redirect::to('/login');
        }
        $isAdmin = Auth::hasAnyRole(['admin']);
        $cutService = new CashierCutService();
        $reportRepo = new ReportRepository();

        $sessionIdQ = (int) ($request->query['sesion'] ?? 0);
        $session = $cutService->resolveSession($shopId, $sessionIdQ > 0 ? $sessionIdQ : null);

        $userIdQ = (int) ($request->query['usuario'] ?? 0);
        $targetUserId = $userIdQ > 0 ? $userIdQ : (int) Auth::userId();
        if (!$isAdmin && $targetUserId !== (int) Auth::userId()) {
            $targetUserId = (int) Auth::userId();
        }

        $cut = null;
        if ($session) {
            $cut = $cutService->getCutData($shopId, (int) $session['id'], $targetUserId);
        }

        $flash = Flash::consume();
        $sessionsList = $isAdmin ? $reportRepo->listCashSessions($shopId) : [];
        $usersList = $isAdmin ? $reportRepo->listUsers($shopId) : [];

        View::render('admin/caja/corte_cajero', [
            'pageTitle' => 'Corte por cajero',
            'session' => $session,
            'cut' => $cut,
            'targetUserId' => $targetUserId,
            'isAdmin' => $isAdmin,
            'sessionsList' => $sessionsList,
            'usersList' => $usersList,
            'userName' => $this->getUserName(),
            'shopName' => $this->getShopName(),
            'flash' => $flash,
            'basePath' => $this->getBasePath(),
        ]);
    }

    /**
     * Ticket imprimible del corte por cajero.
     */
    public function corteCajeroTicket(Request $request): void
    {
        $shopId = Auth::shopId();
        if ($shopId === null) {
            Flash::set('danger', 'Sesión inválida.');
            Redirect::to('/login');
        }
        $isAdmin = Auth::hasAnyRole(['admin']);
        $cutService = new CashierCutService();

        $sessionIdQ = (int) ($request->query['sesion'] ?? 0);
        $session = $cutService->resolveSession($shopId, $sessionIdQ > 0 ? $sessionIdQ : null);

        $userIdQ = (int) ($request->query['usuario'] ?? 0);
        $targetUserId = $userIdQ > 0 ? $userIdQ : (int) Auth::userId();
        if (!$isAdmin && $targetUserId !== (int) Auth::userId()) {
            $targetUserId = (int) Auth::userId();
        }

        $contadoRaw = trim((string) ($request->query['contado'] ?? ''));
        $contado = $contadoRaw === '' ? null : (float) str_replace(',', '.', $contadoRaw);
        if ($contado !== null && !is_finite($contado)) {
            $contado = null;
        }

        $cut = null;
        if ($session) {
            $cut = $cutService->getCutData($shopId, (int) $session['id'], $targetUserId);
        }

        $difference = null;
        if ($cut !== null && $contado !== null) {
            $difference = round($contado - $cut['expected_cash_hand'], 2);
        }

        $flash = Flash::consume();
        View::render('admin/caja/corte_cajero_ticket', [
            'pageTitle' => 'Ticket — Corte por cajero',
            'session' => $session,
            'cut' => $cut,
            'countedCash' => $contado,
            'cashDifference' => $difference,
            'userName' => $this->getUserName(),
            'shopName' => $this->getShopName(),
            'flash' => $flash,
            'basePath' => $this->getBasePath(),
        ]);
    }

    /**
     * Vista principal de caja: sin sesión muestra opción de apertura; con sesión muestra resumen y movimientos.
     */
    public function index(Request $request): void
    {
        $shopId = Auth::shopId();
        if ($shopId === null) {
            Flash::set('danger', 'Sesión inválida.');
            Redirect::to('/login');
        }
        $flash = Flash::consume();
        $summary = $this->cashService->getCurrentSummary($shopId);
        $movements = $summary ? $this->cashService->getCurrentMovements($shopId) : [];

        View::render('admin/caja/indice', [
            'pageTitle' => 'Caja',
            'summary' => $summary,
            'movements' => $movements,
            'userName' => $this->getUserName(),
            'shopName' => $this->getShopName(),
            'flash' => $flash,
            'basePath' => $this->getBasePath(),
        ]);
    }

    /**
     * Formulario de apertura de caja.
     */
    public function apertura(Request $request): void
    {
        $shopId = Auth::shopId();
        if ($shopId === null) {
            Flash::set('danger', 'Sesión inválida.');
            Redirect::to('/login');
        }
        if ($this->cashService->hasOpenSession($shopId)) {
            Flash::set('info', 'Ya hay una caja abierta.');
            Redirect::to('/admin/caja');
        }
        $flash = Flash::consume();
        View::render('admin/caja/apertura', [
            'pageTitle' => 'Abrir caja',
            'userName' => $this->getUserName(),
            'shopName' => $this->getShopName(),
            'flash' => $flash,
            'errors' => [],
            'old' => ['monto_inicial' => ''],
        ]);
    }

    /**
     * Procesar apertura de caja.
     */
    public function guardarApertura(Request $request): void
    {
        $shopId = Auth::shopId();
        if ($shopId === null) {
            Flash::set('danger', 'Sesión inválida.');
            Redirect::to('/login');
        }
        $monto = trim((string) ($request->body['monto_inicial'] ?? ''));
        $montoVal = $monto === '' ? 0.0 : (float) str_replace(',', '.', $monto);
        if ($monto !== '' && $montoVal < 0) {
            Flash::set('danger', 'El monto inicial no puede ser negativo.');
            Redirect::to('/admin/caja/apertura');
        }
        $result = $this->cashService->openSession($shopId, (int) Auth::userId(), $montoVal);
        if (!$result['success']) {
            Flash::set('danger', $result['error'] ?? 'Error al abrir la caja.');
            Redirect::to('/admin/caja/apertura');
        }
        Flash::set('success', 'Caja abierta correctamente.');
        Redirect::to('/admin/caja');
    }

    /**
     * Formulario de ingreso manual.
     */
    public function ingreso(Request $request): void
    {
        $this->showMovementForm($request, 'IN', 'Ingreso a caja');
    }

    /**
     * Formulario de retiro manual.
     */
    public function retiro(Request $request): void
    {
        $this->showMovementForm($request, 'OUT', 'Retiro de caja');
    }

    private function showMovementForm(Request $request, string $type, string $pageTitle): void
    {
        $shopId = Auth::shopId();
        if ($shopId === null) {
            Flash::set('danger', 'Sesión inválida.');
            Redirect::to('/login');
        }
        if (!$this->cashService->hasOpenSession($shopId)) {
            Flash::set('warning', 'No hay caja abierta. Ábrala primero.');
            Redirect::to('/admin/caja');
        }
        $flash = Flash::consume();
        View::render('admin/caja/movimiento', [
            'pageTitle' => $pageTitle,
            'tipo' => $type,
            'userName' => $this->getUserName(),
            'shopName' => $this->getShopName(),
            'flash' => $flash,
            'errors' => [],
            'old' => ['monto' => '', 'motivo' => ''],
        ]);
    }

    /**
     * Guardar ingreso manual.
     */
    public function guardarIngreso(Request $request): void
    {
        $this->saveMovement($request, 'IN', '/admin/caja/ingreso', 'Ingreso registrado correctamente.');
    }

    /**
     * Guardar retiro manual.
     */
    public function guardarRetiro(Request $request): void
    {
        $this->saveMovement($request, 'OUT', '/admin/caja/retiro', 'Retiro registrado correctamente.');
    }

    private function saveMovement(Request $request, string $type, string $redirectForm, string $successMsg): void
    {
        $shopId = Auth::shopId();
        if ($shopId === null) {
            Flash::set('danger', 'Sesión inválida.');
            Redirect::to('/login');
        }
        $monto = trim((string) ($request->body['monto'] ?? ''));
        $motivo = trim((string) ($request->body['motivo'] ?? ''));
        $montoVal = $monto === '' ? 0.0 : (float) str_replace(',', '.', $monto);
        if ($montoVal <= 0) {
            Flash::set('danger', 'El monto debe ser mayor a cero.');
            Redirect::to($redirectForm);
        }
        $result = $this->cashService->addMovement($shopId, (int) Auth::userId(), $type, $montoVal, $motivo !== '' ? $motivo : null);
        if (!$result['success']) {
            Flash::set('danger', $result['error'] ?? 'Error al registrar el movimiento.');
            Redirect::to($redirectForm);
        }
        Flash::set('success', $successMsg);
        Redirect::to('/admin/caja');
    }

    /**
     * Formulario de cierre de caja.
     */
    public function cierre(Request $request): void
    {
        $shopId = Auth::shopId();
        if ($shopId === null) {
            Flash::set('danger', 'Sesión inválida.');
            Redirect::to('/login');
        }
        $summary = $this->cashService->getCurrentSummary($shopId);
        if (!$summary) {
            Flash::set('warning', 'No hay caja abierta para cerrar.');
            Redirect::to('/admin/caja');
        }
        $flash = Flash::consume();
        View::render('admin/caja/cierre', [
            'pageTitle' => 'Cerrar caja',
            'summary' => $summary,
            'userName' => $this->getUserName(),
            'shopName' => $this->getShopName(),
            'flash' => $flash,
            'errors' => [],
            'old' => ['monto_contado' => '', 'observaciones' => ''],
        ]);
    }

    /**
     * Procesar cierre de caja.
     */
    public function guardarCierre(Request $request): void
    {
        $shopId = Auth::shopId();
        if ($shopId === null) {
            Flash::set('danger', 'Sesión inválida.');
            Redirect::to('/login');
        }
        $montoContado = trim((string) ($request->body['monto_contado'] ?? ''));
        $observaciones = trim((string) ($request->body['observaciones'] ?? ''));
        $counted = $montoContado === '' ? null : (float) str_replace(',', '.', $montoContado);
        $result = $this->cashService->closeSession($shopId, (int) Auth::userId(), $counted, $observaciones !== '' ? $observaciones : null);
        if (!$result['success']) {
            Flash::set('danger', $result['error'] ?? 'Error al cerrar la caja.');
            Redirect::to('/admin/caja/cierre');
        }
        Flash::set('success', 'Caja cerrada correctamente.');
        Redirect::to('/admin/caja');
    }

    /**
     * Historial de sesiones de caja.
     */
    public function historial(Request $request): void
    {
        $shopId = Auth::shopId();
        if ($shopId === null) {
            Flash::set('danger', 'Sesión inválida.');
            Redirect::to('/login');
        }
        $flash = Flash::consume();
        $page = max(1, (int) ($request->query['pagina'] ?? 1));
        $filters = [
            'desde' => trim((string) ($request->query['desde'] ?? '')),
            'hasta' => trim((string) ($request->query['hasta'] ?? '')),
            'estado' => isset($request->query['estado']) ? (string) $request->query['estado'] : '',
        ];
        if ($filters['estado'] !== '' && !in_array($filters['estado'], ['OPEN', 'CLOSED'], true)) {
            $filters['estado'] = '';
        }
        $result = $this->cashService->getHistory($shopId, $page, $filters);

        View::render('admin/caja/historial', [
            'pageTitle' => 'Historial de caja',
            'items' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'total_pages' => $result['total_pages'],
            'per_page' => $result['per_page'],
            'filters' => $filters,
            'userName' => $this->getUserName(),
            'shopName' => $this->getShopName(),
            'flash' => $flash,
        ]);
    }

    /**
     * Detalle de una sesión (historial).
     */
    public function detalle(Request $request): void
    {
        $shopId = Auth::shopId();
        if ($shopId === null) {
            Flash::set('danger', 'Sesión inválida.');
            Redirect::to('/login');
        }
        $id = (int) ($request->routeParams['id'] ?? 0);
        $detail = $this->cashService->getSessionDetail($id, $shopId);
        if (!$detail) {
            Flash::set('danger', 'Sesión no encontrada.');
            Redirect::to('/admin/caja/historial');
        }
        $flash = Flash::consume();
        View::render('admin/caja/detalle', [
            'pageTitle' => 'Detalle de sesión de caja',
            'detail' => $detail,
            'userName' => $this->getUserName(),
            'shopName' => $this->getShopName(),
            'flash' => $flash,
        ]);
    }

    private function getUserName(): string
    {
        $pdo = \App\Database\Database::pdo();
        $stmt = $pdo->prepare('SELECT first_name, last_name FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => Auth::userId()]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
        return $name !== '' ? $name : 'Usuario';
    }

    private function getShopName(): string
    {
        $pdo = \App\Database\Database::pdo();
        $stmt = $pdo->prepare('SELECT name FROM shops WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => Auth::shopId()]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (string) ($row['name'] ?? '');
    }

    private function getBasePath(): string
    {
        $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
        $basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
        return ($basePath === '.' || $basePath === '' || $basePath === '\\' || $basePath === '/') ? '' : $basePath;
    }
}
