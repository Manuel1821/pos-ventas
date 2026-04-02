<?php

declare(strict_types=1);

namespace App\Validation;

class CategoryValidator
{
    /**
     * Valida datos de categoría. Retorna array de mensajes de error (vacío si todo es válido).
     *
     * @param array{name?:string} $data
     * @return string[]
     */
    public static function validate(array $data): array
    {
        $errors = [];
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            $errors[] = 'El nombre de la categoría es obligatorio.';
        }
        if (mb_strlen($name) > 150) {
            $errors[] = 'El nombre no puede superar 150 caracteres.';
        }
        return $errors;
    }
}
