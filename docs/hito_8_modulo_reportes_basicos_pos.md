# Hito 8: Módulo de reportes básicos del POS SaaS

## Objetivo del hito

Construir el módulo de reportes básicos del sistema POS SaaS para ofrecer visibilidad operativa y administrativa sobre la información registrada en ventas, caja, gastos e inventario. Este hito debe dejar listo un conjunto inicial de reportes claros, útiles y fáciles de consultar, que permitan al negocio monitorear su desempeño diario, semanal o mensual y tomar decisiones mejor fundamentadas.

La meta principal de este hito es transformar los datos acumulados en información útil. Hasta este punto, el sistema ya registra productos, clientes, caja, ventas y gastos; ahora se busca que esa información pueda visualizarse de forma ordenada mediante reportes filtrables, comprensibles y con una presentación visual profesional.

Este módulo debe enfocarse en reportes esenciales para la operación del negocio, manteniendo una experiencia visual limpia, moderna y orientada a la lectura rápida de indicadores clave.

---

## Tareas del hito

### 1. Definición del alcance de reportes básicos
- Determinar el conjunto inicial de reportes que formarán parte del MVP.
- Priorizar reportes operativos y administrativos de mayor uso.
- Definir el alcance mínimo de filtros por fecha, usuario, método de pago, categoría o estado, según corresponda.
- Establecer una estructura uniforme para todos los reportes.

### 2. Reporte de ventas por periodo
- Construir un reporte que muestre ventas por rango de fechas.
- Mostrar totales de ventas, número de transacciones, promedio por venta y otros indicadores básicos si aplica.
- Permitir filtros por fecha y, opcionalmente, por usuario o caja.
- Preparar una vista clara y fácil de interpretar.

### 3. Reporte de ventas por método de pago
- Construir un reporte que agrupe ventas por método o métodos de pago registrados.
- Mostrar montos por cada forma de pago.
- Permitir filtros por fecha y otros criterios relevantes.
- Preparar la base para conciliación operativa y revisión administrativa.

### 4. Reporte de caja
- Construir un reporte que permita consultar aperturas, cierres, montos esperados, montos contados y diferencias de caja.
- Permitir filtros por fecha, usuario o sesión de caja.
- Facilitar la revisión de operaciones de caja y cortes históricos.

### 5. Reporte de gastos
- Construir un reporte que muestre egresos por periodo.
- Permitir filtros por fecha, categoría, proveedor o método de pago si aplica.
- Mostrar total de gastos y desglose relevante.
- Facilitar la revisión administrativa de egresos.

### 6. Reporte de utilidad básica
- Construir un reporte que combine ventas y gastos para mostrar una utilidad operativa básica.
- Definir claramente el criterio de cálculo dentro del alcance aprobado.
- Asegurar que el resultado se explique visualmente de manera entendible.
- Preparar este reporte como una primera aproximación gerencial, sin confundirlo con contabilidad formal.

### 7. Reporte de inventario actual
- Construir un reporte que muestre el estado actual del inventario registrado.
- Mostrar productos, stock actual, categoría y otros datos relevantes.
- Permitir filtros por categoría, estado o disponibilidad.
- Facilitar la consulta rápida de existencias.

### 8. Diseño de filtros y consultas
- Implementar filtros reutilizables para los distintos reportes.
- Validar rangos de fechas y combinaciones permitidas.
- Asegurar consultas eficientes para no degradar el rendimiento.
- Mantener consistencia en la forma de filtrar y presentar resultados.

### 9. Exportación básica
- Definir qué reportes podrán exportarse en esta etapa.
- Preparar exportación básica a PDF o Excel, según alcance aprobado.
- Asegurar que la exportación refleje correctamente los filtros aplicados.
- Mantener formatos claros y legibles para uso administrativo.

### 10. Definición visual del módulo
- Diseñar vistas atractivas, modernas y centradas en la lectura de información.
- Utilizar tarjetas, tablas, resúmenes y elementos visuales claros.
- Preparar jerarquía visual para indicadores importantes.
- Mantener consistencia visual con el resto del sistema.
- Asegurar responsividad y lectura cómoda en distintos tamaños de pantalla.

### 11. Integración con módulos previos
- Consumir correctamente datos de ventas, pagos, caja, gastos y productos.
- Mantener coherencia con los datos registrados en módulos anteriores.
- Preparar la información para futuras capas de reportería avanzada.
- Garantizar que la lógica de consulta no altere datos operativos.

### 12. Validaciones funcionales del módulo
- Verificar exactitud de los totales mostrados en los reportes.
- Verificar filtros por fechas y otros criterios.
- Verificar consistencia entre reportes y datos operativos.
- Verificar exportaciones.
- Confirmar que el módulo entregue información útil para operación y supervisión.

---

## Requerimientos técnicos

### Tecnologías base
- **PHP** para lógica del módulo y renderizado de vistas.
- **MySQL** para consultas agregadas y persistencia operativa.
- **HTML, CSS, JavaScript y Bootstrap 5** para interfaz.
- **PDO** para acceso a datos.
- **Bootstrap Icons** o **Font Awesome** para reforzar la interfaz.
- Librerías de exportación a **PDF** y/o **Excel** según la tecnología elegida en el proyecto.

### Estructura técnica del módulo
- Separación entre controlador, servicio de reportes, acceso a datos y vistas.
- Reutilización del layout y componentes definidos en el Hito 1.
- Lógica de consultas desacoplada de los módulos transaccionales.
- Preparación para ampliar el sistema de reportes sin rediseñar el módulo completo.

### Base de datos y consultas
- Uso de información proveniente de `sales`, `sale_items`, `sale_payments`, `cash_sessions`, `cash_movements`, `expenses`, `products` e `inventory_movements`, según corresponda.
- Uso de índices que favorezcan filtros por fecha, usuario, categoría, método de pago y estado.
- Consultas agregadas eficientes.
- Uso de InnoDB y `utf8mb4`.
- Preparación para crecimiento del volumen de datos.

### Reglas técnicas recomendadas
- Los reportes deben consultar información persistida y consistente.
- Las consultas deben optimizarse para evitar tiempos de respuesta altos.
- Deben emplearse consultas preparadas y validación de filtros de entrada.
- Las exportaciones deben respetar exactamente los filtros aplicados.
- El módulo debe dejar una base preparada para reportería más avanzada en etapas posteriores.

### Requerimientos visuales
- Los reportes deben ser claros y fáciles de interpretar.
- Los indicadores clave deben destacarse visualmente.
- Los filtros deben ser simples y consistentes.
- Las tablas deben ser legibles y ordenadas.
- El módulo debe ser responsivo y mantener coherencia visual con el resto del sistema.

### Preparación para módulos futuros
Este módulo debe quedar listo para ser utilizado por:
- Supervisión gerencial
- Auditoría operativa
- Cancelaciones y devoluciones
- Reporterías avanzadas
- Funciones futuras como dashboards ejecutivos o análisis comparativos

---

## Requerimientos funcionales

### Funcionalidades mínimas esperadas
- El usuario autorizado debe poder acceder al módulo de reportes.
- Debe poder consultar ventas por periodo.
- Debe poder consultar ventas por método de pago.
- Debe poder consultar información de caja.
- Debe poder consultar gastos por periodo.
- Debe poder consultar utilidad básica.
- Debe poder consultar inventario actual.
- Debe poder aplicar filtros relevantes a cada reporte.
- Debe poder exportar ciertos reportes si esa función se aprueba en el alcance.

### Reportes funcionales sugeridos
El módulo idealmente debe contemplar:
- reporte de ventas por fecha
- reporte de ventas por método de pago
- reporte de caja
- reporte de gastos
- reporte de utilidad básica
- reporte de inventario actual

### Comportamientos esperados
- Los reportes deben reflejar correctamente la información operativa registrada.
- Los filtros deben modificar los resultados de forma confiable.
- La utilidad mostrada debe explicarse como utilidad operativa básica dentro del alcance del sistema.
- Las exportaciones, si existen, deben respetar exactamente la información visible o filtrada.
- El módulo debe servir como herramienta real de consulta para el negocio.

---

## Definición del hito

El **Hito 8** se considerará terminado cuando el sistema cuente con un módulo de reportes básicos funcional, visualmente consistente y técnicamente sólido, capaz de mostrar información clave del negocio de forma clara y útil.

### Criterios de cumplimiento
- Existe reporte de ventas por periodo.
- Existe reporte de ventas por método de pago.
- Existe reporte de caja.
- Existe reporte de gastos.
- Existe reporte de utilidad básica.
- Existe reporte de inventario actual.
- Existen filtros funcionales para los reportes definidos.
- Existe exportación básica si fue aprobada en el alcance.
- El módulo mantiene consistencia visual con el sistema.
- El flujo queda listo para integrarse al **Hito 9: Cancelaciones y devoluciones** y a futuras ampliaciones de reportería.

### Resultado del negocio
Al terminar este hito, el negocio contará con información consolidada para revisar su operación, controlar ventas, caja, gastos e inventario, y tomar decisiones con mejor respaldo dentro del sistema.

---

## Resumen ejecutivo del hito

El Hito 8 consiste en desarrollar el módulo de reportes básicos del POS SaaS. Su meta es transformar los datos operativos en información útil mediante reportes claros, filtrables y visualmente comprensibles sobre ventas, caja, gastos, utilidad e inventario. Este hito marca el paso de la captura operativa a la supervisión y análisis básico del negocio.

