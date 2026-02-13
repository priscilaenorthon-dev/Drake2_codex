<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;

final class AuthController
{
    public function loginForm(): void
    {
        View::render('auth/login');
    }

    public function login(): void
    {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (Auth::attempt($email, $password)) {
            header('Location: /dashboard');
            return;
        }

        View::render('auth/login', ['error' => 'Credenciais inválidas']);
    }

    public function logout(): void
    {
        Auth::logout();
        header('Location: /login');
    }
}
