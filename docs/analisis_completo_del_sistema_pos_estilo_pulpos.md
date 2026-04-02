# Análisis completo del sistema POS (estilo Pulpos)

> Documento de especificación funcional y técnica (basado en las pantallas y flujos compartidos). Enfoque: POS **general** (abarrotes, farmacia, papelería, ferretería, ropa, etc.) con diseño y experiencia **muy similar**.

---

## 0) Objetivo del sistema
Crear un Punto de Venta web que permita:
- **Vender rápido** (buscador/escáner → carrito → cobro → ticket).
- Controlar **inventario** (stock, mínimos, variantes opcionales, lotes opcionales).
- Gestionar **clientes**, **crédito**, **cotizaciones**.
- Controlar **caja** (ingresos/retiros, corte, históricos).
- Ver **reportes** (ventas, margen, métodos de pago, inventario, finanzas).
- Registrar **gastos** (egresos que no alteran inventario).

Opcionales a futuro: compras con entrada a inventario, compras desde XML, facturación CFDI, catálogo en línea/pedidos, bridge de impresión.

---

## 1) Principios de arquitectura
### 1.1 Multi-tienda y multi-usuario
- Cada **tienda** (shop) agrupa productos, ventas, clientes, caja, etc.
- **Usuarios** con roles y permisos (admin/cajero/vendedor).

### 1.2 UI/UX estilo Pulpos
- Sidebar oscuro fijo + sección activa.
- Topbar con tienda/usuario.
- Cards blancas con sombra ligera.
- Botón principal color teal.
- Modales centrados para acciones rápidas.

### 1.3 POS general (multi-giro)
- No “talla/color” como columnas fijas.
- Variantes mediante **atributos configurables** por producto.
- Lotes/caducidad como módulo opcional.

---

## 2) Onboarding
### 2.1 Modal Bienvenida
- 4 tarjetas: inventario, vender, facturas, catálogo en línea.
- Botón: **Comenzar**.

### 2.2 Modal Configura tu cuenta
Campos:
- Nombre, Apellido
- Giro (lista)
- ¿Cómo nos conociste? (lista)
- Ventas mensuales (lista)

Reglas:
- Al guardar → marcar onboarding completado y habilitar sistema.

---

## 3) Inicio (Dashboard)
- Barra “días de prueba” (opcional en tu versión final).
- Checklist 0/4 (venta de prueba, crear productos, etc.).

Reglas:
- Las tareas se completan automáticamente cuando el usuario realiza acciones.

---

## 4) Productos
### 4.1 Listado
- Buscar por nombre/SKU/código de barras.
- Botones: Crear etiquetas, Descargar, Importar.
- Botón “Agregar producto” (dropdown):
  - Nuevo producto
  - Nuevo servicio
  - Nuevo kit (opcional fase 2)
- Tabla: Producto (imagen+nombre), Stock, Categoría, Precio.

### 4.2 Crear producto rápido (modal)
Campos:
- Tipo (Producto/Servicio)
- Nombre
- Existencias + Unidad
- Código de barras (opcional)
- Categoría (buscar/crear)
- Costo (neto)
- Precio de venta
- Link: “Ir al editor completo”

### 4.3 Editor completo (formulario por secciones)
Secciones:
1) Datos del producto: nombre, tipo, barcode, SKU, checks (vender en POS / catálogo online)
2) Imágenes
3) Datos adicionales: unidad, categoría, marca, ubicación, descripción
4) Existencias: usar existencias, lotes/caducidad, cantidad, mínimo
5) Impuestos: IVA / IEPS
6) Variantes: opciones (tallas, colores, medidas, etc.)
7) Fabricación (opcional)
8) Precio de compra / costo
9) Precios de venta: lista “Público” + listas adicionales
10) Facturación: claves SAT (opcional)

Reglas clave:
- Si usa variantes: el stock vive en variantes y el producto muestra “en N variantes”.
- Si usa lotes: el stock se controla por lote.

---

## 5) POS / Nueva venta
Pantalla 2 columnas:
- Izquierda: buscador + acciones rápidas.
- Derecha: carrito + subtotal + botón grande **Cobrar**.

Acciones rápidas:
- Crear producto
- Entrada manual
- Añadir descuento
- Añadir cliente
- Añadir vendedor
- (Opcional) recargas/servicios

### 5.1 Descuento al total (modal)
- Tabs: Cantidad ($) / Porcentaje (%)
- Título opcional
- Aplicación al total (MVP)

### 5.2 Cliente (modal)
- Buscar por nombre/teléfono/email
- Nuevo cliente
- Incluye “Público en general”

### 5.3 Cobro (modal)
- Total a cobrar
- Métodos: Efectivo, A crédito, T. Crédito, T. Débito, Transferencia, Múltiples métodos
- Efectivo: captura recibido y calcula cambio

### 5.4 Confirmación “¡Listo!”
- Total y cambio
- Enviar por WhatsApp
- Descargar PDF
- Ajustes de tickets e impresión

---

## 6) Ventas (listado)
Tabla con:
- Venta/folio
- Fecha
- Cliente
- Pago/estatus
- Total
- Deuda
- Productos/entrega
- Cajero
- Vendedor
- Canal (POS)
- Factura (opcional)

Acción “Crear” (dropdown):
- Nueva venta
- Nuevo pedido (opcional fase futura)

---

## 7) Cotizaciones
### 7.1 Listado
Tabla: Cotización, Fecha, Creada por, Cliente, Estado, Total.

### 7.2 Crear cotización
Layout:
- Izquierda: productos + total.
- Derecha: cliente, válida desde/hasta, vendedor, dirección entrega, nota.
- Botones: cancelar / crear.

### 7.3 Confirmación
- Enviar por WhatsApp
- Descargar PDF

Opcional futuro:
- Convertir cotización → venta.

---

## 8) Clientes
### 8.1 Listado
- Importar / Exportar / Agregar
- Buscar por nombre/teléfono/email
- Tabla: Nombre, Teléfono, Email, Cantidad de ventas, Deuda

### 8.2 Nuevo cliente (modal)
Tabs:
- Datos básicos: nombre, apellido, teléfono, email
- Datos de facturación (opcional): RFC, razón social, régimen, uso CFDI, dirección fiscal

### 8.3 Detalle cliente
- Datos
- Ventas del cliente
- Deuda / pagos (opcional)
- Cotizaciones (opcional)

---

## 9) Caja
### 9.1 Caja actual
- Botones: Ingresar efectivo, Retirar efectivo, Hacer corte de caja
- Tabla “Total esperado” por método: efectivo, TDC, TDD, transferencia, total
- Card “Efectivo al inicio” (fondo inicial)

Regla:
- Total esperado = ventas pagadas por método + ingresos en efectivo − retiros en efectivo.

### 9.2 Cortes históricos
Tabla:
- Fecha
- Realizado por
- Total esperado
- Recuento manual
- Diferencia
- Retiro

---

## 10) Reportes (dashboard)
Filtros:
- Agrupación: por día (y opcional semana/mes)
- Rango: últimos 7 días (y opcional personalizado)

Secciones principales:
### 10.1 Ventas
- Ventas (conteo)
- Facturación (monto)
- Margen (monto)

### 10.2 Rankings y desgloses
- Productos más vendidos
- Productos con más margen
- Detalle de ventas
- Listado de ventas
- Ventas por método de pago
- Ventas por categoría
- Ventas por cajero
- Ventas por vendedor
- Ventas por cliente

### 10.3 Finanzas
- Cuentas por cobrar (por antigüedad)
- Cuentas por pagar (futuro si manejas proveedores)
- Utilidad = Ventas − Compras − Gastos

### 10.4 Inventario
- Stock de productos
- Entradas y salidas
- Valor del inventario (valor total, costo total, margen total)
- Por reponer (stock mínimo)
- Pronto a expirar (si lotes)
- Apartados (si reservas)

### 10.5 Compras y gastos
- Listado de compras
- Detalle compras
- Listado de gastos

---

## 11) Compras y Gastos (MVP actual)
### 11.1 Listado
Tabla: Número, Fecha, Proveedor, Estado, Total
Botón: **Nueva compra o gasto** (dropdown)

### 11.2 Nuevo gasto (implementación solicitada)
Campos:
- Concepto
- Importe (con impuestos / sin impuestos)
- IVA (ej. 16%)
- IEPS (No aplica)
- Proveedor (buscar/crear)
- Fecha del gasto
- Folio fiscal
- Notas
- Resumen: subtotal, IVA, total

Reglas de cálculo:
- Importe con impuestos: total = importe
- Importe sin impuestos: subtotal = importe; IVA = subtotal*IVA; total = subtotal+IVA

---

## 12) Reglas de negocio clave
- Folios consecutivos por tienda (ventas, cotizaciones, gastos).
- Cliente default “Público en general”.
- Crédito: deuda = total − pagado.
- Margen fiable: guardar `cost_snapshot` en cada partida de venta.
- Ventas generan salida de inventario (si usa existencias).
- Gastos NO afectan inventario.

---

## 13) Esquema de base de datos recomendado (resumen)
### 13.1 Núcleo
- shops
- users
- user_profiles (opcional)

### 13.2 Catálogos
- categories
- brands
- units

### 13.3 Productos
- products
- product_images

**Variantes (opcional):**
- product_options
- product_option_values
- product_variants

### 13.4 Ventas
- sales
- sale_items
- sale_payments

### 13.5 Clientes y cotizaciones
- customers
- customer_tax_profiles (opcional)
- quotations
- quotation_items
- addresses (opcional)

### 13.6 Caja
- cash_sessions
- cash_movements
- cash_audits

### 13.7 Gastos
- suppliers
- expenses
- expense_categories (opcional)

### 13.8 Inventario (para reportes completos)
- inventory_movements
- inventory_batches (opcional)
- reservations + reservation_items (opcional)

---

## 14) MVP recomendado (por fases)
### Fase 1 (imprescindible)
- Productos (crear rápido + editor)
- POS (venta + cobro + ticket pdf)
- Ventas listado
- Clientes + nuevo cliente
- Caja actual + IN/OUT + corte
- Gastos (nuevo gasto)
- Reportes básicos

### Fase 2
- Compras (entrada inventario)
- Cotizaciones + PDF + WhatsApp
- Reportes avanzados inventario/valor/margen
- Apartados
- Lotes/caducidad

### Fase 3
- Facturación CFDI
- Catálogo en línea / pedidos
- Bridge de impresión

---

## 15) Decisiones confirmadas (cerrado al 100%)
1) **Login y roles:** Sí. Habrá roles desde el inicio (mínimo: **Admin**, **Cajero**, **Vendedor**).
2) **Modo de operación:** POS **solo en línea**.
3) **Apertura/cierre de caja:** Se requiere **corte/apertura de caja al finalizar** (modelo por turno/sesión). Recomendación técnica: manejar **cash_sessions** (abierta) y obligar a **registrar corte** antes de cerrar turno.
4) **Cancelaciones/devoluciones:** Sí. Habrá cancelaciones/devoluciones con **retorno de inventario** y ajuste de caja/pagos.
5) **Gastos con método de pago:** Sí. Registrar **método de pago** (efectivo/transfer/tarjeta/otro) para reportes.

### 15.1 Implicaciones funcionales
- **Roles/permisos:**
  - Admin: configuración, catálogos, reportes, caja, usuarios.
  - Cajero: POS, ventas, caja, clientes (según permisos).
  - Vendedor: ventas/cotizaciones/clientes (sin caja, según permisos).
- **Caja por turno:**
  - Debe existir una **sesión de caja abierta** para cobrar.
  - Al finalizar turno: **corte de caja** (recuento manual) y cierre de sesión.
- **Cancelación/Devolución:**
  - Cambia estado de venta (CANCELLED/REFUNDED) y genera:
    - movimiento de inventario (IN)
    - ajuste de pagos/caja (egreso o reverso)
    - registro de auditoría (quién/cuándo/motivo)
- **Gastos por método:**
  - Alimenta reportes de egresos por método y afecta utilidad.

---

## 16) Próximo entregable sugerido
- Diagrama ER (mermaid)
- SQL CREATE TABLE de las tablas de Fase 1
- Esqueleto de proyecto PHP + Bootstrap con layout estilo Pulpos

