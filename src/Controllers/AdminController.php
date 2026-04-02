<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Request;
use App\Core\View;
use App\Database\Database;
use App\Repositories\DashboardRepository;
use App\Services\CashService;
use PDO;

class AdminController
{
    public function dashboard(Request $request): void
    {
        $flash = Flash::consume();

        $userId = Auth::userId();
        $shopId = Auth::shopId();
        $pdo = Database::pdo();

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

        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        $dashRepo = new DashboardRepository();
        $todaySales = ['total' => 0.0, 'count' => 0];
        $yesterdaySales = ['total' => 0.0, 'count' => 0];
        $monthSales = ['total' => 0.0, 'count' => 0];
        $recentSales = [];
        $catalog = ['products' => 0, 'customers' => 0];
        $cashOpen = false;
        $cashSummary = null;

        if ($shopId !== null) {
            $todaySales = $dashRepo->getSalesTotalsForDay($shopId, $today);
            $yesterdaySales = $dashRepo->getSalesTotalsForDay($shopId, $yesterday);
            $monthSales = $dashRepo->getSalesTotalsCurrentMonth($shopId);
            $recentSales = $dashRepo->getRecentSales($shopId, 6);
            $catalog = $dashRepo->getCatalogCounts($shopId);

            $cashService = new CashService();
            $cashOpen = $cashService->hasOpenSession($shopId);
            if ($cashOpen) {
                $cashSummary = $cashService->getCurrentSummary($shopId);
            }
        }

        $deltaVsYesterday = null;
        if ($yesterdaySales['total'] > 0.009) {
            $deltaVsYesterday = (($todaySales['total'] - $yesterdaySales['total']) / $yesterdaySales['total']) * 100;
        }

        View::render('admin/dashboard', [
            'userName' => $userName,
            'shopName' => $shop,
            'flash' => $flash,
            'todaySales' => $todaySales,
            'yesterdaySales' => $yesterdaySales,
            'monthSales' => $monthSales,
            'recentSales' => $recentSales,
            'catalog' => $catalog,
            'cashOpen' => $cashOpen,
            'cashSummary' => $cashSummary,
            'deltaVsYesterday' => $deltaVsYesterday,
            'todayDateLabel' => date('d/m/Y'),
            'isAdmin' => Auth::hasAnyRole(['admin']),
        ]);
    }
}

