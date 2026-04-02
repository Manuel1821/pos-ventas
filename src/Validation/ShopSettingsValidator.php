<?php

declare(strict_types=1);

namespace App\Validation;

class ShopSettingsValidator
{
    /**
     * @param array<string, mixed> $data
     * @param list<string> $allowedGiros
     * @return string[]
     */
    public static function validate(
        array $data,
        array $allowedGiros,
        bool $validateTicketPrint = false,
        bool $validateQuotationPrint = false
    ): array {
        $errors = [];
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            $errors[] = 'El nombre de la tienda es obligatorio.';
        }
        if (mb_strlen($name) > 150) {
            $errors[] = 'El nombre de la tienda no puede superar 150 caracteres.';
        }

        $responsible = trim((string) ($data['responsible_name'] ?? ''));
        if (mb_strlen($responsible) > 200) {
            $errors[] = 'El nombre del responsable no puede superar 200 caracteres.';
        }

        $rfc = strtoupper(trim((string) ($data['rfc'] ?? '')));
        if (mb_strlen($rfc) > 20) {
            $errors[] = 'El RFC no puede superar 20 caracteres.';
        }
        if ($rfc !== '' && !preg_match('/^[A-ZÑ&]{3,4}[0-9]{6}[A-Z0-9]{3}$/u', $rfc)) {
            $errors[] = 'El formato del RFC no es válido.';
        }

        $email = trim((string) ($data['contact_email'] ?? ''));
        if (mb_strlen($email) > 190) {
            $errors[] = 'El correo no puede superar 190 caracteres.';
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'El correo de la tienda no es válido.';
        }

        $address = trim((string) ($data['address'] ?? ''));
        if (mb_strlen($address) > 500) {
            $errors[] = 'La dirección no puede superar 500 caracteres.';
        }

        $phone = trim((string) ($data['phone'] ?? ''));
        if (mb_strlen($phone) > 30) {
            $errors[] = 'El teléfono no puede superar 30 caracteres.';
        }

        $giro = trim((string) ($data['business_type'] ?? ''));
        if ($giro !== '' && !in_array($giro, $allowedGiros, true)) {
            $errors[] = 'Selecciona un giro válido de la lista.';
        }

        if ($validateTicketPrint) {
            $paper = (int) ($data['ticket_paper_width_mm'] ?? 80);
            if (!in_array($paper, [58, 72, 80], true)) {
                $errors[] = 'El ancho de papel del ticket debe ser 58, 72 u 80 mm.';
            }

            $preset = trim((string) ($data['ticket_font_preset'] ?? 'sans_bold'));
            $allowedPresets = ['system', 'sans', 'sans_bold', 'mono', 'mono_bold', 'serif'];
            if (!in_array($preset, $allowedPresets, true)) {
                $errors[] = 'Selecciona un tipo de fuente de ticket válido.';
            }

            $tSize = (float) ($data['ticket_font_size_pt'] ?? 13.0);
            if (!is_finite($tSize) || $tSize < 8.0 || $tSize > 24.0) {
                $errors[] = 'El tamaño de fuente del ticket debe estar entre 8 y 24 pt.';
            }
        }

        if ($validateQuotationPrint) {
            $qp = strtolower(trim((string) ($data['quotation_print_paper'] ?? 'letter')));
            if (!in_array($qp, ['letter', 'a4'], true)) {
                $errors[] = 'El tamaño de papel de cotización debe ser carta (letter) o A4.';
            }

            $qm = (int) ($data['quotation_print_margin_mm'] ?? 10);
            if (!in_array($qm, [6, 8, 10, 12, 15], true)) {
                $errors[] = 'El margen de impresión de cotización debe ser 6, 8, 10, 12 o 15 mm.';
            }

            $qs = (int) ($data['quotation_print_scale_pct'] ?? 100);
            if ($qs < 85 || $qs > 115) {
                $errors[] = 'La escala de texto de cotización debe estar entre 85% y 115%.';
            }

            $footer = trim((string) ($data['quotation_print_footer_note'] ?? ''));
            if (mb_strlen($footer) > 600) {
                $errors[] = 'El pie de cotización no puede superar 600 caracteres.';
            }
        }

        return $errors;
    }
}
