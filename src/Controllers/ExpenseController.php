<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Redirect;
use App\Core\Request;
use App\Core\View;
use App\Database\Database;
use App\Repositories\ExpenseCategoryRepository;
use App\Repositories\ExpenseRepository;
use App\Validation\ExpenseValidator;

class ExpenseController
{
    private ExpenseRepository $expenseRepo;
    private ExpenseCategoryRepository $categoryRepo;

    public function __construct()
    {
        $this->expenseRepo = new ExpenseRepository();
        $this->categoryRepo = new ExpenseCategoryRepository();
    }

    public function index(Request $request): void
    {
        $shopId = $this->requireShopId();
        $page = max(1, (int) ($request->query['pagina'] ?? 1));
        $filters = [
            'q' => trim((string) ($request->query['q'] ?? '')),
            'desde' => trim((string) ($request->query['desde'] ?? '')),
            'hasta' => trim((string) ($request->query['hasta'] ?? '')),
            'expense_category_id' => (int) ($request->query['expense_category_id'] ?? 0),
            'supplier' => trim((string) ($request->query['supplier'] ?? '')),
            'payment_method' => strtoupper(trim((string) ($request->query['payment_method'] ?? ''))),
            'user_id' => (int) ($request->query['user_id'] ?? 0),
            'estado' => strtoupper(trim((string) ($request->query['estado'] ?? ''))),
        ];
        if ($filters['payment_method'] !== '' && !in_array($filters['payment_method'], ExpenseValidator::PAYMENT_METHODS, true)) {
            $filters['payment_method'] = '';
        }
        if (!in_array($filters['estado'], ['', 'ALL', 'CANCELLED'], true)) {
            $filters['estado'] = '';
        }

        $history = $this->expenseRepo->listForShop($shopId, $page, $filters);
        $categories = $this->categoryRepo->listByShop($shopId);
        $users = $this->listShopUsers($shopId);

        View::render('admin/gastos/indice', [
            'pageTitle' => 'Gastos',
            'items' => $history['items'],
            'total' => $history['total'],
            'page' => $history['page'],
            'total_pages' => $history['total_pages'],
            'filters' => $filters,
            'categories' => $categories,
            'users' => $users,
            'paymentMethods' => ExpenseValidator::PAYMENT_METHODS,
            'flash' => Flash::consume(),
            'userName' => $this->getUserName(),
            'shopName' => $this->getShopName(),
        ]);
    }

    public function detalle(Request $request): void
    {
        $shopId = $this->requireShopId();
        $id = (int) ($request->routeParams['id'] ?? 0);
        $row = $id ? $this->expenseRepo->findById($id, $shopId) : null;
        if (!$row) {
            Flash::set('danger', 'Gasto no encontrado.');
            Redirect::to('/admin/gastos');
        }
        View::render('admin/gastos/detalle', [
            'pageTitle' => 'Detalle del gasto',
            'expense' => $row,
            'paymentMethods' => ExpenseValidator::PAYMENT_METHODS,
            'flash' => Flash::consume(),
            'userName' => $this->getUserName(),
            'shopName' => $this->getShopName(),
        ]);
    }

    public function create(Request $request): void
    {
        $shopId = $this->requireShopId();
        $categories = $this->categoryRepo->listByShop($shopId, true);
        View::render('admin/gastos/crear', [
            'pageTitle' => 'Registrar gasto',
            'categories' => $categories,
            'paymentMethods' => ExpenseValidator::PAYMENT_METHODS,
            'userName' => $this->getUserName(),
            'shopName' => $this->getShopName(),
            'flash' => Flash::consume(),
            'errors' => [],
            'old' => $this->defaultOldForm(),
        ]);
    }

    public function store(Request $request): void
    {
        $shopId = $this->requireShopId();
        $categories = $this->categoryRepo->listByShop($shopId, true);
        $old = $this->collectOldFromBody($request->body);
        $errors = ExpenseValidator::validate($request->body);
        $catId = (int) ($request->body['expense_category_id'] ?? 0);
        if ($catId > 0 && $this->expenseRepo->findActiveCategory($catId, $shopId) === null) {
            $errors[] = 'La categoría no existe, no pertenece a tu tienda o está inactiva.';
        }
        if ($errors !== []) {
            View::render('admin/gastos/crear', [
                'pageTitle' => 'Registrar gasto',
                'categories' => $categories,
                'paymentMethods' => ExpenseValidator::PAYMENT_METHODS,
                'userName' => $this->getUserName(),
                'shopName' => $this->getShopName(),
                'flash' => null,
                'errors' => $errors,
                'old' => $old,
            ]);
            return;
        }
        $amount = ExpenseValidator::parsePositiveAmount($request->body['amount'] ?? null);
        $occurred = ExpenseValidator::normalizeOccurredAt(trim((string) ($request->body['occurred_at'] ?? '')));
        $pm = strtoupper(trim((string) ($request->body['payment_method'] ?? '')));
        $this->expenseRepo->create(
            $shopId,
            $catId,
            trim((string) ($request->body['concept'] ?? '')),
            $amount,
            0.0,
            $amount,
            $pm,
            trim((string) ($request->body['supplier_name'] ?? '')) ?: null,
            trim((string) ($request->body['reference'] ?? '')) ?: null,
            $occurred,
            trim((string) ($request->body['notes'] ?? '')) ?: null,
            (int) Auth::userId()
        );
        Flash::set('success', 'Gasto registrado correctamente.');
        Redirect::to('/admin/gastos');
    }

    public function edit(Request $request): void
    {
        $shopId = $this->requireShopId();
        $id = (int) ($request->routeParams['id'] ?? 0);
        $row = $id ? $this->expenseRepo->findById($id, $shopId) : null;
        if (!$row) {
            Flash::set('danger', 'Gasto no encontrado.');
            Redirect::to('/admin/gastos');
        }
        if (($row['status'] ?? '') === 'CANCELLED') {
            Flash::set('warning', 'Este gasto está anulado y no puede editarse.');
            Redirect::to('/admin/gastos/detalle/' . $id);
        }
        $categories = $this->categoryRepo->listByShop($shopId, true);
        $old = $this->rowToOld($row);
        View::render('admin/gastos/editar', [
            'pageTitle' => 'Editar gasto',
            'expense' => $row,
            'categories' => $categories,
            'paymentMethods' => ExpenseValidator::PAYMENT_METHODS,
            'userName' => $this->getUserName(),
            'shopName' => $this->getShopName(),
            'flash' => Flash::consume(),
            'errors' => [],
            'old' => $old,
        ]);
    }

    public function update(Request $request): void
    {
        $shopId = $this->requireShopId();
        $id = (int) ($request->routeParams['id'] ?? 0);
        $row = $id ? $this->expenseRepo->findById($id, $shopId) : null;
        if (!$row) {
            Flash::set('danger', 'Gasto no encontrado.');
            Redirect::to('/admin/gastos');
        }
        if (($row['status'] ?? '') === 'CANCELLED') {
            Flash::set('warning', 'Este gasto está anulado y no puede editarse.');
            Redirect::to('/admin/gastos');
        }
        $categories = $this->categoryRepo->listByShop($shopId, true);
        $old = $this->collectOldFromBody($request->body);
        $old['id'] = $id;
        $errors = ExpenseValidator::validate($request->body);
        $catId = (int) ($request->body['expense_category_id'] ?? 0);
        if ($catId > 0 && $this->expenseRepo->findActiveCategory($catId, $shopId) === null) {
            $errors[] = 'La categoría no existe, no pertenece a tu tienda o está inactiva.';
        }
        if ($errors !== []) {
            View::render('admin/gastos/editar', [
                'pageTitle' => 'Editar gasto',
                'expense' => $row,
                'categories' => $categories,
                'paymentMethods' => ExpenseValidator::PAYMENT_METHODS,
                'userName' => $this->getUserName(),
                'shopName' => $this->getShopName(),
                'flash' => null,
                'errors' => $errors,
                'old' => $old,
            ]);
            return;
        }
        $amount = ExpenseValidator::parsePositiveAmount($request->body['amount'] ?? null);
        $occurred = ExpenseValidator::normalizeOccurredAt(trim((string) ($request->body['occurred_at'] ?? '')));
        $pm = strtoupper(trim((string) ($request->body['payment_method'] ?? '')));
        $ok = $this->expenseRepo->update(
            $id,
            $shopId,
            $catId,
            trim((string) ($request->body['concept'] ?? '')),
            $amount,
            0.0,
            $amount,
            $pm,
            trim((string) ($request->body['supplier_name'] ?? '')) ?: null,
            trim((string) ($request->body['reference'] ?? '')) ?: null,
            $occurred,
            trim((string) ($request->body['notes'] ?? '')) ?: null
        );
        if (!$ok) {
            Flash::set('danger', 'No se pudo actualizar el gasto.');
            Redirect::to('/admin/gastos/editar/' . $id);
        }
        Flash::set('success', 'Gasto actualizado correctamente.');
        Redirect::to('/admin/gastos');
    }

    public function anular(Request $request): void
    {
        $shopId = $this->requireShopId();
        $id = (int) ($request->routeParams['id'] ?? 0);
        if ($id <= 0) {
            Flash::set('danger', 'Gasto no válido.');
            Redirect::to('/admin/gastos');
        }
        if ($this->expenseRepo->cancel($id, $shopId)) {
            Flash::set('success', 'Gasto anulado (baja lógica). Permanece en el historial para auditoría.');
        } else {
            Flash::set('danger', 'No se pudo anular el gasto (quizá ya estaba anulado).');
        }
        Redirect::to('/admin/gastos');
    }

    private function requireShopId(): int
    {
        $shopId = Auth::shopId();
        if ($shopId === null) {
            Flash::set('danger', 'Sesión inválida.');
            Redirect::to('/login');
        }
        return $shopId;
    }

    /**
     * @return array<int, array{id:int,name:string}>
     */
    private function listShopUsers(int $shopId): array
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

    /**
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     */
    private function collectOldFromBody(array $body): array
    {
        return [
            'expense_category_id' => (int) ($body['expense_category_id'] ?? 0),
            'concept' => trim((string) ($body['concept'] ?? '')),
            'amount' => trim((string) ($body['amount'] ?? '')),
            'occurred_at' => trim((string) ($body['occurred_at'] ?? '')),
            'payment_method' => trim((string) ($body['payment_method'] ?? '')),
            'supplier_name' => trim((string) ($body['supplier_name'] ?? '')),
            'reference' => trim((string) ($body['reference'] ?? '')),
            'notes' => trim((string) ($body['notes'] ?? '')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultOldForm(): array
    {
        $now = new \DateTimeImmutable('now');
        return [
            'expense_category_id' => 0,
            'concept' => '',
            'amount' => '',
            'occurred_at' => $now->format('Y-m-d\TH:i'),
            'payment_method' => 'EFECTIVO',
            'supplier_name' => '',
            'reference' => '',
            'notes' => '',
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function rowToOld(array $row): array
    {
        $at = (string) ($row['occurred_at'] ?? '');
        $occurredLocal = $at;
        if ($at !== '' && preg_match('/^(\d{4}-\d{2}-\d{2}) (\d{2}:\d{2})/', $at, $m)) {
            $occurredLocal = $m[1] . 'T' . $m[2];
        }
        return [
            'expense_category_id' => (int) ($row['expense_category_id'] ?? 0),
            'concept' => (string) ($row['concept'] ?? ''),
            'amount' => (string) ($row['total'] ?? ''),
            'occurred_at' => $occurredLocal,
            'payment_method' => (string) ($row['payment_method'] ?? 'EFECTIVO'),
            'supplier_name' => (string) ($row['supplier_name'] ?? ''),
            'reference' => (string) ($row['reference'] ?? ''),
            'notes' => (string) ($row['notes'] ?? ''),
        ];
    }
}
