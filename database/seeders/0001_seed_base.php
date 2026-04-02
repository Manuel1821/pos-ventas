<?php

declare(strict_types=1);

use PDO;

return function (PDO $pdo): void {
    $pdo->beginTransaction();

    $adminEmail = (string) ($_ENV['ADMIN_EMAIL'] ?? 'admin@tenda.com');
    $adminPassword = (string) ($_ENV['ADMIN_PASSWORD'] ?? 'admin123');
    $adminFirstName = (string) ($_ENV['ADMIN_FIRST_NAME'] ?? 'Admin');
    $adminLastName = (string) ($_ENV['ADMIN_LAST_NAME'] ?? 'Principal');

    // 1) Shop
    $shopSlug = (string) ($_ENV['SHOP_SLUG'] ?? 'tienda-principal');
    $shopName = (string) ($_ENV['SHOP_NAME'] ?? 'Tienda Principal');

    $stmt = $pdo->prepare(
        'INSERT INTO shops (name, slug, created_at)
         VALUES (:name, :slug, NOW())
         ON DUPLICATE KEY UPDATE name = VALUES(name)'
    );
    $stmt->execute(['name' => $shopName, 'slug' => $shopSlug]);

    $shopId = (int) ($pdo->query("SELECT id FROM shops WHERE slug = " . $pdo->quote($shopSlug))->fetchColumn());

    // 2) Roles
    $roles = [
        ['name' => 'admin', 'display' => 'Administrador'],
        ['name' => 'cajero', 'display' => 'Cajero'],
        ['name' => 'vendedor', 'display' => 'Vendedor'],
    ];

    $roleIds = [];
    foreach ($roles as $role) {
        $stmt = $pdo->prepare(
            'INSERT INTO roles (name, display_name, status, created_at)
             VALUES (:name, :display, "ACTIVE", NOW())
             ON DUPLICATE KEY UPDATE display_name = VALUES(display_name)'
        );
        $stmt->execute(['name' => $role['name'], 'display' => $role['display']]);

        $roleIds[$role['name']] = (int) (
            $pdo->query("SELECT id FROM roles WHERE name = " . $pdo->quote($role['name']))->fetchColumn()
        );
    }

    // 3) Admin user
    $passwordHash = password_hash($adminPassword, PASSWORD_BCRYPT);

    $stmt = $pdo->prepare(
        'INSERT INTO users (shop_id, email, password_hash, first_name, last_name, status, created_at)
         VALUES (:shop_id, :email, :password_hash, :first_name, :last_name, "ACTIVE", NOW())
         ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash),
                                 first_name = VALUES(first_name),
                                 last_name = VALUES(last_name)'
    );
    $stmt->execute([
        'shop_id' => $shopId,
        'email' => mb_strtolower(trim($adminEmail)),
        'password_hash' => $passwordHash,
        'first_name' => $adminFirstName,
        'last_name' => $adminLastName,
    ]);

    $userId = (int) (
        $pdo->query(
            "SELECT id FROM users WHERE shop_id = {$shopId} AND email = " . $pdo->quote(mb_strtolower(trim($adminEmail)))
        )->fetchColumn()
    );

    // 4) user_roles (admin)
    $stmt = $pdo->prepare(
        'INSERT INTO user_roles (user_id, role_id, created_at)
         VALUES (:user_id, :role_id, NOW())
         ON DUPLICATE KEY UPDATE user_id = VALUES(user_id)'
    );
    $stmt->execute(['user_id' => $userId, 'role_id' => $roleIds['admin']]);

    // 5) categories (products)
    $categories = [
        ['name' => 'General', 'slug' => 'general'],
    ];
    $stmtCat = $pdo->prepare(
        'INSERT INTO categories (shop_id, name, slug, status, created_at)
         VALUES (:shop_id, :name, :slug, "ACTIVE", NOW())
         ON DUPLICATE KEY UPDATE name = VALUES(name)'
    );
    foreach ($categories as $c) {
        $stmtCat->execute(['shop_id' => $shopId, 'name' => $c['name'], 'slug' => $c['slug']]);
    }

    // 6) expense_categories
    $expenseCats = [
        ['name' => 'Gastos generales', 'slug' => 'gastos-generales'],
    ];
    $stmtEc = $pdo->prepare(
        'INSERT INTO expense_categories (shop_id, name, slug, status, created_at)
         VALUES (:shop_id, :name, :slug, "ACTIVE", NOW())
         ON DUPLICATE KEY UPDATE name = VALUES(name)'
    );
    foreach ($expenseCats as $c) {
        $stmtEc->execute(['shop_id' => $shopId, 'name' => $c['name'], 'slug' => $c['slug']]);
    }

    // 7) default customer (public)
    $update = $pdo->prepare(
        'UPDATE customers
         SET name = :name, phone = NULL, email = NULL, status = "ACTIVE"
         WHERE shop_id = :shop_id AND is_public = 1'
    );
    $update->execute(['shop_id' => $shopId, 'name' => 'Público en general']);

    if ($update->rowCount() === 0) {
        $insert = $pdo->prepare(
            'INSERT INTO customers (shop_id, name, phone, email, is_public, status, created_at)
             VALUES (:shop_id, :name, NULL, NULL, 1, "ACTIVE", NOW())'
        );
        $insert->execute(['shop_id' => $shopId, 'name' => 'Público en general']);
    }

    $pdo->commit();
};

