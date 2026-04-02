<?php

declare(strict_types=1);

namespace App\Validation;

class CustomerValidator
{
    /**
     * Valida datos de cliente. Retorna array de mensajes de error (vacío si todo es válido).
     *
     * @param array{name?:string, phone?:string, email?:string, address?:string, rfc?:string, notes?:string} $data
     * @return string[]
     */
    public static function validate(array $data): array
    {
        $errors = [];
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            $errors[] = 'El nombre del cliente es obligatorio.';
        }
        if (mb_strlen($name) > 200) {
            $errors[] = 'El nombre no puede superar 200 caracteres.';
        }
        $phone = trim((string) ($data['phone'] ?? ''));
        if (mb_strlen($phone) > 30) {
            $errors[] = 'El teléfono no puede superar 30 caracteres.';
        }
        $email = trim((string) ($data['email'] ?? ''));
        if (mb_strlen($email) > 190) {
            $errors[] = 'El correo no puede superar 190 caracteres.';
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'El correo electrónico no es válido.';
        }
        $address = trim((string) ($data['address'] ?? ''));
        if (mb_strlen($address) > 500) {
            $errors[] = 'La dirección no puede superar 500 caracteres.';
        }
        $rfc = trim((string) ($data['rfc'] ?? ''));
        if (mb_strlen($rfc) > 20) {
            $errors[] = 'El RFC no puede superar 20 caracteres.';
        }
        $notes = trim((string) ($data['notes'] ?? ''));
        if (mb_strlen($notes) > 1000) {
            $errors[] = 'Las observaciones no pueden superar 1000 caracteres.';
        }
        return $errors;
    }
}
