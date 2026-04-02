<?php

declare(strict_types=1);

use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\CategoryController;
use App\Controllers\HealthController;
use App\Controllers\InstallController;
use App\Controllers\ProductController;
use App\Controllers\CustomerController;
use App\Controllers\CashController;
use App\Controllers\POSController;
use App\Controllers\SalesController;
use App\Controllers\ExpenseController;
use App\Controllers\ExpenseCategoryController;
use App\Controllers\ReportController;
use App\Controllers\FutureModuleController;
use App\Controllers\CatalogController;
use App\Controllers\ShopSettingsController;
use App\Controllers\ShopUserController;
use App\Controllers\QuotationController;
use App\Controllers\LayawayController;
use App\Controllers\InventoryBatchController;
use App\Core\Redirect;
use App\Core\Request;
use App\Core\Router;
use App\Core\View;

// Nota: este archivo asume que existe la variable $router en el scope.

$router->get('/', function (Request $request) {
    if (isset($_SESSION['auth']['user_id'])) {
        Redirect::to('/admin/dashboard');
    }
    Redirect::to('/login');
});

$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/logout', [AuthController::class, 'logout']);

$router->get('/admin/dashboard', [AdminController::class, 'dashboard'], [
    'auth' => true,
]);

$router->get('/admin/configuracion/tienda', [ShopSettingsController::class, 'index'], [
    'auth' => true,
]);
$router->post('/admin/configuracion/tienda', [ShopSettingsController::class, 'save'], [
    'auth' => true,
]);

$router->get('/admin/configuracion/usuarios', [ShopUserController::class, 'index'], [
    'auth' => true,
    'roles' => ['admin'],
]);
$router->get('/admin/configuracion/usuarios/crear', [ShopUserController::class, 'create'], [
    'auth' => true,
    'roles' => ['admin'],
]);
$router->post('/admin/configuracion/usuarios/guardar', [ShopUserController::class, 'store'], [
    'auth' => true,
    'roles' => ['admin'],
]);
$router->get('/admin/configuracion/usuarios/editar/{id}', [ShopUserController::class, 'edit'], [
    'auth' => true,
    'roles' => ['admin'],
]);
$router->post('/admin/configuracion/usuarios/actualizar/{id}', [ShopUserController::class, 'update'], [
    'auth' => true,
    'roles' => ['admin'],
]);
$router->post('/admin/configuracion/usuarios/cambiar-estado/{id}', [ShopUserController::class, 'toggleStatus'], [
    'auth' => true,
    'roles' => ['admin'],
]);

$router->get('/admin/health', [HealthController::class, 'index'], [
    'auth' => true,
]);

// Catálogo: categorías
$router->get('/admin/categorias', [CategoryController::class, 'index'], ['auth' => true]);
$router->get('/admin/categorias/crear', [CategoryController::class, 'create'], ['auth' => true]);
$router->post('/admin/categorias/guardar', [CategoryController::class, 'store'], ['auth' => true]);
$router->get('/admin/categorias/editar/{id}', [CategoryController::class, 'edit'], ['auth' => true]);
$router->post('/admin/categorias/actualizar/{id}', [CategoryController::class, 'update'], ['auth' => true]);
$router->post('/admin/categorias/cambiar-estado/{id}', [CategoryController::class, 'toggleStatus'], ['auth' => true]);

// Catálogo: productos
$router->get('/admin/productos', [ProductController::class, 'index'], ['auth' => true]);
$router->get('/admin/productos/crear', [ProductController::class, 'create'], ['auth' => true]);
$router->post('/admin/productos/guardar', [ProductController::class, 'store'], ['auth' => true]);
$router->get('/admin/productos/editar/{id}', [ProductController::class, 'edit'], ['auth' => true]);
$router->post('/admin/productos/actualizar/{id}', [ProductController::class, 'update'], ['auth' => true]);
$router->post('/admin/productos/cambiar-estado/{id}', [ProductController::class, 'toggleStatus'], ['auth' => true]);
$router->get('/admin/productos/imagen/{productId}/{imageId}/miniatura', [ProductController::class, 'imageGalleryMiniatura'], ['auth' => true]);
$router->get('/admin/productos/{id}/imagen-miniatura', [ProductController::class, 'imagePrimaryMiniatura'], ['auth' => true]);
$router->get('/admin/productos/imagen/{productId}/{imageId}', [ProductController::class, 'imageGallery'], ['auth' => true]);
$router->get('/admin/productos/imagen/{id}', [ProductController::class, 'image'], ['auth' => true]);

// Catálogo: clientes
$router->get('/admin/clientes', [CustomerController::class, 'index'], ['auth' => true]);
$router->get('/admin/clientes/deuda/{id}', [CustomerController::class, 'deuda'], ['auth' => true]);
$router->post('/admin/clientes/deuda/{id}/pago', [CustomerController::class, 'registrarPagoDeuda'], ['auth' => true]);
$router->get('/admin/clientes/crear', [CustomerController::class, 'create'], ['auth' => true]);
$router->post('/admin/clientes/guardar', [CustomerController::class, 'store'], ['auth' => true]);
$router->get('/admin/clientes/editar/{id}', [CustomerController::class, 'edit'], ['auth' => true]);
$router->post('/admin/clientes/actualizar/{id}', [CustomerController::class, 'update'], ['auth' => true]);
$router->post('/admin/clientes/cambiar-estado/{id}', [CustomerController::class, 'toggleStatus'], ['auth' => true]);

// Caja (Hito 4)
$router->get('/admin/caja', [CashController::class, 'index'], ['auth' => true]);
$router->get('/admin/caja/apertura', [CashController::class, 'apertura'], ['auth' => true]);
$router->post('/admin/caja/guardar-apertura', [CashController::class, 'guardarApertura'], ['auth' => true]);
$router->get('/admin/caja/ingreso', [CashController::class, 'ingreso'], ['auth' => true]);
$router->post('/admin/caja/guardar-ingreso', [CashController::class, 'guardarIngreso'], ['auth' => true]);
$router->get('/admin/caja/retiro', [CashController::class, 'retiro'], ['auth' => true]);
$router->post('/admin/caja/guardar-retiro', [CashController::class, 'guardarRetiro'], ['auth' => true]);
$router->get('/admin/caja/cierre', [CashController::class, 'cierre'], ['auth' => true]);
$router->post('/admin/caja/guardar-cierre', [CashController::class, 'guardarCierre'], ['auth' => true]);
$router->get('/admin/caja/historial', [CashController::class, 'historial'], ['auth' => true]);
$router->get('/admin/caja/detalle/{id}', [CashController::class, 'detalle'], ['auth' => true]);
$router->get('/admin/caja/corte-cajero', [CashController::class, 'corteCajero'], ['auth' => true]);
$router->get('/admin/caja/corte-cajero/ticket', [CashController::class, 'corteCajeroTicket'], ['auth' => true]);

// POS: Nueva venta (Hito 5)
$router->get('/admin/pos/nueva-venta', [POSController::class, 'nuevaVenta'], ['auth' => true]);
$router->post('/admin/pos/nueva-venta/confirmar', [POSController::class, 'confirmarVenta'], ['auth' => true]);
$router->get('/admin/pos/productos/buscar', [POSController::class, 'buscarProductos'], ['auth' => true]);
$router->get('/admin/pos/clientes/buscar', [POSController::class, 'buscarClientes'], ['auth' => true]);
$router->get('/admin/pos/ticket/{id}', [POSController::class, 'ticket'], ['auth' => true]);

// Cotizaciones
$router->get('/admin/cotizaciones', [QuotationController::class, 'index'], ['auth' => true]);
$router->get('/admin/cotizaciones/crear', [QuotationController::class, 'crear'], ['auth' => true]);
$router->post('/admin/cotizaciones/guardar', [QuotationController::class, 'guardar'], ['auth' => true]);
$router->get('/admin/cotizaciones/documento/{id}', [QuotationController::class, 'documento'], ['auth' => true]);
$router->post('/admin/cotizaciones/marcar-vendida/{id}', [QuotationController::class, 'marcarVendida'], ['auth' => true]);

// Apartados
$router->get('/admin/apartados', [LayawayController::class, 'index'], ['auth' => true]);
$router->get('/admin/apartados/crear', [LayawayController::class, 'crear'], ['auth' => true]);
$router->post('/admin/apartados/guardar', [LayawayController::class, 'guardar'], ['auth' => true]);
$router->get('/admin/apartados/documento/{id}', [LayawayController::class, 'documento'], ['auth' => true]);
$router->get('/admin/apartados/ticket/{id}', [LayawayController::class, 'ticket'], ['auth' => true]);
$router->post('/admin/apartados/registrar-abono/{id}', [LayawayController::class, 'registrarAbono'], ['auth' => true]);
$router->post('/admin/apartados/cancelar/{id}', [LayawayController::class, 'cancelar'], ['auth' => true]);

// Ventas: historial administrativo (Hito 6)
$router->get('/admin/ventas', [SalesController::class, 'index'], ['auth' => true]);
$router->get('/admin/ventas/detalle/{id}', [SalesController::class, 'detalle'], ['auth' => true]);
$router->get('/admin/ventas/ticket/{id}', [SalesController::class, 'ticket'], ['auth' => true]);
$router->post('/admin/ventas/cancelar/{id}', [SalesController::class, 'cancelar'], ['auth' => true, 'roles' => ['admin', 'cajero']]);
$router->post('/admin/ventas/devolver/{id}', [SalesController::class, 'devolver'], ['auth' => true, 'roles' => ['admin', 'cajero']]);

// Gastos (Hito 7)
$router->get('/admin/gastos', [ExpenseController::class, 'index'], ['auth' => true]);
$router->get('/admin/gastos/crear', [ExpenseController::class, 'create'], ['auth' => true]);
$router->post('/admin/gastos/guardar', [ExpenseController::class, 'store'], ['auth' => true]);
$router->get('/admin/gastos/detalle/{id}', [ExpenseController::class, 'detalle'], ['auth' => true]);
$router->get('/admin/gastos/editar/{id}', [ExpenseController::class, 'edit'], ['auth' => true]);
$router->post('/admin/gastos/actualizar/{id}', [ExpenseController::class, 'update'], ['auth' => true]);
$router->post('/admin/gastos/anular/{id}', [ExpenseController::class, 'anular'], ['auth' => true]);

$router->get('/admin/gastos/categorias', [ExpenseCategoryController::class, 'index'], ['auth' => true]);
$router->get('/admin/gastos/categorias/crear', [ExpenseCategoryController::class, 'create'], ['auth' => true]);
$router->post('/admin/gastos/categorias/guardar', [ExpenseCategoryController::class, 'store'], ['auth' => true]);
$router->get('/admin/gastos/categorias/editar/{id}', [ExpenseCategoryController::class, 'edit'], ['auth' => true]);
$router->post('/admin/gastos/categorias/actualizar/{id}', [ExpenseCategoryController::class, 'update'], ['auth' => true]);
$router->post('/admin/gastos/categorias/cambiar-estado/{id}', [ExpenseCategoryController::class, 'toggleStatus'], ['auth' => true]);

// Lotes y caducidades
$router->get('/admin/lotes', [InventoryBatchController::class, 'index'], ['auth' => true]);
$router->get('/admin/lotes/crear', [InventoryBatchController::class, 'create'], ['auth' => true]);
$router->post('/admin/lotes/guardar', [InventoryBatchController::class, 'store'], ['auth' => true]);
$router->get('/admin/lotes/editar/{id}', [InventoryBatchController::class, 'edit'], ['auth' => true]);
$router->post('/admin/lotes/actualizar/{id}', [InventoryBatchController::class, 'update'], ['auth' => true]);
$router->post('/admin/lotes/eliminar/{id}', [InventoryBatchController::class, 'delete'], ['auth' => true]);

// Reportes (Hito 8)
$router->get('/admin/reportes', [ReportController::class, 'index'], ['auth' => true]);
$router->get('/admin/reportes/exportar', [ReportController::class, 'export'], ['auth' => true]);

// Tienda en línea (Catalogo web) - publico
$router->get('/catalogo/{shopSlug}', [CatalogController::class, 'index']);
$router->get('/catalogo/{shopSlug}/producto/{productId}', [CatalogController::class, 'product']);
$router->get('/catalogo/{shopSlug}/producto/{productId}/imagen/{imageId}/miniatura', [CatalogController::class, 'galleryImageMiniatura']);
$router->get('/catalogo/{shopSlug}/producto/{productId}/imagen-miniatura', [CatalogController::class, 'productImageMiniatura']);
$router->get('/catalogo/{shopSlug}/producto/{productId}/imagen/{imageId}', [CatalogController::class, 'galleryImage']);
$router->get('/catalogo/{shopSlug}/producto/{productId}/imagen', [CatalogController::class, 'image']);

// Compatibilidad: enlace antiguo de cotizaciones (módulo futuro) ahora apunta al módulo real.
$router->get('/admin/futuro/cotizaciones', function (Request $request) {
    Redirect::to('/admin/cotizaciones');
});
$router->get('/admin/futuro/apartados', function (Request $request) {
    Redirect::to('/admin/apartados');
});
$router->get('/admin/futuro/lotes', function (Request $request) {
    Redirect::to('/admin/lotes');
});

// Modulos futuros (Hito 10) - placeholders de UI/roadmap
$router->get('/admin/futuro/{module}', [FutureModuleController::class, 'index'], ['auth' => true]);

// Setup inicial sin autenticación.
// Útil para cuando aún no existen tablas en MySQL.
$router->get('/setup', [InstallController::class, 'index']);
$router->post('/setup/run', [InstallController::class, 'run']);

