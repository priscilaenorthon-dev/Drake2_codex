<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;
use App\Services\AuditService;

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
            $user = Auth::user();
            if ($user) {
                (new AuditService())->logEvent(
                    (int) $user['tenant_id'],
                    (int) $user['id'],
                    'auth',
                    'login',
                    null,
                    ['email' => $email, 'result' => 'success']
                );
            }

            header('Location: /dashboard');
            return;
        }

        $stmt = Database::connection()->prepare('SELECT id, tenant_id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $candidate = $stmt->fetch();
        if ($candidate) {
            (new AuditService())->logEvent(
                (int) $candidate['tenant_id'],
                (int) $candidate['id'],
                'auth',
                'login_failed',
                null,
                ['email' => $email, 'result' => 'invalid_credentials']
            );
        }

        View::render('auth/login', ['error' => 'Credenciais inválidas']);
    }

    public function logout(): void
    {
        $user = Auth::user();
        if ($user) {
            (new AuditService())->logEvent(
                (int) $user['tenant_id'],
                (int) $user['id'],
                'auth',
                'logout',
                ['session' => 'active'],
                ['session' => 'terminated']
            );
        }

        Auth::logout();
        header('Location: /login');
    }
}
