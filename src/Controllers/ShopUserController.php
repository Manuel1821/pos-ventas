<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Redirect;
use App\Core\Request;
use App\Core\View;
use App\Database\Database;
use App\Repositories\UserManagementRepository;
use App\Validation\ShopUserValidator;

class ShopUserController
{
    private UserManagementRepository $userRepo;

    public function __construct()
    {
        $this->userRepo = new UserManagementRepository();
    }

    public function index(Request $request): void
    {
        $shopId = Auth::shopId();
        if ($shopId === null) {
            Flash::set('danger', 'Sesión inválida.');
            Redirect::to('/login');
        }

        $flash = Flash::consume();
        $users = $this->userRepo->listByShop($shopId);

        View::render('admin/configuracion/usuarios_indice', [
            'pageTitle' => 'Usuarios de la tienda',
            'users' => $users,
            'currentUserId' => Auth::userId(),
            'flash' => $flash,
            'userName' => $this->getUserName(),
            'shopName' => $this->getShopName(),
        ]);
    }

    public function create(Request $request): void
    {
        $shopId = Auth::shopId();
        if ($shopId === null) {
            Flash::set('danger', 'Sesión inválida.');
            Redirect::to('/login');
        }

        View::render('admin/configuracion/usuarios_form', [
            'pageTitle' => 'Nuevo usuario',
            'mode' => 'create',
            'user' => null,
            'errors' => [],
            'old' => [
                'email' => '',
                'first_name' => '',
                'last_name' => '',
                'role' => 'cajero',
                'password' => '',
            ],
            'userName' => $this->getUserName(),
            'shopName' => $this->getShopName(),
            'flash' => Flash::consume(),
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
            'email' => trim((string) ($body['email'] ?? '')),
            'first_name' => trim((string) ($body['first_name'] ?? '')),
            'last_name' => trim((string) ($body['last_name'] ?? '')),
            'role' => trim((string) ($body['role'] ?? 'cajero')),
            'password' => (string) ($body['password'] ?? ''),
        ];

        $errors = ShopUserValidator::validateCreate($old);
        if ($this->userRepo->emailExistsForShop($old['email'], $shopId, null)) {
            $errors[] = 'Ya existe un usuario con ese correo en esta tienda.';
        }

        if ($errors !== []) {
            View::render('admin/configuracion/usuarios_form', [
                'pageTitle' => 'Nuevo usuario',
                'mode' => 'create',
                'user' => null,
                'errors' => $errors,
                'old' => $old,
                'userName' => $this->getUserName(),
                'shopName' => $this->getShopName(),
                'flash' => null,
            ]);
            return;
        }

        $hash = password_hash($old['password'], PASSWORD_BCRYPT);
        try {
            $newId = $this->userRepo->create(
                $shopId,
                $old['email'],
                $hash,
                $old['first_name'],
                $old['last_name']
            );
            $this->userRepo->setUserRole($newId, $old['role']);
        } catch (\Throwable) {
            Flash::set('danger', 'No se pudo crear el usuario. Inténtalo de nuevo.');
            Redirect::to('/admin/configuracion/usuarios/crear');
        }

        Flash::set('success', 'Usuario creado correctamente.');
        Redirect::to('/admin/configuracion/usuarios');
    }

    public function edit(Request $request): void
    {
        $shopId = Auth::shopId();
        if ($shopId === null) {
            Flash::set('danger', 'Sesión inválida.');
            Redirect::to('/login');
        }

        $id = (int) ($request->routeParams['id'] ?? 0);
        if ($id <= 0) {
            Flash::set('danger', 'Usuario inválido.');
            Redirect::to('/admin/configuracion/usuarios');
        }

        $user = $this->userRepo->findByIdForShop($id, $shopId);
        if (!$user) {
            Flash::set('danger', 'Usuario no encontrado.');
            Redirect::to('/admin/configuracion/usuarios');
        }

        $role = $this->userRepo->getPrimaryRoleName($id) ?? 'cajero';

        View::render('admin/configuracion/usuarios_form', [
            'pageTitle' => 'Editar usuario',
            'mode' => 'edit',
            'user' => $user,
            'errors' => [],
            'old' => [
                'email' => (string) $user['email'],
                'first_name' => (string) $user['first_name'],
                'last_name' => (string) $user['last_name'],
                'role' => in_array($role, ['admin', 'cajero'], true) ? $role : 'cajero',
                'password' => '',
            ],
            'userName' => $this->getUserName(),
            'shopName' => $this->getShopName(),
            'flash' => Flash::consume(),
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
        if ($id <= 0) {
            Flash::set('danger', 'Usuario inválido.');
            Redirect::to('/admin/configuracion/usuarios');
        }

        $existing = $this->userRepo->findByIdForShop($id, $shopId);
        if (!$existing) {
            Flash::set('danger', 'Usuario no encontrado.');
            Redirect::to('/admin/configuracion/usuarios');
        }

        $body = $request->body;
        $old = [
            'email' => trim((string) ($body['email'] ?? '')),
            'first_name' => trim((string) ($body['first_name'] ?? '')),
            'last_name' => trim((string) ($body['last_name'] ?? '')),
            'role' => trim((string) ($body['role'] ?? 'cajero')),
            'password' => (string) ($body['password'] ?? ''),
        ];

        $errors = ShopUserValidator::validateUpdate($old);
        if ($this->userRepo->emailExistsForShop($old['email'], $shopId, $id)) {
            $errors[] = 'Ya existe otro usuario con ese correo en esta tienda.';
        }

        $prevRole = $this->userRepo->getPrimaryRoleName($id) ?? '';
        if ($prevRole === 'admin' && $old['role'] === 'cajero') {
            if ($this->userRepo->countActiveAdminsInShop($shopId, $id) < 1) {
                $errors[] = 'Debe haber al menos otro administrador activo antes de cambiar este usuario a cajero.';
            }
        }

        if ($errors !== []) {
            View::render('admin/configuracion/usuarios_form', [
                'pageTitle' => 'Editar usuario',
                'mode' => 'edit',
                'user' => $existing,
                'errors' => $errors,
                'old' => $old,
                'userName' => $this->getUserName(),
                'shopName' => $this->getShopName(),
                'flash' => null,
            ]);
            return;
        }

        $pwd = $old['password'] !== '' ? password_hash($old['password'], PASSWORD_BCRYPT) : null;
        $status = (string) ($existing['status'] ?? 'ACTIVE');

        $ok = $this->userRepo->update(
            $id,
            $shopId,
            $old['email'],
            $old['first_name'],
            $old['last_name'],
            $pwd,
            $status
        );
        if (!$ok) {
            Flash::set('danger', 'No se pudo actualizar el usuario.');
            Redirect::to('/admin/configuracion/usuarios/editar/' . $id);
        }

        try {
            $this->userRepo->setUserRole($id, $old['role']);
        } catch (\Throwable) {
            Flash::set('danger', 'Usuario actualizado pero no se pudo asignar el rol.');
            Redirect::to('/admin/configuracion/usuarios');
        }

        Flash::set('success', 'Usuario actualizado correctamente.');
        Redirect::to('/admin/configuracion/usuarios');
    }

    public function toggleStatus(Request $request): void
    {
        $shopId = Auth::shopId();
        $actorId = Auth::userId();
        if ($shopId === null || $actorId === null) {
            Flash::set('danger', 'Sesión inválida.');
            Redirect::to('/login');
        }

        $id = (int) ($request->routeParams['id'] ?? 0);
        if ($id <= 0) {
            Flash::set('danger', 'Usuario inválido.');
            Redirect::to('/admin/configuracion/usuarios');
        }

        if ($id === $actorId) {
            Flash::set('danger', 'No puedes desactivar tu propia cuenta desde aquí.');
            Redirect::to('/admin/configuracion/usuarios');
        }

        $user = $this->userRepo->findByIdForShop($id, $shopId);
        if (!$user) {
            Flash::set('danger', 'Usuario no encontrado.');
            Redirect::to('/admin/configuracion/usuarios');
        }

        $current = (string) ($user['status'] ?? 'ACTIVE');
        $newStatus = $current === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';

        if ($newStatus === 'INACTIVE' && ($this->userRepo->getPrimaryRoleName($id) === 'admin')) {
            if ($this->userRepo->countActiveAdminsInShop($shopId, $id) < 1) {
                Flash::set('danger', 'No puedes desactivar al único administrador de la tienda.');
                Redirect::to('/admin/configuracion/usuarios');
            }
        }

        if (!$this->userRepo->setStatus($id, $shopId, $newStatus)) {
            Flash::set('danger', 'No se pudo cambiar el estado.');
        } else {
            Flash::set('success', $newStatus === 'ACTIVE' ? 'Usuario activado.' : 'Usuario desactivado.');
        }
        Redirect::to('/admin/configuracion/usuarios');
    }

    private function getUserName(): string
    {
        $stmt = Database::pdo()->prepare('SELECT first_name, last_name FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => Auth::userId()]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $name = trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? ''));
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
