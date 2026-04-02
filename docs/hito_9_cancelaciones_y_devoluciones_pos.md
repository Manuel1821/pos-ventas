# Hito 9: Módulo de cancelaciones y devoluciones del POS SaaS

## Objetivo del hito

Construir el módulo de cancelaciones y devoluciones del sistema POS SaaS para permitir la corrección controlada de ventas ya registradas, manteniendo la integridad de la información histórica, el impacto correcto en inventario, la trazabilidad sobre caja y la auditoría operativa del sistema. Este hito debe dejar listo el flujo administrativo y operativo para cancelar ventas o registrar devoluciones de manera formal, clara y segura.

La meta principal de este hito es que el sistema pueda manejar incidencias posteriores a la venta sin perder consistencia en los datos. Una venta confirmada no puede eliminarse simplemente; cualquier corrección debe quedar registrada con motivo, usuario responsable, fecha, impacto relacionado y evidencia suficiente para consulta futura.

Este módulo es especialmente sensible porque afecta directamente la validez de la información comercial y financiera del sistema. Por ello, además de una experiencia visual clara y bien guiada, debe contemplar reglas estrictas, trazabilidad completa y comportamiento transaccional cuando exista impacto en inventario, caja o pagos.

---

## Tareas del hito

### 1. Definición del alcance de cancelaciones y devoluciones
- Establecer claramente la diferencia entre cancelación y devolución dentro del sistema.
- Definir si la cancelación aplicará solo a ventas recientes o bajo ciertas condiciones.
- Definir si la devolución podrá ser total, parcial o ambas.
- Determinar si habrá devoluciones con reembolso, con saldo a favor o únicamente con retorno a inventario, según el alcance aprobado.
- Definir restricciones operativas por rol, tiempo transcurrido o estado de la venta.

### 2. Revisión y ajuste del modelo de datos
- Validar que la estructura actual de ventas soporte cambios de estado sin pérdida de historial.
- Preparar o ajustar tablas para registrar cancelaciones, devoluciones, motivos, usuario responsable, fechas y observaciones.
- Preparar el modelo para guardar devoluciones parciales cuando el alcance lo requiera.
- Confirmar relaciones con `sales`, `sale_items`, `inventory_movements`, `cash_movements` y auditoría.
- Asegurar índices y relaciones necesarios para consultas históricas.

### 3. Flujo de cancelación de venta
- Diseñar el proceso para cancelar una venta previamente registrada.
- Solicitar motivo de cancelación.
- Registrar usuario responsable y fecha de la operación.
- Cambiar el estado de la venta a cancelada o equivalente.
- Asegurar que la venta original permanezca en historial.
- Definir el comportamiento del sistema respecto a inventario, pagos y caja cuando una venta se cancela.

### 4. Flujo de devolución de venta
- Diseñar el proceso para registrar una devolución.
- Permitir seleccionar venta origen.
- Permitir seleccionar productos devueltos y cantidades cuando aplique devolución parcial.
- Solicitar motivo de devolución.
- Registrar usuario responsable, fecha y observaciones.
- Determinar y registrar el impacto operativo de la devolución.

### 5. Impacto en inventario
- Definir reglas para el retorno automático de productos al inventario.
- Registrar movimientos de inventario relacionados con cancelación o devolución.
- Evitar inconsistencias de stock por devoluciones duplicadas o cantidades inválidas.
- Mantener trazabilidad del movimiento generado.

### 6. Impacto en caja y pagos
- Definir si la cancelación o devolución impacta caja de manera inmediata.
- Registrar reembolsos o ajustes de caja cuando aplique.
- Relacionar dichos movimientos con la sesión de caja correspondiente o con una trazabilidad equivalente.
- Mantener claridad sobre la diferencia entre revertir una venta y devolver dinero.

### 7. Auditoría y trazabilidad
- Registrar toda cancelación o devolución con usuario, fecha, motivo y datos operativos relevantes.
- Mantener historial completo sin borrar la venta original.
- Preparar el sistema para que estas operaciones puedan consultarse después desde ventas, reportes o auditoría.
- Asegurar evidencia suficiente para supervisión administrativa.

### 8. Reglas operativas del módulo
- Definir qué perfiles pueden cancelar o devolver ventas.
- Definir límites por tiempo o estado.
- Establecer si una venta ya cancelada puede volver a procesarse.
- Definir cómo se mostrarán las ventas canceladas o con devoluciones en el historial.
- Establecer reglas para impedir devoluciones por encima de la cantidad vendida.

### 9. Integración con módulos previos
- Integrar el módulo con historial de ventas.
- Integrar el impacto con inventario.
- Integrar el impacto con caja y pagos.
- Reflejar adecuadamente el nuevo estado de las ventas en reportes.
- Mantener consistencia con la información ya existente en el sistema.

### 10. Definición visual del módulo
- Diseñar flujos claros, guiados y seguros para operaciones sensibles.
- Utilizar confirmaciones visuales antes de ejecutar cambios irreversibles.
- Mostrar advertencias, estados y resultados de manera comprensible.
- Mantener consistencia con la línea visual general del sistema.
- Priorizar claridad sobre velocidad en operaciones críticas.

### 11. Validaciones funcionales del módulo
- Verificar cancelación correcta de venta.
- Verificar devolución total o parcial según el alcance.
- Verificar retorno correcto al inventario.
- Verificar impacto correcto en caja o pagos cuando aplique.
- Verificar auditoría y trazabilidad.
- Confirmar que el historial de ventas mantenga consistencia después de la operación.

---

## Requerimientos técnicos

### Tecnologías base
- **PHP** para lógica del módulo y renderizado de vistas.
- **MySQL** para persistencia de estados, movimientos y auditoría.
- **HTML, CSS, JavaScript y Bootstrap 5** para interfaz.
- **PDO** para acceso a datos.
- **Bootstrap Icons** o **Font Awesome** para reforzar la interfaz.

### Estructura técnica del módulo
- Separación entre controlador, servicio, acceso a datos y vistas.
- Reutilización del layout y componentes definidos en el Hito 1.
- Lógica de cancelación y devolución centralizada en servicios para evitar inconsistencias.
- Integración estrecha con ventas, inventario, caja y reportes.
- Preparación de operaciones sensibles para ejecutarse con transacciones.

### Base de datos y persistencia
- Uso de `sales`, `sale_items`, `sale_payments`, `inventory_movements`, `cash_movements` y estructuras adicionales para cancelaciones o devoluciones según el diseño final.
- Relación con `users` para identificar responsables.
- Uso de estados de venta claros y persistentes.
- Registro histórico sin eliminación física de la venta original.
- Uso de InnoDB y `utf8mb4`.
- Índices adecuados para consultas históricas y administrativas.

### Reglas técnicas recomendadas
- Toda cancelación o devolución debe ejecutarse de forma transaccional cuando impacte múltiples tablas.
- No debe borrarse la venta original.
- Deben existir validaciones para impedir devoluciones repetidas o por cantidades inválidas.
- Deben utilizarse consultas preparadas y validación estricta de entradas.
- El sistema debe guardar motivos, usuario responsable y fecha de la operación.
- El historial y los reportes deben reflejar correctamente el nuevo estado sin perder contexto original.

### Requerimientos visuales
- Las operaciones deben presentarse con advertencias claras.
- Deben existir confirmaciones explícitas antes de ejecutar acciones críticas.
- Los estados de venta cancelada o devuelta deben ser visualmente distinguibles.
- Los formularios deben ser claros y guiados.
- El módulo debe ser consistente con los hitos anteriores y mantener una experiencia profesional.

### Preparación para módulos futuros
Este módulo debe quedar listo para ser utilizado por:
- Reportes avanzados
- Auditoría operativa
- Conciliaciones administrativas
- Funciones futuras relacionadas con crédito, apartados o política de devoluciones ampliada

---

## Requerimientos funcionales

### Funcionalidades mínimas esperadas
- El usuario autorizado debe poder cancelar ventas según las reglas definidas.
- Debe poder registrar motivo de cancelación.
- Debe poder registrar devoluciones según el alcance aprobado.
- Debe poder seleccionar venta origen para devolución.
- Debe poder indicar productos y cantidades en devoluciones parciales, si aplica.
- El sistema debe registrar el impacto en inventario.
- El sistema debe registrar el impacto en caja o pagos cuando corresponda.
- El sistema debe dejar trazabilidad completa de cada operación.
- El historial de ventas debe reflejar el nuevo estado de forma clara.

### Datos funcionales sugeridos para la operación
El módulo idealmente debe contemplar:
- venta origen
- tipo de operación: cancelación o devolución
- motivo
- usuario responsable
- fecha y hora
- productos afectados
- cantidades afectadas
- impacto en inventario
- impacto en caja o pagos
- observaciones
- estado final de la venta o de la devolución

### Comportamientos esperados
- Una venta cancelada no debe desaparecer del historial.
- Una devolución no debe exceder la cantidad originalmente vendida.
- El inventario debe ajustarse correctamente cuando corresponda.
- Las operaciones sensibles deben quedar auditadas.
- El sistema debe impedir acciones inconsistentes o repetidas.
- El módulo debe servir como base confiable para control operativo y aclaraciones administrativas.

---

## Definición del hito

El **Hito 9** se considerará terminado cuando el sistema cuente con un módulo de cancelaciones y devoluciones funcional, visualmente consistente y técnicamente sólido, capaz de corregir ventas ya registradas sin perder trazabilidad ni integridad en los datos.

### Criterios de cumplimiento
- Existe flujo de cancelación de ventas.
- Existe flujo de devolución total o parcial según el alcance definido.
- Existe registro de motivo, usuario y fecha.
- Existe impacto correcto en inventario.
- Existe impacto correcto en caja o pagos cuando aplique.
- Existe trazabilidad y auditoría de la operación.
- El historial de ventas refleja correctamente el nuevo estado.
- El módulo mantiene consistencia visual con el sistema.
- El flujo queda listo para integrarse al **Hito 10: Funciones futuras y expansión** y a reportes más avanzados.

### Resultado del negocio
Al terminar este hito, el negocio contará con un mecanismo formal para corregir errores operativos y gestionar devoluciones sin perder control administrativo, financiero ni de inventario dentro del sistema.

---

## Resumen ejecutivo del hito

El Hito 9 consiste en desarrollar el módulo de cancelaciones y devoluciones del POS SaaS. Su meta es permitir corregir ventas ya registradas mediante procesos formales, trazables y seguros, con impacto correcto en inventario, caja y auditoría. Este hito fortalece la confiabilidad operativa del sistema y evita que las incidencias posteriores a la venta se manejen fuera de control o sin registro adecuado.

