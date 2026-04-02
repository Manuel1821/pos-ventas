# Hito 6: Módulo de ventas del POS SaaS

## Objetivo del hito

Construir el módulo de ventas del sistema POS SaaS para permitir la consulta, administración y seguimiento de las ventas registradas desde el punto de venta. Este hito debe dejar lista una vista operativa y administrativa del historial de ventas, facilitando la búsqueda, filtrado, revisión de detalles, reimpresión de comprobantes y consulta de pagos asociados.

La meta principal de este hito es separar claramente la operación de captura de nuevas ventas del análisis y gestión posterior de las mismas. Mientras el módulo POS se enfoca en vender, este módulo debe permitir revisar qué se vendió, cuándo se vendió, quién la realizó, a qué cliente se asoció, cómo se pagó y cuál es el estado de la transacción.

Este hito también debe mantener una experiencia visual moderna, clara y enfocada en rapidez administrativa, ya que será una pantalla de consulta frecuente para supervisión, aclaraciones y revisión operativa.

---

## Tareas del hito

### 1. Revisión y ajuste del modelo de datos de ventas
- Validar que las tablas `sales`, `sale_items` y `sale_payments` cubran correctamente los requerimientos del módulo.
- Revisar campos como folio, fecha, usuario vendedor, cliente, subtotal, descuento, impuesto, total, estado y observaciones.
- Confirmar la estructura necesaria para mostrar detalle de productos vendidos y pagos realizados.
- Verificar relaciones con clientes, usuarios, caja e inventario.
- Asegurar índices para consultas por fecha, folio, cliente, usuario o estado.

### 2. Construcción del listado general de ventas
- Implementar la vista principal del historial de ventas.
- Mostrar información clave como folio, fecha, cliente, vendedor, total, estado y forma de pago principal o resumen de pagos.
- Diseñar una tabla clara, ordenada y visualmente atractiva.
- Incorporar paginación o carga eficiente de resultados.
- Definir acciones rápidas por registro: ver detalle, reimprimir, consultar pagos y futuras acciones administrativas.

### 3. Implementación de filtros y búsqueda
- Permitir búsqueda por folio de venta.
- Permitir filtros por rango de fechas.
- Permitir filtros por cliente.
- Permitir filtros por usuario o cajero, si aplica.
- Permitir filtros por estado de venta.
- Asegurar tiempos de respuesta razonables aun con crecimiento del historial.

### 4. Vista de detalle de venta
- Construir una vista detallada por venta.
- Mostrar información general de la operación.
- Mostrar productos vendidos con cantidades, precios, descuentos e impuestos.
- Mostrar pagos realizados, montos y métodos.
- Mostrar cliente asociado, vendedor y caja relacionada.
- Mostrar observaciones o notas si forman parte del modelo.

### 5. Reimpresión o regeneración de comprobante
- Permitir reimprimir o regenerar el ticket o comprobante de una venta ya registrada.
- Asegurar que el comprobante refleje la información persistida y no datos alterados por cambios posteriores.
- Preparar una versión legible e imprimible.
- Mantener consistencia con el formato definido en el módulo POS.

### 6. Consulta de pagos y estado de la venta
- Mostrar pagos relacionados a una venta.
- Identificar método o métodos de pago utilizados.
- Preparar la estructura para ventas liquidadas, parciales o con saldo pendiente, si el alcance futuro lo requiere.
- Mostrar de forma clara el estado general de la venta.
- Dejar trazabilidad suficiente para auditoría administrativa.

### 7. Reglas operativas del módulo
- Definir qué estados de venta se mostrarán en esta etapa.
- Definir si una venta podrá editarse después de confirmada o será solo de consulta.
- Establecer qué acciones están permitidas antes de llegar al módulo de cancelaciones y devoluciones.
- Asegurar que la consulta del historial no comprometa la integridad de la información original.

### 8. Integración con módulos previos
- Consumir correctamente información proveniente del módulo POS.
- Relacionar ventas con clientes, usuarios y caja.
- Mostrar pagos registrados desde el proceso de cobro.
- Preparar la información para futura integración con reportes, cancelaciones y devoluciones.

### 9. Definición visual del módulo
- Diseñar vistas atractivas, modernas y orientadas a consulta rápida.
- Priorizar una tabla de ventas legible y bien jerarquizada.
- Diseñar una vista de detalle clara y cómoda de revisar.
- Preparar badges o indicadores visuales para estados de venta y pagos.
- Mantener consistencia con la línea visual del sistema.

### 10. Validaciones funcionales del módulo
- Verificar que las ventas confirmadas aparezcan correctamente en el historial.
- Verificar búsqueda por folio y filtros por fecha, cliente o estado.
- Verificar visualización correcta del detalle.
- Verificar consulta correcta de pagos.
- Verificar reimpresión del comprobante.
- Confirmar que el módulo quede listo como base administrativa previa al manejo de cancelaciones y devoluciones.

---

## Requerimientos técnicos

### Tecnologías base
- **PHP** para lógica del módulo y renderizado de vistas.
- **MySQL** para persistencia y consulta del historial de ventas.
- **HTML, CSS, JavaScript y Bootstrap 5** para interfaz.
- **PDO** para acceso a datos.
- **Bootstrap Icons** o **Font Awesome** para mejorar la experiencia visual.

### Estructura técnica del módulo
- Separación entre controlador, servicio, acceso a datos y vistas.
- Reutilización del layout y componentes definidos en el Hito 1.
- Lógica de consulta desacoplada de la lógica de captura del POS.
- Preparación para escalar consultas y filtros sin afectar rendimiento general.
- Integración limpia con módulos de clientes, caja, POS y reportes.

### Base de datos y persistencia
- Uso de tablas `sales`, `sale_items` y `sale_payments`.
- Relación con `customers`, `users`, `cash_sessions` y productos vendidos.
- Índices adecuados para folio, fecha, cliente, usuario y estado.
- Uso de InnoDB y `utf8mb4`.
- Conservación de snapshots o datos persistidos necesarios para mantener el historial fiel a la venta original.

### Reglas técnicas recomendadas
- El detalle histórico debe basarse en datos persistidos de la venta, no en datos vivos susceptibles a cambios posteriores.
- Las consultas deben usar paginación o mecanismos equivalentes para evitar sobrecarga.
- Deben utilizarse consultas preparadas y validación de filtros de entrada.
- El sistema debe permitir un crecimiento progresivo del historial sin rediseño mayor.
- La reimpresión debe reflejar exactamente el estado original o el estado persistido válido de la venta.

### Requerimientos visuales
- El listado de ventas debe ser claro y fácil de revisar.
- Los filtros deben ser simples y visibles.
- El detalle de venta debe tener buena jerarquía visual.
- Deben existir indicadores visuales claros para estado de venta, pagos y otros datos clave.
- El módulo debe ser responsivo y consistente con los hitos anteriores.

### Preparación para módulos futuros
Este módulo debe quedar listo para ser utilizado por:
- Reportes
- Cancelaciones y devoluciones
- Auditoría operativa
- Funciones futuras como crédito, apartados o facturación

---

## Requerimientos funcionales

### Funcionalidades mínimas esperadas
- El usuario autorizado debe poder acceder al historial de ventas.
- Debe poder ver un listado de ventas registradas.
- Debe poder buscar ventas por folio.
- Debe poder filtrar por fecha, cliente, usuario o estado.
- Debe poder consultar el detalle completo de una venta.
- Debe poder consultar los pagos asociados.
- Debe poder reimprimir o regenerar el comprobante de la venta.
- El módulo debe mostrar información suficiente para revisión administrativa.

### Datos funcionales sugeridos para la vista de ventas
El módulo idealmente debe contemplar:
- folio de venta
- fecha y hora
- usuario vendedor
- cliente
- subtotal
- descuento
- impuesto
- total
- estado de venta
- método o resumen de métodos de pago
- detalle de productos vendidos
- caja asociada
- observaciones, si aplica

### Comportamientos esperados
- Toda venta confirmada en el POS debe aparecer en este módulo.
- El historial debe ser confiable y estable.
- La vista detalle debe reflejar fielmente la operación realizada.
- La reimpresión debe estar disponible para consultas o aclaraciones.
- El módulo debe servir como base administrativa antes de implementar cancelaciones o devoluciones.

---

## Definición del hito

El **Hito 6** se considerará terminado cuando el sistema cuente con un módulo de ventas funcional, visualmente consistente y técnicamente sólido, capaz de mostrar y administrar el historial de ventas registradas por el POS.

### Criterios de cumplimiento
- Existe un listado funcional de ventas.
- Existen filtros y búsqueda por criterios relevantes.
- Existe una vista de detalle de venta.
- Existe consulta de pagos asociados.
- Existe reimpresión o regeneración del comprobante.
- El módulo persiste y consulta correctamente la información histórica.
- El módulo mantiene consistencia visual con el sistema.
- El flujo queda listo para integrarse al **Hito 7: Módulo de gastos** y al **Hito 8: Módulo de reportes básicos**.

### Resultado del negocio
Al terminar este hito, el negocio contará con una herramienta clara para revisar, consultar y dar seguimiento a las ventas ya realizadas, facilitando aclaraciones, supervisión administrativa y control operativo diario.

---

## Resumen ejecutivo del hito

El Hito 6 consiste en desarrollar el módulo de ventas del POS SaaS. Su meta es dejar lista la consulta administrativa del historial de ventas, con filtros, vista de detalle, consulta de pagos y reimpresión de comprobantes. Este módulo complementa el flujo del POS y permite pasar de la simple captura de ventas a una administración operativa y documental más completa.

