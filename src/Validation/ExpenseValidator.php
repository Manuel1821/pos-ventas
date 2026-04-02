<?php

declare(strict_types=1);

namespace App\Validation;

class ExpenseValidator
{
    public const PAYMENT_METHODS = [
        'EFECTIVO',
        'TARJETA_DEBITO',
        'TARJETA_CREDITO',
        'TRANSFERENCIA',
        'OTRO',
    ];

    /**
     * @param array<string, mixed> $data
     * @return string[]
     */
    public static function validate(array $data): array
    {
        $errors = [];
        $catId = (int) ($data['expense_category_id'] ?? 0);
        if ($catId <= 0) {
            $errors[] = 'Debes seleccionar una categoría de gasto.';
        }

        $concept = trim((string) ($data['concept'] ?? ''));
        if ($concept === '') {
            $errors[] = 'La descripción del gasto es obligatoria.';
        }
        if (mb_strlen($concept) > 180) {
            $errors[] = 'La descripción no puede superar 180 caracteres.';
        }

        $amount = self::parsePositiveAmount($data['amount'] ?? null);
        if ($amount === null) {
            $errors[] = 'El monto debe ser un número mayor que cero.';
        }

        $occurredRaw = trim((string) ($data['occurred_at'] ?? ''));
        if ($occurredRaw === '') {
            $errors[] = 'La fecha y hora del gasto son obligatorias.';
        } elseif (self::normalizeOccurredAt($occurredRaw) === null) {
            $errors[] = 'La fecha y hora del gasto no son válidas.';
        }

        $pm = strtoupper(trim((string) ($data['payment_method'] ?? '')));
        if (!in_array($pm, self::PAYMENT_METHODS, true)) {
            $errors[] = 'Selecciona un método de pago válido.';
        }

        $supplier = trim((string) ($data['supplier_name'] ?? ''));
        if (mb_strlen($supplier) > 160) {
            $errors[] = 'El proveedor no puede superar 160 caracteres.';
        }

        $reference = trim((string) ($data['reference'] ?? ''));
        if (mb_strlen($reference) > 120) {
            $errors[] = 'La referencia no puede superar 120 caracteres.';
        }

        $notes = trim((string) ($data['notes'] ?? ''));
        if (mb_strlen($notes) > 65535) {
            $errors[] = 'Las observaciones son demasiado extensas.';
        }

        return $errors;
    }

    public static function parsePositiveAmount(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            $n = (float) $value;
            return $n > 0 ? round($n, 2) : null;
        }
        if (!is_string($value)) {
            return null;
        }
        $s = trim(str_replace(' ', '', $value));
        if ($s === '' || !is_numeric($s)) {
            return null;
        }
        $n = (float) $s;
        return $n > 0 ? round($n, 2) : null;
    }

    public static function normalizeOccurredAt(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $raw)) {
            return str_replace('T', ' ', $raw) . ':00';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $raw)) {
            return $raw . ':00';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $raw)) {
            return $raw;
        }
        $dt = \DateTime::createFromFormat('Y-m-d', $raw);
        if ($dt instanceof \DateTime && $dt->format('Y-m-d') === $raw) {
            return $raw . ' 12:00:00';
        }
        return null;
    }

    public static function paymentMethodLabel(string $code): string
    {
        return match ($code) {
            'EFECTIVO' => 'Efectivo',
            'TARJETA_DEBITO' => 'Tarjeta débito',
            'TARJETA_CREDITO' => 'Tarjeta crédito',
            'TRANSFERENCIA' => 'Transferencia',
            'OTRO' => 'Otro',
            default => $code,
        };
    }
}
