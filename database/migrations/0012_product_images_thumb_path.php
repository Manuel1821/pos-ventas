<?php

declare(strict_types=1);

use PDO;

return function (PDO $pdo): void {
    $pdo->exec(
        'ALTER TABLE product_images ADD COLUMN thumb_path VARCHAR(255) NULL DEFAULT NULL AFTER path'
    );
};
