# Hito 4: Módulo de caja del POS SaaS

## Objetivo del hito

Construir el módulo de caja del sistema POS SaaS para controlar la operación diaria del efectivo y de los movimientos relacionados con la apertura, uso y cierre de caja. Este hito debe dejar listo el flujo operativo que permita iniciar una jornada de caja, registrar movimientos durante el día, consultar el estado actual de la caja y realizar cortes o cierres con trazabilidad suficiente.

La meta principal de este hito es que el sistema tenga control sobre el contexto financiero operativo en el que se realizarán las ventas. El módulo de caja será la base para validar si una venta puede cobrarse, registrar ingresos y retiros manuales, calcular montos esperados al cierre y generar historial de cortes para consulta posterior.

Además de resolver la parte funcional, este hito debe mantener una experiencia visual clara, moderna y orientada a la operación rápida, ya que la caja suele ser un módulo de uso frecuente y sensible dentro del negocio.

---

## Tareas del hito

### 1. Revisión y ajuste del modelo de datos de caja
- Validar que las tablas creadas en el Hito 1 para caja cubran las necesidades operativas del sistema.
- Revisar la estructura de `cash_sessions`, `cash_movements` y `cash_audits` o las tablas equivalentes definidas.
- Confirmar campos como usuario, fecha de apertura, monto inicial, fecha de cierre, monto esperado, monto contado, diferencia, estado y observaciones.
- Definir índices y relaciones con usuarios, ventas, pagos y gastos, cuando aplique.
- Asegurar que la estructura soporte trazabilidad e historial.

### 2. Flujo de apertura de caja
- Crear la interfaz para abrir una nueva sesión de caja.
- Solicitar monto inicial o fondo de caja.
- Relacionar la apertura con el usuario responsable.
- Validar que no exista otra caja abierta incompatible con la operación definida.
- Registrar fecha y hora de apertura.

### 3. Vista de caja actual
- Construir una pantalla para consultar el estado actual de la caja abierta.
- Mostrar monto inicial, ingresos, retiros, ventas cobradas, total esperado y estado actual.
- Permitir una visualización rápida y clara para el operador o administrador.
- Mostrar resumen de movimientos del día o de la sesión actual.

### 4. Registro de ingresos y retiros manuales
- Crear el flujo para registrar entradas manuales de efectivo o equivalentes.
- Crear el flujo para registrar retiros de caja.
- Solicitar motivo, monto, usuario y observaciones.
- Asegurar que todo movimiento quede ligado a una sesión de caja activa.
- Mantener historial completo de estos movimientos.

### 5. Cierre o corte de caja
- Construir el flujo de cierre de sesión de caja.
- Calcular el monto esperado con base en ventas, ingresos, retiros y otros movimientos relacionados.
- Permitir capturar monto contado o monto real al cierre.
- Calcular diferencia sobrante o faltante.
- Permitir registrar observaciones del corte.
- Cambiar el estado de la sesión de caja a cerrada.
- Dejar evidencia del usuario responsable del cierre.

### 6. Historial de cortes y sesiones de caja
- Implementar una vista histórica de aperturas y cierres de caja.
- Permitir filtros por fecha, usuario, estado o rango.
- Consultar detalles de una sesión específica.
- Preparar base para futuras exportaciones o reportes.

### 7. Reglas operativas del módulo
- Definir si las ventas solo podrán realizarse con una caja abierta.
- Definir restricciones para abrir más de una caja según la lógica del negocio.
- Establecer qué tipos de movimientos manuales estarán permitidos.
- Determinar si los gastos impactan directamente a caja en esta etapa o solo quedan registrados para reportes.
- Definir el comportamiento del sistema ante diferencias al cierre.

### 8. Integración con el flujo de ventas
- Preparar el módulo para integrarse con el POS.
- Asegurar que las ventas cobradas impacten la sesión de caja correspondiente.
- Permitir que el sistema calcule correctamente el total esperado de caja.
- Garantizar que los movimientos queden trazables y relacionados con la operación real.

### 9. Definición visual del módulo
- Diseñar vistas atractivas, modernas y operativamente claras.
- Crear una pantalla de caja actual con resumen visual entendible.
- Diseñar formularios rápidos para apertura, retiro, ingreso y cierre.
- Preparar tablas limpias para historial de cortes y movimientos.
- Mantener consistencia visual con los módulos anteriores.

### 10. Validaciones funcionales del módulo
- Verificar apertura correcta de caja.
- Verificar registro correcto de ingresos y retiros.
- Verificar cálculo esperado de caja.
- Verificar cierre correcto con diferencia calculada.
- Verificar historial de sesiones.
- Confirmar compatibilidad del módulo con el futuro flujo de ventas.

---

## Requerimientos técnicos

### Tecnologías base
- **PHP** para lógica del módulo y renderizado de vistas.
- **MySQL** para persistencia de sesiones y movimientos de caja.
- **HTML, CSS, JavaScript y Bootstrap 5** para interfaz.
- **PDO** para acceso a datos.
- **Bootstrap Icons** o **Font Awesome** para reforzar la experiencia visual.

### Estructura técnica del módulo
- Separación entre controlador, servicio, acceso a datos y vistas.
- Reutilización del layout y componentes base definidos en el Hito 1.
- Lógica del módulo encapsulada para facilitar integración con ventas y reportes.
- Validaciones de negocio centralizadas para evitar inconsistencias operativas.

### Base de datos y persistencia
- Tabla `cash_sessions` para controlar aperturas y cierres.
- Tabla `cash_movements` para registrar ingresos y retiros.
- Tabla `cash_audits` o equivalente para mantener trazabilidad adicional si se requiere.
- Relación con `users` para identificar responsables.
- Preparación para relación con `sales`, `sale_payments` y eventualmente `expenses`.
- Uso de InnoDB, `utf8mb4`, claves foráneas e índices adecuados.

### Reglas técnicas recomendadas
- Una sesión de caja debe tener estado claramente definido: abierta, cerrada o equivalente.
- No debe poder registrarse un movimiento manual sin una sesión activa.
- El sistema debe evitar aperturas simultáneas no permitidas según la lógica definida.
- El cálculo del monto esperado debe ser consistente y trazable.
- Las operaciones sensibles deben quedar preparadas para uso de transacciones cuando se integren con ventas.
- Todas las entradas deben validarse y manejarse con consultas preparadas.

### Requerimientos visuales
- La vista de caja actual debe ser clara y de lectura rápida.
- Los datos más importantes deben mostrarse en tarjetas o resúmenes visuales.
- Los formularios de apertura, ingreso, retiro y cierre deben ser simples y directos.
- El historial debe ser legible y filtrable.
- La experiencia debe ser responsiva y consistente con el resto del sistema.

### Preparación para módulos futuros
Este módulo debe quedar listo para ser utilizado por:
- POS / Nueva venta
- Ventas
- Reportes
- Gastos
- Cancelaciones y devoluciones

---

## Requerimientos funcionales

### Funcionalidades mínimas esperadas
- El usuario autorizado debe poder abrir una caja.
- Debe poder registrar el monto inicial de apertura.
- Debe poder consultar la caja actual.
- Debe poder registrar ingresos manuales.
- Debe poder registrar retiros manuales.
- Debe poder cerrar o cortar caja.
- Debe poder capturar monto contado al cierre.
- El sistema debe calcular monto esperado y diferencia.
- Debe existir historial de sesiones de caja.
- El sistema debe poder ligar la caja al usuario responsable.

### Datos funcionales sugeridos para la caja
El módulo idealmente debe contemplar:
- usuario responsable
- fecha y hora de apertura
- monto inicial
- estado de la sesión
- fecha y hora de cierre
- monto esperado
- monto contado
- diferencia
- observaciones
- movimientos de ingreso
- movimientos de retiro

### Comportamientos esperados
- No debe existir cobro en POS sin una caja abierta, si esa regla queda aprobada por el negocio.
- Un movimiento manual debe quedar ligado a una caja activa.
- El cierre de caja debe dejar trazabilidad suficiente.
- El historial debe permitir revisar sesiones pasadas sin alterar su consistencia.
- El módulo debe servir como base confiable para el control operativo del negocio.

---

## Definición del hito

El **Hito 4** se considerará terminado cuando el sistema cuente con un módulo de caja funcional, claro, visualmente consistente y listo para integrarse al flujo de ventas del POS.

### Criterios de cumplimiento
- Existe apertura de caja con validaciones.
- Existe vista de caja actual.
- Existe registro de ingresos y retiros manuales.
- Existe cierre o corte de caja con cálculo de monto esperado, contado y diferencia.
- Existe historial de sesiones de caja.
- El módulo persiste correctamente la información en la base de datos.
- El módulo mantiene consistencia visual con el sistema.
- El flujo queda listo para integrarse al **Hito 5: Módulo POS / Nueva venta**.

### Resultado del negocio
Al terminar este hito, el negocio contará con control básico y trazable de caja, lo que permitirá operar ventas con mayor orden, registrar movimientos manuales y llevar control sobre aperturas, cierres y diferencias operativas.

---

## Resumen ejecutivo del hito

El Hito 4 consiste en desarrollar el módulo de caja del POS SaaS. Su meta es dejar lista la operación básica de apertura, consulta, movimientos manuales, cierre e historial de caja, con una interfaz clara, moderna y funcional. Este módulo será indispensable para soportar de manera correcta el cobro de ventas, el control operativo diario y la trazabilidad financiera básica del sistema.

