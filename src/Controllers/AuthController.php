<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Redirect;
use App\Core\Request;
use App\Core\View;
use App\Services\AuthService;

class AuthController
{
    public function showLogin(Request $request): void
    {
        $flash = Flash::consume();

        View::render('auth/login', [
            'flash' => $flash,
        ]);
    }

    public function login(Request $request): void
    {
        $email = (string) ($request->body['email'] ?? '');
        $password = (string) ($request->body['password'] ?? '');

        $service = new AuthService();
        $ok = $service->login($email, $password);

        if (!$ok) {
            Flash::set('danger', 'Correo o contraseña inválidos.');
            Redirect::to('/login');
        }

        Redirect::to('/admin/dashboard');
    }

    public function logout(Request $request): void
    {
        Auth::logout();
        if (php_sapi_name() !== 'cli') {
            session_destroy();
        }
        Redirect::to('/login');
    }
}

