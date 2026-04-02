<?php

declare(strict_types=1);

namespace App\Validation;

class ProductValidator
{
    /**
     * Valida datos de producto. Retorna array de mensajes de error (vacío si todo es válido).
     * No valida unicidad de SKU/barcode (eso lo hace el controlador con el repositorio).
     *
     * @param array{name?:string, sku?:string, barcode?:string, price?:mixed, cost?:mixed, tax_percent?:mixed, stock?:mixed} $data
     * @return string[]
     */
    public static function validate(array $data): array
    {
        $errors = [];
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            $errors[] = 'El nombre del producto es obligatorio.';
        }
        if (mb_strlen($name) > 200) {
            $errors[] = 'El nombre no puede superar 200 caracteres.';
        }
        $sku = trim((string) ($data['sku'] ?? ''));
        if (mb_strlen($sku) > 120) {
            $errors[] = 'El SKU no puede superar 120 caracteres.';
        }
        $barcode = trim((string) ($data['barcode'] ?? ''));
        if (mb_strlen($barcode) > 120) {
            $errors[] = 'El código de barras no puede superar 120 caracteres.';
        }
        $price = self::parseDecimal($data['price'] ?? 0);
        if ($price === null || $price < 0) {
            $errors[] = 'El precio debe ser un número mayor o igual a 0.';
        }
        $cost = self::parseDecimal($data['cost'] ?? 0);
        if ($cost === null || $cost < 0) {
            $errors[] = 'El costo debe ser un número mayor o igual a 0.';
        }
        $taxPercent = self::parseDecimal($data['tax_percent'] ?? 0);
        if ($taxPercent === null || $taxPercent < 0 || $taxPercent > 100) {
            $errors[] = 'El porcentaje de impuesto debe estar entre 0 y 100.';
        }
        $stock = self::parseDecimal($data['stock'] ?? 0);
        if ($stock === null || $stock < 0) {
            $errors[] = 'El stock debe ser un número mayor o igual a 0.';
        }
        return $errors;
    }

    private static function parseDecimal(mixed $value): ?float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }
        if (is_string($value)) {
            $value = str_replace(',', '.', $value);
            return is_numeric($value) ? (float) $value : null;
        }
        return null;
    }
}
