<?php

declare(strict_types=1);

namespace App\Validation;

class InventoryBatchValidator
{
    /**
     * @param array<string, mixed> $data
     * @return string[]
     */
    public static function validate(array $data): array
    {
        $errors = [];
        $productId = isset($data['product_id']) ? (int) $data['product_id'] : 0;
        if ($productId <= 0) {
            $errors[] = 'Seleccione un producto.';
        }
        $lot = trim((string) ($data['lot_code'] ?? ''));
        if ($lot === '') {
            $errors[] = 'El código de lote es obligatorio.';
        } elseif (mb_strlen($lot) > 64) {
            $errors[] = 'El código de lote no puede superar 64 caracteres.';
        }
        $qtyRaw = $data['quantity'] ?? '';
        if ($qtyRaw === '' || !is_numeric((string) $qtyRaw)) {
            $errors[] = 'Indique una cantidad numérica válida.';
        } else {
            $qty = (float) $qtyRaw;
            if ($qty < 0) {
                $errors[] = 'La cantidad no puede ser negativa.';
            }
        }
        $expRaw = trim((string) ($data['expiry_date'] ?? ''));
        if ($expRaw !== '') {
            $d = \DateTime::createFromFormat('Y-m-d', $expRaw);
            if (!$d || $d->format('Y-m-d') !== $expRaw) {
                $errors[] = 'La fecha de caducidad no es válida.';
            }
        }
        $notes = trim((string) ($data['notes'] ?? ''));
        if (mb_strlen($notes) > 2000) {
            $errors[] = 'Las notas no pueden superar 2000 caracteres.';
        }

        return $errors;
    }

    public static function normalizedExpiry(?string $expRaw): ?string
    {
        $expRaw = trim((string) ($expRaw ?? ''));

        return $expRaw === '' ? null : $expRaw;
    }
}
