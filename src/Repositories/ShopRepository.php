<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Database;
use PDOException;

class ShopRepository
{
    private static ?bool $profileColumnsExist = null;

    private static ?bool $ticketPrintColumnsExist = null;

    private static ?bool $quotationPrintColumnsExist = null;

    public function findBySlug(string $slug): ?array
    {
        $slug = strtolower(trim($slug));
        if ($slug === '') {
            return null;
        }

        // SELECT * evita error 1054 si aún no se aplicó la migración de columnas extra.
        return Database::fetch(
            'SELECT * FROM shops WHERE slug = :slug LIMIT 1',
            ['slug' => $slug]
        );
    }

    public function findById(int $shopId): ?array
    {
        if ($shopId <= 0) {
            return null;
        }

        return Database::fetch(
            'SELECT * FROM shops WHERE id = :id LIMIT 1',
            ['id' => $shopId]
        );
    }

    /**
     * Indica si existen las columnas del perfil de negocio (migración 0007).
     */
    public function hasProfileColumns(): bool
    {
        if (self::$profileColumnsExist !== null) {
            return self::$profileColumnsExist;
        }
        try {
            Database::pdo()->query('SELECT responsible_name FROM shops LIMIT 0');
            self::$profileColumnsExist = true;
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'Unknown column') || str_contains($e->getMessage(), '1054')) {
                self::$profileColumnsExist = false;
            } else {
                throw $e;
            }
        }
        return self::$profileColumnsExist;
    }

    /**
     * Columnas de migración 0009 (impresión de tickets).
     */
    public function hasTicketPrintColumns(): bool
    {
        if (self::$ticketPrintColumnsExist !== null) {
            return self::$ticketPrintColumnsExist;
        }
        try {
            Database::pdo()->query('SELECT ticket_paper_width_mm FROM shops LIMIT 0');
            self::$ticketPrintColumnsExist = true;
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'Unknown column') || str_contains($e->getMessage(), '1054')) {
                self::$ticketPrintColumnsExist = false;
            } else {
                throw $e;
            }
        }
        return self::$ticketPrintColumnsExist;
    }

    /**
     * Columnas de migración 0016 (impresión avanzada de cotizaciones).
     */
    public function hasQuotationPrintColumns(): bool
    {
        if (self::$quotationPrintColumnsExist !== null) {
            return self::$quotationPrintColumnsExist;
        }
        try {
            Database::pdo()->query('SELECT quotation_print_paper FROM shops LIMIT 0');
            self::$quotationPrintColumnsExist = true;
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'Unknown column') || str_contains($e->getMessage(), '1054')) {
                self::$quotationPrintColumnsExist = false;
            } else {
                throw $e;
            }
        }
        return self::$quotationPrintColumnsExist;
    }

    /**
     * @param array{
     *   name:string,
     *   responsible_name:?string,
     *   rfc:?string,
     *   contact_email:?string,
     *   address:?string,
     *   phone:?string,
     *   business_type:?string,
     *   ticket_paper_width_mm?:int,
     *   ticket_font_preset?:string,
     *   ticket_font_size_pt?:float,
     *   quotation_print_paper?:string,
     *   quotation_print_margin_mm?:int,
     *   quotation_print_scale_pct?:int,
     *   quotation_print_show_sku?:int,
     *   quotation_print_show_tax_col?:int,
     *   quotation_print_show_signatures?:int,
     *   quotation_print_footer_note?:?string
     * } $data
     */
    public function updateProfile(int $shopId, array $data): void
    {
        if (!$this->hasProfileColumns()) {
            Database::execute(
                'UPDATE shops SET name = :name WHERE id = :id',
                [
                    'id' => $shopId,
                    'name' => $data['name'],
                ]
            );
            return;
        }

        $set = [
            'name = :name',
            'responsible_name = :responsible_name',
            'rfc = :rfc',
            'contact_email = :contact_email',
            'address = :address',
            'phone = :phone',
            'business_type = :business_type',
        ];
        $params = [
            'id' => $shopId,
            'name' => $data['name'],
            'responsible_name' => $data['responsible_name'],
            'rfc' => $data['rfc'],
            'contact_email' => $data['contact_email'],
            'address' => $data['address'],
            'phone' => $data['phone'],
            'business_type' => $data['business_type'],
        ];

        if ($this->hasTicketPrintColumns()) {
            $set[] = 'ticket_paper_width_mm = :ticket_paper_width_mm';
            $set[] = 'ticket_font_preset = :ticket_font_preset';
            $set[] = 'ticket_font_size_pt = :ticket_font_size_pt';
            $params['ticket_paper_width_mm'] = (int) ($data['ticket_paper_width_mm'] ?? 80);
            $params['ticket_font_preset'] = (string) ($data['ticket_font_preset'] ?? 'sans_bold');
            $params['ticket_font_size_pt'] = round((float) ($data['ticket_font_size_pt'] ?? 13.0), 1);
        }

        if ($this->hasQuotationPrintColumns()) {
            $set[] = 'quotation_print_paper = :quotation_print_paper';
            $set[] = 'quotation_print_margin_mm = :quotation_print_margin_mm';
            $set[] = 'quotation_print_scale_pct = :quotation_print_scale_pct';
            $set[] = 'quotation_print_show_sku = :quotation_print_show_sku';
            $set[] = 'quotation_print_show_tax_col = :quotation_print_show_tax_col';
            $set[] = 'quotation_print_show_signatures = :quotation_print_show_signatures';
            $set[] = 'quotation_print_footer_note = :quotation_print_footer_note';
            $params['quotation_print_paper'] = (string) ($data['quotation_print_paper'] ?? 'letter');
            $params['quotation_print_margin_mm'] = (int) ($data['quotation_print_margin_mm'] ?? 10);
            $params['quotation_print_scale_pct'] = (int) ($data['quotation_print_scale_pct'] ?? 100);
            $params['quotation_print_show_sku'] = (int) (($data['quotation_print_show_sku'] ?? 1) ? 1 : 0);
            $params['quotation_print_show_tax_col'] = (int) (($data['quotation_print_show_tax_col'] ?? 1) ? 1 : 0);
            $params['quotation_print_show_signatures'] = (int) (($data['quotation_print_show_signatures'] ?? 1) ? 1 : 0);
            $note = $data['quotation_print_footer_note'] ?? null;
            $params['quotation_print_footer_note'] = ($note !== null && trim((string) $note) !== '') ? trim((string) $note) : null;
        }

        $set[] = 'updated_at = NOW()';
        $sql = 'UPDATE shops SET ' . implode(', ', $set) . ' WHERE id = :id';
        Database::execute($sql, $params);
    }
}

