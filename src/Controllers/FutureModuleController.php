<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Redirect;
use App\Core\Request;
use App\Core\View;
use App\Database\Database;

class FutureModuleController
{
    /**
     * @var array<string, array{
     *   title:string,
     *   summary:string,
     *   roadmap: array<int, string>,
     *   dependencies: array<int, string>,
     *   nextSteps: array<int, string>
     * }>
     */
    private array $modules = [
        'compras' => [
            'title' => 'Compras',
            'summary' => 'Registrar compras a proveedores para alimentar inventario, costos y reportes.',
            'roadmap' => [
                'Modelo de compras: proveedor, referencia, fechas y método de pago/moneda.',
                'Entrada de inventario derivada de compras con movimiento trazable.',
                'Integración con costos y reportes operativos (utilidad).',
            ],
            'dependencies' => [
                'Catálogo de productos (y sus categorías)',
                'Modelo base de inventario / inventory_movements',
                'Usuarios y sesiones de caja (si aplica para pago o conciliación)',
            ],
            'nextSteps' => [
                'Definir campos mínimos y estados (activa/cancelada).',
                'Definir cómo se computa el impacto en stock y costo promedio.',
                'Crear rutas y formularios para registro de compras.',
            ],
        ],
        'cotizaciones' => [
            'title' => 'Cotizaciones',
            'summary' => 'Generar cotizaciones previas a la venta con borradores, vigencia y conversión.',
            'roadmap' => [
                'Borradores con edición: cliente, productos, precios y reglas comerciales.',
                'Vigencia y estado de cotización (borrador, enviada, vigente, vencida).',
                'Conversión a venta con historial y trazabilidad.',
            ],
            'dependencies' => [
                'Clientes',
                'Productos y precios (y sus reglas actuales)',
                'Ventas / flujo POS para conversión y auditoría',
            ],
            'nextSteps' => [
                'Definir el formato de salida (impresión/exportación) para cotizaciones.',
                'Alinear reglas de impuestos/descuentos con el modelo actual.',
                'Diseñar la UI de borrador y el historial administrativo.',
            ],
        ],
        'apartados' => [
            'title' => 'Apartados / Reservas',
            'summary' => 'Separar mercancía con anticipos, saldos y liberación según estados operativos.',
            'roadmap' => [
                'Estados del apartado y reglas de anticipo/saldo pendiente.',
                'Bloqueo/release de inventario (sin afectar la venta final prematuramente).',
                'Integración con caja para pagos parciales y conciliación.',
            ],
            'dependencies' => [
                'Inventario y movimientos',
                'Clientes',
                'Ventas (cuando el apartado se libera o se convierte)',
            ],
            'nextSteps' => [
                'Definir reglas de expiración y liberación automática.',
                'Definir cómo se registra el anticipo y su relación con caja.',
                'Diseñar estados visibles y reportes asociados.',
            ],
        ],
        'facturacion' => [
            'title' => 'Facturacion / Integración fiscal',
            'summary' => 'Preparar la estructura para generar facturas o integrarse con servicios fiscales externos.',
            'roadmap' => [
                'Datos fiscales del cliente y mapeo fiscal de la venta.',
                'Generación de documento (PDF/impresión) o integración externa.',
                'Persistencia del resultado, estados y auditoría.',
            ],
            'dependencies' => [
                'Ventas (y su persistencia histórica)',
                'Clientes y datos de identificación',
                'Impresión avanzada o exportación (según estrategia)',
            ],
            'nextSteps' => [
                'Definir alcance legal y campos mínimos requeridos.',
                'Definir estados: pendiente, enviada, aceptada, rechazada.',
                'Alinear el modelo de pagos y totales con la factura.',
            ],
        ],
        'tienda' => [
            'title' => 'Tienda en linea / Catalogo web',
            'summary' => 'Preparar la base para sincronizar catálogo, stock, precios y pedidos con el POS interno.',
            'roadmap' => [
                'Modelo de publicación: visibilidad, disponibilidad y reglas de stock.',
                'Sincronización catálogo/stock y precios hacia pedidos web.',
                'Flujo de pedidos web: confirmación y conversión al sistema interno.',
            ],
            'dependencies' => [
                'Productos e inventario',
                'Clientes',
                'Modelo de pedidos (a definir) conectado con ventas',
            ],
            'nextSteps' => [
                'Definir estrategia de sincronización (batch vs eventos).',
                'Definir mapeo de estados y tiempos de disponibilidad.',
                'Preparar endpoints/controladores para pedidos.',
            ],
        ],
        'impresion' => [
            'title' => 'Impresión avanzada',
            'summary' => 'Impresión térmica, tickets especializados, códigos de barras y otros periféricos.',
            'roadmap' => [
                'Diseño de plantillas de impresión por tipo de documento.',
                'Integración con un bridge de impresión (si aplica hardware específico).',
                'Soporte de códigos de barras y formatos de ticket.',
            ],
            'dependencies' => [
                'Generación de documentos (ventas, cotizaciones, facturas según módulo)',
                'Formato de datos y totales consistente con reportes',
                'Plantillas/renderer de impresión',
            ],
            'nextSteps' => [
                'Definir formatos (tipo ticket, tamaño, márgenes).',
                'Establecer estándar de numeración y texto fijo.',
                'Preparar pruebas con datos reales del POS.',
            ],
        ],
        'lotes' => [
            'title' => 'Lotes y caducidades',
            'summary' => 'Trazabilidad avanzada de inventario con lotes y fechas de caducidad.',
            'roadmap' => [
                'Ampliar modelo de inventario con lote/caducidad (o tabla de lotes).',
                'Asignación de lotes en compras y consumo en ventas/devoluciones.',
                'Reportes de caducidad y control de trazabilidad.',
            ],
            'dependencies' => [
                'Modelo actual de inventario e inventory_movements',
                'Compras/devoluciones para determinar entradas/salidas de lotes',
                'Reportes para alertas y análisis',
            ],
            'nextSteps' => [
                'Definir estrategia: FEFO/otros para consumo de lotes.',
                'Definir cómo se manejan productos sin lote/caducidad.',
                'Diseñar pantallas para selección de lotes.',
            ],
        ],
        'sucursales' => [
            'title' => 'Multi-sucursal',
            'summary' => 'Soportar múltiples sucursales/cajas con segmentación de inventario, usuarios y reportes.',
            'roadmap' => [
                'Segmentacion por tienda: inventario, usuarios y permisos.',
                'Cajas simultaneas con sesiones por sucursal.',
                'Reportes con filtros por sucursal/caja y consistencia operativa.',
            ],
            'dependencies' => [
                'Modelo existente de shops / cajas / sessions de caja',
                'Autorización por tienda (roles y scopes)',
                'Inventario y movimientos por tienda',
            ],
            'nextSteps' => [
                'Definir si “shop” ya cubre la necesidad completa o requiere separación adicional.',
                'Actualizar consultas para asegurar segmentación consistente.',
                'Preparar UI y permisos para operacion distribuida.',
            ],
        ],
        'dashboards' => [
            'title' => 'Dashboards ejecutivos',
            'summary' => 'Evolución de reportería hacia dashboards con KPIs, tendencias y comparativos.',
            'roadmap' => [
                'KPIs base: ventas, utilidad, caja, gastos e inventario (segun disponibilidad).',
                'Tendencias y comparativos históricos por periodo.',
                'Paneles ejecutivos y exportación de resumen.',
            ],
            'dependencies' => [
                'Reportes basicos (Hito 8)',
                'Datos consistentes de ventas, caja, gastos e inventario',
                'Evolución posterior con compras/cotizaciones/apartados si se adoptan',
            ],
            'nextSteps' => [
                'Definir layout de KPIs y criterios de cálculo.',
                'Definir filtros estándar (periodo, sucursal, usuario).',
                'Preparar un pipeline de métricas (consultas agregadas eficientes).',
            ],
        ],
    ];

    public function index(Request $request): void
    {
        $moduleKey = strtolower(trim((string) ($request->routeParams['module'] ?? '')));
        if ($moduleKey === '' || !array_key_exists($moduleKey, $this->modules)) {
            Flash::set('danger', 'Modulo no valido.');
            Redirect::to('/admin/dashboard');
        }

        $flash = Flash::consume();
        $module = $this->modules[$moduleKey];

        $userName = $this->getUserName();
        $shopName = $this->getShopName();
        $shopSlug = $this->getShopSlug();

        View::render('admin/futuro/indice', [
            'pageTitle' => 'Modulo futuro: ' . $module['title'],
            'flash' => $flash,
            'userName' => $userName,
            'shopName' => $shopName,
            'shopSlug' => $shopSlug,
            'moduleTitle' => $module['title'],
            'moduleKey' => $moduleKey,
            'moduleSummary' => $module['summary'],
            'moduleRoadmap' => $module['roadmap'],
            'moduleDependencies' => $module['dependencies'],
            'moduleNextSteps' => $module['nextSteps'],
        ]);
    }

    private function getUserName(): string
    {
        $userId = Auth::userId();
        if ($userId === null) {
            return 'Usuario';
        }

        $stmt = Database::pdo()->prepare(
            'SELECT first_name, last_name
             FROM users
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $userId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        $first = trim((string) ($row['first_name'] ?? ''));
        $last = trim((string) ($row['last_name'] ?? ''));
        $name = trim($first . ' ' . $last);

        return $name !== '' ? $name : 'Usuario';
    }

    private function getShopName(): string
    {
        $shopId = Auth::shopId();
        if ($shopId === null) {
            return '';
        }

        $stmt = Database::pdo()->prepare(
            'SELECT name
             FROM shops
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $shopId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (string) ($row['name'] ?? '');
    }

    private function getShopSlug(): string
    {
        $shopId = Auth::shopId();
        if ($shopId === null) {
            return '';
        }

        $stmt = Database::pdo()->prepare(
            'SELECT slug
             FROM shops
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $shopId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (string) ($row['slug'] ?? '');
    }
}

