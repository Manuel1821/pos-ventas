<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Redirect;
use App\Core\Request;
use App\Core\View;
use App\Database\Database;
use App\Repositories\ShopRepository;
use App\Validation\ShopSettingsValidator;
use PDO;

class ShopSettingsController
{
    private ShopRepository $shopRepo;

    public function __construct()
    {
        $this->shopRepo = new ShopRepository();
    }

    public function index(Request $request): void
    {
        $shopId = Auth::shopId();
        if ($shopId === null) {
            Flash::set('danger', 'Sesión inválida.');
            Redirect::to('/login');
        }
        $flash = Flash::consume();
        $giros = $this->loadGiros();
        $shop = $this->shopRepo->findById($shopId);
        if ($shop === null) {
            Flash::set('danger', 'No se encontró la tienda.');
            Redirect::to('/admin/dashboard');
        }

        $profileReady = $this->shopRepo->hasProfileColumns();

        View::render('admin/configuracion/tienda', [
            'userName' => $this->getUserName(),
            'shopName' => (string) ($shop['name'] ?? ''),
            'shopSlug' => (string) ($shop['slug'] ?? ''),
            'flash' => $flash,
            'errors' => [],
            'giros' => $giros,
            'old' => $this->shopRowToOld($shop),
            'shopProfileMigrationNeeded' => !$profileReady,
            'shopTicketMigrationNeeded' => !$this->shopRepo->hasTicketPrintColumns(),
            'shopQuotationMigrationNeeded' => !$this->shopRepo->hasQuotationPrintColumns(),
        ]);
    }

    public function save(Request $request): void
    {
        $shopId = Auth::shopId();
        if ($shopId === null) {
            Flash::set('danger', 'Sesión inválida.');
            Redirect::to('/login');
        }
        $giros = $this->loadGiros();
        $body = $request->body;
        $rfcRaw = strtoupper(trim((string) ($body['rfc'] ?? '')));
        if ($rfcRaw !== '') {
            $rfcRaw = (string) preg_replace('/[^A-ZÑ&0-9]/u', '', $rfcRaw);
        }
        $old = [
            'name' => trim((string) ($body['name'] ?? '')),
            'responsible_name' => trim((string) ($body['responsible_name'] ?? '')),
            'rfc' => $rfcRaw,
            'contact_email' => trim((string) ($body['contact_email'] ?? '')),
            'address' => trim((string) ($body['address'] ?? '')),
            'phone' => trim((string) ($body['phone'] ?? '')),
            'business_type' => trim((string) ($body['business_type'] ?? '')),
            'ticket_paper_width_mm' => (int) ($body['ticket_paper_width_mm'] ?? 80),
            'ticket_font_preset' => trim((string) ($body['ticket_font_preset'] ?? 'sans_bold')),
            'ticket_font_size_pt' => (float) ($body['ticket_font_size_pt'] ?? 13.0),
            'quotation_print_paper' => strtolower(trim((string) ($body['quotation_print_paper'] ?? 'letter'))),
            'quotation_print_margin_mm' => (int) ($body['quotation_print_margin_mm'] ?? 10),
            'quotation_print_scale_pct' => (int) ($body['quotation_print_scale_pct'] ?? 100),
            'quotation_print_show_sku' => (int) ($body['quotation_print_show_sku'] ?? 0) === 1 ? 1 : 0,
            'quotation_print_show_tax_col' => (int) ($body['quotation_print_show_tax_col'] ?? 0) === 1 ? 1 : 0,
            'quotation_print_show_signatures' => (int) ($body['quotation_print_show_signatures'] ?? 0) === 1 ? 1 : 0,
            'quotation_print_footer_note' => trim((string) ($body['quotation_print_footer_note'] ?? '')),
        ];

        $errors = ShopSettingsValidator::validate(
            $old,
            $giros,
            $this->shopRepo->hasTicketPrintColumns(),
            $this->shopRepo->hasQuotationPrintColumns()
        );
        if ($errors !== []) {
            $shop = $this->shopRepo->findById($shopId);
            View::render('admin/configuracion/tienda', [
                'userName' => $this->getUserName(),
                'shopName' => (string) ($shop['name'] ?? $old['name']),
                'shopSlug' => (string) ($shop['slug'] ?? ''),
                'flash' => null,
                'errors' => $errors,
                'giros' => $giros,
                'old' => $old,
                'shopProfileMigrationNeeded' => !$this->shopRepo->hasProfileColumns(),
                'shopTicketMigrationNeeded' => !$this->shopRepo->hasTicketPrintColumns(),
                'shopQuotationMigrationNeeded' => !$this->shopRepo->hasQuotationPrintColumns(),
            ]);
            return;
        }

        $profileReady = $this->shopRepo->hasProfileColumns();

        $updatePayload = [
            'name' => $old['name'],
            'responsible_name' => $old['responsible_name'] !== '' ? $old['responsible_name'] : null,
            'rfc' => $old['rfc'] !== '' ? $old['rfc'] : null,
            'contact_email' => $old['contact_email'] !== '' ? $old['contact_email'] : null,
            'address' => $old['address'] !== '' ? $old['address'] : null,
            'phone' => $old['phone'] !== '' ? $old['phone'] : null,
            'business_type' => $old['business_type'] !== '' ? $old['business_type'] : null,
            'ticket_paper_width_mm' => $old['ticket_paper_width_mm'],
            'ticket_font_preset' => $old['ticket_font_preset'],
            'ticket_font_size_pt' => $old['ticket_font_size_pt'],
            'quotation_print_paper' => $old['quotation_print_paper'],
            'quotation_print_margin_mm' => $old['quotation_print_margin_mm'],
            'quotation_print_scale_pct' => $old['quotation_print_scale_pct'],
            'quotation_print_show_sku' => $old['quotation_print_show_sku'],
            'quotation_print_show_tax_col' => $old['quotation_print_show_tax_col'],
            'quotation_print_show_signatures' => $old['quotation_print_show_signatures'],
            'quotation_print_footer_note' => $old['quotation_print_footer_note'] !== '' ? $old['quotation_print_footer_note'] : null,
        ];

        $this->shopRepo->updateProfile($shopId, $updatePayload);

        $ticketOk = $this->shopRepo->hasTicketPrintColumns();
        $quoteOk = $this->shopRepo->hasQuotationPrintColumns();

        if ($profileReady && $ticketOk && $quoteOk) {
            Flash::set('success', 'Datos de la tienda guardados correctamente.');
        } elseif ($profileReady && $ticketOk) {
            Flash::set(
                'warning',
                'Datos de la tienda guardados. Para guardar también la impresión avanzada de cotizaciones, aplica la migración o ejecuta database/sql/hito17_quotation_print.sql en MySQL.'
            );
        } elseif ($profileReady && $quoteOk) {
            Flash::set(
                'warning',
                'Datos de la tienda guardados. Para guardar también la configuración de tickets, aplica la migración o ejecuta database/sql/hito13_ticket_print.sql en MySQL.'
            );
        } elseif ($profileReady) {
            Flash::set(
                'warning',
                'Datos de la tienda guardados. Para guardar también tickets e impresión de cotizaciones, aplica las migraciones o ejecuta database/sql/hito13_ticket_print.sql y database/sql/hito17_quotation_print.sql en MySQL.'
            );
        } else {
            Flash::set(
                'warning',
                'Solo se actualizó el nombre de la tienda. Para guardar responsable, RFC, correo, dirección, teléfono y giro, aplica la migración en el servidor: entra a /setup (confirmando con SI) o ejecuta el SQL database/sql/hito11_shop_settings.sql en tu base de datos.'
            );
        }
        Redirect::to('/admin/configuracion/tienda');
    }

    /**
     * @return list<string>
     */
    private function loadGiros(): array
    {
        $path = (string) ($GLOBALS['app_base_path'] ?? dirname(__DIR__, 2)) . '/config/shop_giros.php';
        if (!is_file($path)) {
            return [];
        }
        $list = require $path;
        return is_array($list) ? array_values(array_filter($list, fn ($g) => is_string($g) && $g !== '')) : [];
    }

    /**
     * @param array<string, mixed> $shop
     * @return array<string, string>
     */
    private function shopRowToOld(array $shop): array
    {
        return [
            'name' => (string) ($shop['name'] ?? ''),
            'responsible_name' => (string) ($shop['responsible_name'] ?? ''),
            'rfc' => (string) ($shop['rfc'] ?? ''),
            'contact_email' => (string) ($shop['contact_email'] ?? ''),
            'address' => (string) ($shop['address'] ?? ''),
            'phone' => (string) ($shop['phone'] ?? ''),
            'business_type' => (string) ($shop['business_type'] ?? ''),
            'ticket_paper_width_mm' => (int) ($shop['ticket_paper_width_mm'] ?? 80),
            'ticket_font_preset' => (string) ($shop['ticket_font_preset'] ?? 'sans_bold'),
            'ticket_font_size_pt' => (float) ($shop['ticket_font_size_pt'] ?? 13.0),
            'quotation_print_paper' => (string) ($shop['quotation_print_paper'] ?? 'letter'),
            'quotation_print_margin_mm' => (int) ($shop['quotation_print_margin_mm'] ?? 10),
            'quotation_print_scale_pct' => (int) ($shop['quotation_print_scale_pct'] ?? 100),
            'quotation_print_show_sku' => (int) ($shop['quotation_print_show_sku'] ?? 1),
            'quotation_print_show_tax_col' => (int) ($shop['quotation_print_show_tax_col'] ?? 1),
            'quotation_print_show_signatures' => (int) ($shop['quotation_print_show_signatures'] ?? 1),
            'quotation_print_footer_note' => (string) ($shop['quotation_print_footer_note'] ?? ''),
        ];
    }

    private function getUserName(): string
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT first_name, last_name FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => Auth::userId()]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
        return $name !== '' ? $name : 'Usuario';
    }
}
