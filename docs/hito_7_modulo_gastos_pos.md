# Hito 7: Módulo de gastos del POS SaaS

## Objetivo del hito

Construir el módulo de gastos del sistema POS SaaS para permitir el registro, consulta, clasificación y seguimiento de los egresos del negocio. Este hito debe dejar listo un flujo administrativo claro para capturar gastos operativos, relacionarlos con categorías y, cuando aplique, con proveedores, métodos de pago y observaciones, de modo que el sistema pueda reflejar mejor la realidad financiera del negocio.

La meta principal de este hito es complementar la información de ventas con el control de egresos, permitiendo que el sistema evolucione de un simple punto de venta a una herramienta más completa de administración operativa. Este módulo servirá como base para reportes de gastos, análisis de utilidad y control general del negocio.

Además de resolver la parte funcional, este hito debe mantener una experiencia visual moderna, clara y fácil de operar, ya que el registro de gastos debe ser rápido, entendible y consistente con el resto del sistema.

---

## Tareas del hito

### 1. Revisión y ajuste del modelo de datos de gastos
- Validar que las tablas creadas en el Hito 1 para gastos cubran las necesidades operativas del sistema.
- Revisar la estructura de `expenses` y `expense_categories` o las tablas equivalentes definidas.
- Confirmar campos como fecha, categoría, descripción, monto, método de pago, referencia, proveedor, usuario responsable, observaciones y estado si aplica.
- Verificar relaciones con usuarios, caja y reportes, cuando corresponda.
- Asegurar índices para consultas por fecha, categoría, proveedor y método de pago.

### 2. Gestión de categorías de gasto
- Implementar el flujo de alta, edición, consulta y desactivación de categorías de gasto.
- Validar unicidad de nombre si aplica.
- Relacionar categorías con el registro de gastos.
- Preparar la interfaz administrativa del catálogo de categorías.

### 3. Construcción del listado general de gastos
- Implementar la vista principal del historial de gastos.
- Mostrar información clave como fecha, categoría, descripción, monto, método de pago, proveedor y usuario responsable si aplica.
- Diseñar una tabla clara, ordenada y visualmente atractiva.
- Incorporar búsqueda, filtros y paginación o carga eficiente.
- Definir acciones rápidas por registro: ver, editar, desactivar o eliminar lógicamente según la política del sistema.

### 4. Desarrollo del formulario de alta de gasto
- Crear el formulario de captura de gastos.
- Permitir seleccionar categoría.
- Permitir registrar descripción, monto, fecha, método de pago, proveedor y observaciones, según el alcance aprobado.
- Incluir validaciones del lado servidor y del lado cliente cuando aplique.
- Preparar una experiencia de captura simple, clara y rápida.

### 5. Desarrollo del formulario de edición de gasto
- Permitir modificar la información principal del gasto.
- Validar cambios antes de guardar.
- Mantener integridad del historial administrativo.
- Definir si existen límites de edición según el estado del gasto o el tiempo transcurrido.

### 6. Relación con proveedor y método de pago
- Definir si en este hito se manejará una referencia simple de proveedor o un catálogo formal.
- Permitir registrar método de pago del gasto.
- Preparar base para análisis posteriores por proveedor o forma de pago.
- Asegurar consistencia entre la captura y la información mostrada en listados y reportes.

### 7. Reglas operativas del módulo
- Definir si los gastos impactan caja en este hito o solo se registran como egreso administrativo.
- Definir si se permitirá editar gastos una vez registrados.
- Definir si se manejará eliminación lógica en vez de eliminación física.
- Establecer reglas para evitar montos inválidos o categorías inexistentes.
- Preparar la lógica para que el módulo alimente correctamente reportes de utilidad.

### 8. Integración con módulos previos y futuros
- Relacionar gastos con usuarios responsables.
- Preparar integración con caja si el negocio decide que ciertos gastos afectan caja directamente.
- Preparar el módulo para alimentar reportes de gastos y utilidad.
- Mantener compatibilidad con futuras funciones administrativas.

### 9. Definición visual del módulo
- Diseñar vistas atractivas, modernas y operativamente claras.
- Crear formularios cómodos de usar y listados fáciles de revisar.
- Diseñar filtros visibles y comprensibles.
- Preparar indicadores visuales de categoría, método de pago o estado si aplica.
- Mantener consistencia visual con el resto del sistema.

### 10. Validaciones funcionales del módulo
- Verificar alta correcta de gastos.
- Verificar edición correcta.
- Verificar administración de categorías.
- Verificar filtros por fecha, categoría, proveedor o método de pago.
- Confirmar consistencia de montos registrados.
- Confirmar que el módulo quede listo para integrarse al sistema de reportes.

---

## Requerimientos técnicos

### Tecnologías base
- **PHP** para lógica del módulo y renderizado de vistas.
- **MySQL** para persistencia de gastos y categorías.
- **HTML, CSS, JavaScript y Bootstrap 5** para interfaz.
- **PDO** para acceso a datos.
- **Bootstrap Icons** o **Font Awesome** para reforzar la interfaz.

### Estructura técnica del módulo
- Separación entre controlador, servicio, acceso a datos y vistas.
- Reutilización del layout y componentes definidos en el Hito 1.
- Validaciones reutilizables para formularios y filtros.
- Preparación del módulo para integrarse con reportes y, en caso necesario, con caja.

### Base de datos y persistencia
- Uso de tablas `expenses` y `expense_categories`.
- Relación con `users` y, de manera opcional según alcance, con proveedor o caja.
- Índices adecuados para fecha, categoría, método de pago y proveedor.
- Uso de InnoDB y `utf8mb4`.
- Soporte para trazabilidad histórica del gasto.

### Reglas técnicas recomendadas
- El monto del gasto debe validarse como valor numérico positivo.
- La categoría debe existir y estar activa para poder usarse.
- Deben emplearse consultas preparadas y validación de entradas.
- La eliminación lógica es preferible para mantener historial administrativo.
- Las consultas del historial deben ser eficientes y preparadas para crecer con el tiempo.

### Requerimientos visuales
- El módulo debe ser fácil de usar y rápido para captura administrativa.
- Los formularios deben ser claros y breves.
- Los listados deben resaltar información útil sin saturar la pantalla.
- Deben existir estados visuales para errores de validación, registros inactivos y resultados de búsqueda.
- El módulo debe ser responsivo y consistente con los hitos anteriores.

### Preparación para módulos futuros
Este módulo debe quedar listo para ser utilizado por:
- Reportes
- Utilidad operativa
- Caja, si se define impacto directo
- Auditoría administrativa
- Funciones futuras relacionadas con compras o cuentas por pagar

---

## Requerimientos funcionales

### Funcionalidades mínimas esperadas
- El usuario autorizado debe poder ver un listado de gastos.
- Debe poder registrar nuevos gastos.
- Debe poder editar gastos existentes, según la política definida.
- Debe poder buscar y filtrar gastos por fecha, categoría, proveedor o método de pago.
- Debe poder administrar categorías de gasto.
- Debe poder capturar descripción, monto, fecha y método de pago.
- Debe poder registrar proveedor u observaciones si el alcance lo incluye.
- El módulo debe quedar listo para alimentar reportes de gastos y utilidad.

### Datos funcionales sugeridos para el gasto
El módulo idealmente debe contemplar:
- fecha
- categoría de gasto
- descripción
- monto
- método de pago
- proveedor
- referencia
- observaciones
- usuario responsable
- estado del gasto, si aplica

### Comportamientos esperados
- No debe permitirse registrar gastos con montos inválidos.
- No debe permitirse usar categorías inexistentes o inactivas.
- El historial debe ser confiable y claro.
- Los filtros deben facilitar la revisión administrativa.
- El módulo debe servir como base directa para el análisis posterior de utilidad y egresos.

---

## Definición del hito

El **Hito 7** se considerará terminado cuando el sistema cuente con un módulo de gastos funcional, visualmente consistente y técnicamente sólido, capaz de registrar y administrar egresos del negocio de manera clara y ordenada.

### Criterios de cumplimiento
- Existe un listado funcional de gastos.
- Existe alta de gastos con validaciones.
- Existe edición de gastos, si fue aprobada en el alcance.
- Existe administración de categorías de gasto.
- Existen filtros y búsqueda por criterios relevantes.
- El módulo persiste correctamente la información en la base de datos.
- El módulo mantiene consistencia visual con el sistema.
- El flujo queda listo para integrarse al **Hito 8: Módulo de reportes básicos**.

### Resultado del negocio
Al terminar este hito, el negocio contará con control administrativo de egresos, lo que permitirá ordenar mejor los gastos operativos y complementar la información necesaria para conocer la utilidad real del negocio.

---

## Resumen ejecutivo del hito

El Hito 7 consiste en desarrollar el módulo de gastos del POS SaaS. Su meta es dejar listo el registro, consulta y clasificación de egresos mediante una interfaz clara, atractiva y funcional, incluyendo categorías, filtros, métodos de pago y observaciones. Este módulo permitirá ampliar la visión administrativa del sistema y servirá como base directa para los reportes financieros y de utilidad.

