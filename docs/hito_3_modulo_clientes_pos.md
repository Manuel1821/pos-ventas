# Hito 3: Módulo de clientes del POS SaaS

## Objetivo del hito

Construir el módulo de clientes del sistema POS SaaS para permitir la administración, consulta y selección de clientes dentro del flujo operativo del negocio. Este hito debe dejar listo un catálogo de clientes funcional, claro y fácil de usar, que sirva tanto para el área administrativa como para el proceso de venta en el POS.

La meta principal de este hito es que el sistema pueda relacionar ventas con clientes específicos cuando el negocio lo requiera, sin perder agilidad en mostrador. Por ello, el módulo debe contemplar tanto la administración de clientes registrados como la posibilidad de trabajar con un cliente genérico o “público en general” para ventas rápidas.

Además de cubrir la parte funcional, este módulo debe mantener la línea visual atractiva, moderna y consistente definida en los hitos anteriores, priorizando una experiencia de captura rápida, búsqueda eficiente y operación sencilla.

---

## Tareas del hito

### 1. Revisión y ajuste del modelo de datos de clientes
- Validar que la tabla de clientes creada en el Hito 1 cubra las necesidades del negocio.
- Ajustar campos base como nombre, teléfono, correo, dirección, RFC y observaciones, según el alcance definido.
- Definir qué campos serán obligatorios y cuáles opcionales.
- Preparar índices en campos de búsqueda frecuentes, como nombre, teléfono o correo.
- Verificar la relación del cliente con ventas, pagos y reportes.

### 2. Construcción del listado de clientes
- Implementar la vista principal del catálogo de clientes.
- Mostrar información clave como nombre, teléfono, correo, RFC, estado y fecha de registro, si aplica.
- Incorporar búsqueda rápida y filtros.
- Definir acciones visibles por cliente: ver, editar, activar, desactivar o eliminar lógicamente.
- Preparar una tabla clara, ordenada y visualmente agradable.

### 3. Desarrollo del formulario de alta de cliente
- Crear el formulario de captura de clientes.
- Incluir validaciones del lado servidor y del lado cliente cuando aplique.
- Permitir registrar información de contacto.
- Contemplar datos fiscales o administrativos si serán necesarios después.
- Preparar una experiencia de captura simple, clara y rápida.

### 4. Desarrollo del formulario de edición de cliente
- Permitir modificar la información principal del cliente.
- Mantener integridad de los datos existentes.
- Validar cambios antes de guardar.
- Conservar consistencia con ventas ya relacionadas, evitando afectar historiales previos.

### 5. Cliente genérico para ventas rápidas
- Crear y definir el comportamiento del cliente por defecto o “público en general”.
- Asegurar que el POS pueda usarlo de forma inmediata para ventas rápidas.
- Definir reglas para evitar duplicidad o confusión con otros registros.
- Garantizar que este cliente esté siempre disponible cuando se requiera.

### 6. Manejo de estatus del cliente
- Definir si el cliente se podrá eliminar físicamente o solo desactivar.
- Implementar cambio de estado activo/inactivo.
- Asegurar que clientes con historial de ventas no se eliminen de manera que se comprometa la integridad de la información.

### 7. Integración con el flujo de ventas
- Preparar el módulo para ser consumido por el POS.
- Permitir búsqueda rápida de clientes desde el proceso de venta.
- Asegurar que el sistema pueda asociar una venta a un cliente registrado o a cliente genérico.
- Verificar compatibilidad con reportes y consultas futuras.

### 8. Definición visual del módulo
- Diseñar vistas atractivas, modernas y operativamente claras.
- Mantener la línea visual establecida en el Hito 1.
- Crear formularios cómodos de usar y tablas legibles.
- Preparar estados visuales para errores, inactivos, vacíos y resultados de búsqueda.
- Asegurar consistencia con el módulo de productos y la interfaz general del sistema.

### 9. Validaciones funcionales del módulo
- Verificar altas correctas.
- Verificar edición correcta.
- Verificar búsquedas y filtros.
- Verificar uso correcto del cliente genérico.
- Confirmar que el catálogo quede listo para integrarse al POS.
- Confirmar que el historial de ventas no se vea afectado por cambios administrativos en clientes.

---

## Requerimientos técnicos

### Tecnologías base
- **PHP** para lógica del módulo y renderizado de vistas.
- **MySQL** para persistencia de clientes.
- **HTML, CSS, JavaScript y Bootstrap 5** para interfaz.
- **PDO** para acceso a datos.
- **Bootstrap Icons** o **Font Awesome** para mejorar la experiencia visual.

### Estructura técnica del módulo
- Separación entre controlador, servicio, acceso a datos y vistas.
- Reutilización del layout y componentes base definidos en el Hito 1.
- Validaciones reutilizables y centralizadas.
- Lógica preparada para integrarse con ventas, reportes y futuras funciones administrativas.

### Base de datos y persistencia
- Tabla `customers` correctamente estructurada.
- Índices recomendados para nombre, teléfono, correo y RFC si aplica.
- Relación clara entre clientes y ventas.
- Soporte para estado activo/inactivo.
- Uso de InnoDB y `utf8mb4`.
- Restricciones y validaciones de integridad según las reglas del negocio.

### Reglas técnicas recomendadas
- Nombre del cliente obligatorio si se trata de un registro formal.
- Teléfono y correo opcionales o requeridos según decisión del negocio.
- RFC opcional, salvo que se quiera preparar la base para procesos fiscales futuros.
- Evitar duplicidad excesiva mediante validaciones razonables.
- Consultas preparadas y sanitización de entradas.
- Manejo consistente del cliente genérico.

### Requerimientos visuales
- El módulo debe ser fácil de entender y rápido de operar.
- Las tablas deben mostrar datos importantes sin saturar la pantalla.
- Los formularios deben ser simples, bien ordenados y visualmente agradables.
- Deben existir estados visuales para activo/inactivo, errores de validación y búsquedas sin resultados.
- La experiencia debe ser responsiva y consistente con el resto del sistema.

### Preparación para módulos futuros
Este módulo debe quedar listo para ser utilizado por:
- POS / Nueva venta
- Ventas
- Reportes
- Cancelaciones y devoluciones
- Funciones futuras relacionadas con crédito, facturación o historial de cliente

---

## Requerimientos funcionales

### Funcionalidades mínimas esperadas
- El usuario administrador debe poder ver un listado de clientes.
- Debe poder registrar nuevos clientes.
- Debe poder editar clientes existentes.
- Debe poder buscar clientes por nombre, teléfono o correo.
- Debe poder activar o desactivar clientes.
- Debe existir un cliente genérico disponible para ventas rápidas.
- El sistema debe poder asociar ventas a clientes registrados o al cliente genérico.
- El catálogo debe quedar preparado para consultas administrativas y operativas.

### Campos funcionales sugeridos para clientes
La lista exacta puede ajustarse, pero idealmente este módulo debe contemplar:
- nombre
- teléfono
- correo electrónico
- dirección
- RFC
- observaciones
- estatus del cliente
- fecha de registro, si aplica

### Comportamientos esperados
- Un cliente con historial de ventas no debe eliminarse de forma que rompa la integridad de los datos.
- El cliente genérico debe poder utilizarse sin fricción en el POS.
- Las búsquedas deben ser útiles y rápidas.
- El sistema debe evitar registros vacíos o inconsistentes.
- La edición de un cliente no debe afectar la información histórica de ventas previas.

---

## Definición del hito

El **Hito 3** se considerará terminado cuando el sistema cuente con un módulo de clientes funcional, claro, visualmente consistente y listo para integrarse al proceso de venta.

### Criterios de cumplimiento
- Existe un listado funcional de clientes.
- Existe alta de clientes con validaciones.
- Existe edición de clientes.
- Existen búsquedas y filtros funcionales.
- Existe manejo de estado activo/inactivo.
- Existe un cliente genérico configurado para ventas rápidas.
- Los clientes quedan correctamente persistidos en la base de datos.
- El módulo mantiene consistencia visual con el sistema.
- El catálogo queda listo para integrarse al **Hito 4: Módulo de caja** y al flujo posterior del POS.

### Resultado del negocio
Al terminar este hito, el negocio contará con una base organizada de clientes, lo que permitirá registrar ventas con mejor trazabilidad, consultar historiales y mantener una operación más ordenada sin perder rapidez en mostrador.

---

## Resumen ejecutivo del hito

El Hito 3 consiste en desarrollar el módulo de clientes del POS SaaS. Su meta es dejar lista la administración de clientes con una interfaz clara, atractiva y funcional, incluyendo alta, edición, búsqueda, control de estado y el uso de un cliente genérico para ventas rápidas. Este módulo permitirá conectar la operación comercial con la gestión de clientes dentro del sistema y servirá como base para el flujo de ventas y reportes posteriores.

