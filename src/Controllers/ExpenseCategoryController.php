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
use App\Validation\ExpenseCategoryValidator;

class ExpenseCategoryController
{
    private ExpenseCategoryRepository $repo;

    public function __construct()
    {
        $this->repo = new ExpenseCategoryRepository();
    }

    public function index(Request $request): void
    {
        $shopId = $this->requireShopId();
        View::render('admin/gastos/categorias/indice', [
            'categories' => $this->repo->listByShop($shopId),
            'userName' => $this->getUserName(),
            'shopName' => $this->getShopName(),
            'flash' => Flash::consume(),
        ]);
    }

    public function create(Request $request): void
    {
        $this->requireShopId();
        View::render('admin/gastos/categorias/crear', [
            'userName' => $this->getUserName(),
            'shopName' => $this->getShopName(),
            'flash' => Flash::consume(),
            'errors' => [],
            'old' => [],
        ]);
    }

    public function store(Request $request): void
    {
        $shopId = $this->requireShopId();
        $name = trim((string) ($request->body['name'] ?? ''));
        $old = ['name' => $name];
        $errors = ExpenseCategoryValidator::validate(['name' => $name]);
        if ($this->repo->existsSlugInShop($name, $shopId)) {
            $errors[] = 'Ya existe una categoría de gasto con ese nombre (o slug equivalente).';
        }
        if ($errors !== []) {
            $this->renderCrear($errors, $old);
            return;
        }
        $this->repo->create($shopId, $name, 'ACTIVE');
        Flash::set('success', 'Categoría de gasto creada correctamente.');
        Redirect::to('/admin/gastos/categorias');
    }

    public function edit(Request $request): void
    {
        $shopId = $this->requireShopId();
        $id = (int) ($request->routeParams['id'] ?? 0);
        $category = $id ? $this->repo->findById($id, $shopId) : null;
        if (!$category) {
            Flash::set('danger', 'Categoría no encontrada.');
            Redirect::to('/admin/gastos/categorias');
        }
        View::render('admin/gastos/categorias/editar', [
            'category' => $category,
            'userName' => $this->getUserName(),
            'shopName' => $this->getShopName(),
            'flash' => Flash::consume(),
            'errors' => [],
            'old' => $category,
        ]);
    }

    public function update(Request $request): void
    {
        $shopId = $this->requireShopId();
        $id = (int) ($request->routeParams['id'] ?? 0);
        $category = $id ? $this->repo->findById($id, $shopId) : null;
        if (!$category) {
            Flash::set('danger', 'Categoría no encontrada.');
            Redirect::to('/admin/gastos/categorias');
        }
        $name = trim((string) ($request->body['name'] ?? ''));
        $status = (string) ($request->body['status'] ?? 'ACTIVE');
        if (!in_array($status, ['ACTIVE', 'INACTIVE'], true)) {
            $status = 'ACTIVE';
        }
        $old = ['id' => $id, 'name' => $name, 'status' => $status];
        $errors = ExpenseCategoryValidator::validate(['name' => $name]);
        if ($this->repo->existsSlugInShop($name, $shopId, $id)) {
            $errors[] = 'Ya existe otra categoría de gasto con ese nombre (o slug equivalente).';
        }
        if ($errors !== []) {
            $this->renderEditar($category, $errors, $old);
            return;
        }
        $this->repo->update($id, $shopId, $name, $status);
        Flash::set('success', 'Categoría actualizada correctamente.');
        Redirect::to('/admin/gastos/categorias');
    }

    public function toggleStatus(Request $request): void
    {
        $shopId = $this->requireShopId();
        $id = (int) ($request->routeParams['id'] ?? 0);
        $newStatus = $this->repo->toggleStatus($id, $shopId);
        if ($newStatus === null) {
            Flash::set('danger', 'Categoría no encontrada.');
        } else {
            Flash::set('success', $newStatus === 'ACTIVE' ? 'Categoría activada.' : 'Categoría desactivada.');
        }
        Redirect::to('/admin/gastos/categorias');
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

    private function getUserName(): string
    {
        $stmt = Database::pdo()->prepare('SELECT first_name, last_name FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => Auth::userId()]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
        return $name !== '' ? $name : 'Usuario';
    }

    private function getShopName(): string
    {
        $stmt = Database::pdo()->prepare('SELECT name FROM shops WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => Auth::shopId()]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (string) ($row['name'] ?? '');
    }

    private function renderCrear(array $errors, array $old): void
    {
        View::render('admin/gastos/categorias/crear', [
            'userName' => $this->getUserName(),
            'shopName' => $this->getShopName(),
            'flash' => null,
            'errors' => $errors,
            'old' => $old,
        ]);
    }

    /**
     * @param array<string, mixed> $category
     */
    private function renderEditar(array $category, array $errors, array $old): void
    {
        View::render('admin/gastos/categorias/editar', [
            'category' => $category,
            'userName' => $this->getUserName(),
            'shopName' => $this->getShopName(),
            'flash' => null,
            'errors' => $errors,
            'old' => $old,
        ]);
    }
}
