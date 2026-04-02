<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\View;
use App\Database\Database;
use PDO;

class HealthController
{
    public function index(Request $request): void
    {
        $pdo = Database::pdo();
        $stmt = $pdo->query('SELECT NOW() AS now');
        $now = (string) (($stmt->fetch(PDO::FETCH_ASSOC)['now'] ?? '') ?: '');

        $userId = Auth::userId();
        $stmt = $pdo->prepare(
            'SELECT u.first_name, u.last_name, s.name AS shop_name
             FROM users u
             INNER JOIN shops s ON s.id = u.shop_id
             WHERE u.id = :user_id
             LIMIT 1'
        );
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $first = (string) ($row['first_name'] ?? '');
        $last = (string) ($row['last_name'] ?? '');
        $shop = (string) ($row['shop_name'] ?? '');
        $userName = trim($first . ' ' . $last);
        if ($userName === '') {
            $userName = 'Usuario';
        }

        View::render('admin/health', [
            'userName' => $userName,
            'shopName' => $shop,
            'now' => $now,
        ]);
    }
}

