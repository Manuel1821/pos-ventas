<?php

declare(strict_types=1);

namespace App\Core;

class View
{
    public static function render(string $template, array $data = []): void
    {
        $projectRoot = (string) ($GLOBALS['app_base_path'] ?? dirname(__DIR__, 3));
        $viewsRoot = $projectRoot . '/views';

        // Permite llamar a la vista como "auth/login" en lugar de "auth/login.php".
        $templatePath = $viewsRoot . '/' . ltrim($template, '/');
        if (!is_file($templatePath)) {
            if (is_file($templatePath . '.php')) {
                $templatePath = $templatePath . '.php';
            } else {
                http_response_code(500);
                echo 'Plantilla no encontrada: ' . htmlspecialchars($template);
                return;
            }
        }

        if (!is_file($templatePath)) {
            http_response_code(500);
            echo 'Plantilla no encontrada: ' . htmlspecialchars($template);
            return;
        }

        $baseUrl = (string) (($GLOBALS['config']['app']['url'] ?? '') ?: '');
        $baseUrl = rtrim($baseUrl, '/');

        $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
        $basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
        if ($basePath === '.' || $basePath === '' || $basePath === '\\' || $basePath === '/') {
            $basePath = '';
        }
        $data['basePath'] = $basePath;

        extract($data, EXTR_SKIP);
        require $templatePath;
    }
}

