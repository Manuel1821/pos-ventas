<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Redirect;
use App\Core\Request;
use App\Core\View;
use App\Repositories\CustomerRepository;
use App\Services\CustomerDebtPaymentService;
use App\Validation\CustomerValidator;

class CustomerController
{
    private CustomerRepository $customerRepo;

    public function __construct()
    {
        $this->customerRepo = new CustomerRepository();
    }

    public function index(Request $request): void
    {
        $shopId = Auth::shopId();
        if ($shopId === null) {
            Flash::set('danger', 'Sesión inválida.');
            Redirect::to('/login');
        }
        $flash = Flash::consume();
        $page = max(1, (int) ($request->query['pagina'] ?? 1));
        $search = trim((string) ($request->query['buscar'] ?? ''));
        $status = isset($request->query['estado']) ? (string) $request->query['estado'] : null;
        if ($status !== null && !in_array($status, ['ACTIVE', 'INACTIVE'], true)) {
            $status = null;
        }
        $result = $this->customerRepo->listByShop($shopId, $page, $search, $status);

        View::render('admin/clientes/indice', [
            'customers' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'total_pages' => $result['total_pages'],
            'per_page' => $result['per_page'],
            'search' => $search,
            'statusFilter' => $status,
            'userName' => $this->getUserName(),
            'shopName' => $this->getShopName(),
            'flash' => $flash,
        ]);
    }

    public function deuda(Request $request): void
    {
        $shopId = Auth::shopId();
        if ($shopId === null) {
            Flash::set('danger', 'Sesión inválida.');
            Redirect::to('/login');
        }

        $flash = Flash::consume();
        $id = (int) ($request->routeParams['id'] ?? 0);
        if ($id <= 0) {
            Flash::set('danger', 'Cliente inválido.');
            Redirect::to('/admin/clientes');
        }

        $customer = $this->customerRepo->findById($id, $shopId);
        if (!$customer) {
            Flash::set('danger', 'Cliente no encontrado.');
            Redirect::to('/admin/clientes');
        }

        $debt = $this->customerRepo->getOpenDebtDetailsByCustomer($shopId, $id);
        $debtSettlements = $this->customerRepo->listDebtSettlementsByCustomer($shopId, $id);

        View::render('admin/clientes/deuda', [
            'pageTitle' => 'Deuda del cliente',
            'customer' => $customer,
            'debt' => $debt,
            'debtSettlements' => $debtSettlements,
            'flash' => $flash,
            'userName' => $this->getUserName(),
            'shopName' => $this->getShopName(),
        ]);
    }

    /**
     * Registra abono o liquidación de deuda del cliente (ventas abiertas).
     */
    public function registrarPagoDeuda(Request $request): void
    {
        $shopId = Auth::shopId();
        if ($shopId === null) {
            Flash::set('danger', 'Sesión inválida.');
            Redirect::to('/login');
        }
        $id = (int) ($request->routeParams['id'] ?? 0);
        if ($id <= 0) {
            Flash::set('danger', 'Cliente inválido.');
            Redirect::to('/admin/clientes');
        }

        $accion = strtolower(trim((string) ($request->body['accion'] ?? '')));
        $montoRaw = trim((string) ($request->body['monto'] ?? ''));
        $monto = $montoRaw === '' ? 0.0 : (float) str_replace(',', '.', $montoRaw);
        if (!is_finite($monto)) {
            $monto = 0.0;
        }
        $method = trim((string) ($request->body['payment_method'] ?? 'EFECTIVO'));
        $obsRaw = trim((string) ($request->body['observaciones'] ?? ''));
        if ($obsRaw !== '' && function_exists('mb_strlen') && mb_strlen($obsRaw) > 500) {
            $obsRaw = mb_substr($obsRaw, 0, 500);
        }
        $observaciones = $obsRaw !== '' ? $obsRaw : null;

        $svc = new CustomerDebtPaymentService();
        $result = $svc->registerPayment($shopId, $id, (int) Auth::userId(), $accion, $monto, $method, $observaciones);
        if (!($result['success'] ?? false)) {
            Flash::set('danger', $result['error'] ?? 'No se pudo registrar el pago.');
            Redirect::to('/admin/clientes/deuda/' . $id);
        }
        $applied = (float) ($result['applied_total'] ?? 0);
        Flash::set(
            'success',
            'Cobro registrado correctamente por $' . number_format($applied, 2, '.', ',') . '.'
        );
        Redirect::to('/admin/clientes/deuda/' . $id);
    }

    public function create(Request $request): void
    {
        $shopId = Auth::shopId();
        if ($shopId === null) {
            Flash::set('danger', 'Sesión inválida.');
            Redirect::to('/login');
        }
        $flash = Flash::consume();
        View::render('admin/clientes/crear', [
            'userName' => $this->getUserName(),
            'shopName' => $this->getShopName(),
            'flash' => $flash,
            'errors' => [],
            'old' => [],
        ]);
    }

    public function store(Request $request): void
    {
        $shopId = Auth::shopId();
        if ($shopId === null) {
            Flash::set('danger', 'Sesión inválida.');
            Redirect::to('/login');
        }
        $body = $request->body;
        $old = [
            'name' => trim((string) ($body['name'] ?? '')),
            'phone' => trim((string) ($body['phone'] ?? '')),
            'email' => trim((string) ($body['email'] ?? '')),
            'address' => trim((string) ($body['address'] ?? '')),
            'rfc' => trim((string) ($body['rfc'] ?? '')),
            'notes' => trim((string) ($body['notes'] ?? '')),
        ];
        $errors = CustomerValidator::validate($old);
        if (($old['email'] ?? '') !== '' && $this->customerRepo->emailExistsInShop($old['email'], $shopId)) {
            $errors[] = 'El correo ya está registrado para otro cliente.';
        }
        if ($errors !== []) {
            View::render('admin/clientes/crear', [
                'userName' => $this->getUserName(),
                'shopName' => $this->getShopName(),
                'flash' => null,
                'errors' => $errors,
                'old' => $old,
            ]);
            return;
        }
        $data = [
            'name' => $old['name'],
            'phone' => $old['phone'] !== '' ? $old['phone'] : null,
            'email' => $old['email'] !== '' ? $old['email'] : null,
            'address' => $old['address'] !== '' ? $old['address'] : null,
            'rfc' => $old['rfc'] !== '' ? $old['rfc'] : null,
            'notes' => $old['notes'] !== '' ? $old['notes'] : null,
            'status' => 'ACTIVE',
        ];
        $this->customerRepo->create($shopId, $data);
        Flash::set('success', 'Cliente registrado correctamente.');
        Redirect::to('/admin/clientes');
    }

    public function edit(Request $request): void
    {
        $shopId = Auth::shopId();
        if ($shopId === null) {
            Flash::set('danger', 'Sesión inválida.');
            Redirect::to('/login');
        }
        $id = (int) ($request->routeParams['id'] ?? 0);
        $customer = $id ? $this->customerRepo->findById($id, $shopId) : null;
        if (!$customer) {
            Flash::set('danger', 'Cliente no encontrado.');
            Redirect::to('/admin/clientes');
        }
        $flash = Flash::consume();
        View::render('admin/clientes/editar', [
            'customer' => $customer,
            'userName' => $this->getUserName(),
            'shopName' => $this->getShopName(),
            'flash' => $flash,
            'errors' => [],
            'old' => $customer,
        ]);
    }

    public function update(Request $request): void
    {
        $shopId = Auth::shopId();
        if ($shopId === null) {
            Flash::set('danger', 'Sesión inválida.');
            Redirect::to('/login');
        }
        $id = (int) ($request->routeParams['id'] ?? 0);
        $customer = $id ? $this->customerRepo->findById($id, $shopId) : null;
        if (!$customer) {
            Flash::set('danger', 'Cliente no encontrado.');
            Redirect::to('/admin/clientes');
        }
        $body = $request->body;
        $isPublic = !empty($customer['is_public']);
        $old = [
            'name' => trim((string) ($body['name'] ?? '')),
            'phone' => $isPublic ? null : trim((string) ($body['phone'] ?? '')),
            'email' => $isPublic ? null : trim((string) ($body['email'] ?? '')),
            'address' => $isPublic ? null : trim((string) ($body['address'] ?? '')),
            'rfc' => $isPublic ? null : trim((string) ($body['rfc'] ?? '')),
            'notes' => $isPublic ? null : trim((string) ($body['notes'] ?? '')),
            'status' => $isPublic ? 'ACTIVE' : (isset($body['status']) ? (string) $body['status'] : ($customer['status'] ?? 'ACTIVE')),
        ];
        if (!$isPublic) {
            $old['phone'] = $old['phone'] ?? '';
            $old['email'] = $old['email'] ?? '';
            $old['address'] = $old['address'] ?? '';
            $old['rfc'] = $old['rfc'] ?? '';
            $old['notes'] = $old['notes'] ?? '';
        }
        if (!in_array($old['status'], ['ACTIVE', 'INACTIVE'], true)) {
            $old['status'] = 'ACTIVE';
        }
        $errors = CustomerValidator::validate(array_filter($old, fn($v) => $v !== null));
        if (!$isPublic && ($old['email'] ?? '') !== '' && $this->customerRepo->emailExistsInShop($old['email'], $shopId, $id)) {
            $errors[] = 'El correo ya está registrado para otro cliente.';
        }
        if ($errors !== []) {
            View::render('admin/clientes/editar', [
                'customer' => $customer,
                'userName' => $this->getUserName(),
                'shopName' => $this->getShopName(),
                'flash' => null,
                'errors' => $errors,
                'old' => array_merge($customer, $old),
            ]);
            return;
        }
        $data = [
            'name' => $old['name'],
            'phone' => $isPublic ? null : ($old['phone'] !== '' ? $old['phone'] : null),
            'email' => $isPublic ? null : ($old['email'] !== '' ? $old['email'] : null),
            'address' => $isPublic ? null : ($old['address'] !== '' ? $old['address'] : null),
            'rfc' => $isPublic ? null : ($old['rfc'] !== '' ? $old['rfc'] : null),
            'notes' => $isPublic ? null : ($old['notes'] !== '' ? $old['notes'] : null),
            'status' => $old['status'],
        ];
        $this->customerRepo->update($id, $shopId, $data);
        Flash::set('success', $isPublic ? 'Cliente genérico actualizado.' : 'Cliente actualizado correctamente.');
        Redirect::to('/admin/clientes');
    }

    public function toggleStatus(Request $request): void
    {
        $shopId = Auth::shopId();
        if ($shopId === null) {
            Flash::set('danger', 'Sesión inválida.');
            Redirect::to('/login');
        }
        $id = (int) ($request->routeParams['id'] ?? 0);
        $newStatus = $this->customerRepo->toggleStatus($id, $shopId);
        if ($newStatus === null) {
            Flash::set('danger', 'No se puede cambiar el estado de este cliente (genérico o no encontrado).');
        } else {
            Flash::set('success', $newStatus === 'ACTIVE' ? 'Cliente activado.' : 'Cliente desactivado.');
        }
        Redirect::to('/admin/clientes');
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
}
