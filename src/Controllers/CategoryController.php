<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Redirect;
use App\Core\Request;
use App\Core\View;
use App\Repositories\CategoryRepository;
use App\Validation\CategoryValidator;

class CategoryController
{
    private CategoryRepository $categoryRepo;

    public function __construct()
    {
        $this->categoryRepo = new CategoryRepository();
    }

    public function index(Request $request): void
    {
        $shopId = Auth::shopId();
        if ($shopId === null) {
            Flash::set('danger', 'Sesión inválida.');
            Redirect::to('/login');
        }
        $flash = Flash::consume();
        $categories = $this->categoryRepo->listByShop($shopId);
        $userName = $this->getUserName();
        $shopName = $this->getShopName();

        View::render('admin/categorias/indice', [
            'categories' => $categories,
            'userName' => $userName,
            'shopName' => $shopName,
            'flash' => $flash,
        ]);
    }

    public function create(Request $request): void
    {
        $shopId = Auth::shopId();
        if ($shopId === null) {
            Flash::set('danger', 'Sesión inválida.');
            Redirect::to('/login');
        }
        $flash = Flash::consume();
        $userName = $this->getUserName();
        $shopName = $this->getShopName();
        View::render('admin/categorias/crear', [
            'userName' => $userName,
            'shopName' => $shopName,
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
        $name = trim((string) ($request->body['name'] ?? ''));
        $old = ['name' => $name];
        $errors = CategoryValidator::validate(['name' => $name]);
        if ($this->categoryRepo->existsSlugInShop($name, $shopId)) {
            $errors[] = 'Ya existe una categoría con ese nombre (o slug equivalente).';
        }
        if ($errors !== []) {
            $this->renderCrearConErrores($errors, $old);
            return;
        }
        $this->categoryRepo->create($shopId, $name, 'ACTIVE');
        Flash::set('success', 'Categoría creada correctamente.');
        Redirect::to('/admin/categorias');
    }

    public function edit(Request $request): void
    {
        $shopId = Auth::shopId();
        if ($shopId === null) {
            Flash::set('danger', 'Sesión inválida.');
            Redirect::to('/login');
        }
        $id = (int) ($request->routeParams['id'] ?? 0);
        $category = $id ? $this->categoryRepo->findById($id, $shopId) : null;
        if (!$category) {
            Flash::set('danger', 'Categoría no encontrada.');
            Redirect::to('/admin/categorias');
        }
        $flash = Flash::consume();
        View::render('admin/categorias/editar', [
            'category' => $category,
            'userName' => $this->getUserName(),
            'shopName' => $this->getShopName(),
            'flash' => $flash,
            'errors' => [],
            'old' => $category,
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
        $category = $id ? $this->categoryRepo->findById($id, $shopId) : null;
        if (!$category) {
            Flash::set('danger', 'Categoría no encontrada.');
            Redirect::to('/admin/categorias');
        }
        $name = trim((string) ($request->body['name'] ?? ''));
        $status = (string) ($request->body['status'] ?? 'ACTIVE');
        if (!in_array($status, ['ACTIVE', 'INACTIVE'], true)) {
            $status = 'ACTIVE';
        }
        $old = ['id' => $id, 'name' => $name, 'status' => $status];
        $errors = CategoryValidator::validate(['name' => $name]);
        if ($this->categoryRepo->existsSlugInShop($name, $shopId, $id)) {
            $errors[] = 'Ya existe otra categoría con ese nombre (o slug equivalente).';
        }
        if ($errors !== []) {
            $this->renderEditarConErrores($category, $errors, $old);
            return;
        }
        $this->categoryRepo->update($id, $shopId, $name, $status);
        Flash::set('success', 'Categoría actualizada correctamente.');
        Redirect::to('/admin/categorias');
    }

    public function toggleStatus(Request $request): void
    {
        $shopId = Auth::shopId();
        if ($shopId === null) {
            Flash::set('danger', 'Sesión inválida.');
            Redirect::to('/login');
        }
        $id = (int) ($request->routeParams['id'] ?? 0);
        $newStatus = $this->categoryRepo->toggleStatus($id, $shopId);
        if ($newStatus === null) {
            Flash::set('danger', 'Categoría no encontrada.');
        } else {
            Flash::set('success', $newStatus === 'ACTIVE' ? 'Categoría activada.' : 'Categoría desactivada.');
        }
        Redirect::to('/admin/categorias');
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

    private function renderCrearConErrores(array $errors, array $old): void
    {
        View::render('admin/categorias/crear', [
            'userName' => $this->getUserName(),
            'shopName' => $this->getShopName(),
            'flash' => null,
            'errors' => $errors,
            'old' => $old,
        ]);
    }

    private function renderEditarConErrores(array $category, array $errors, array $old): void
    {
        View::render('admin/categorias/editar', [
            'category' => $category,
            'userName' => $this->getUserName(),
            'shopName' => $this->getShopName(),
            'flash' => null,
            'errors' => $errors,
            'old' => $old,
        ]);
    }
}
