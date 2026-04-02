# Hito 1: Base técnica y base de datos mínima del POS SaaS

## Objetivo del hito

Establecer la base técnica del sistema POS SaaS en **PHP + MySQL**, dejando preparado el proyecto para desarrollar los módulos funcionales posteriores de forma ordenada, mantenible y escalable. Este hito busca construir la estructura inicial de la aplicación, definir las convenciones de desarrollo, preparar la conexión con la base de datos y crear el primer bloque de tablas esenciales del sistema.

La intención de este hito no es entregar todavía un POS completamente funcional, sino dejar listo el terreno técnico para que los siguientes hitos puedan implementarse con menor retrabajo, mejor separación de responsabilidades y mayor consistencia en el código.

---

## Tareas del hito

### 1. Definición de la estructura base del proyecto
- Crear la estructura principal de carpetas del proyecto.
- Separar responsabilidades entre controladores, servicios, modelos o repositorios, vistas, configuración y utilidades.
- Definir la ubicación de archivos públicos, assets, storage, logs y archivos temporales.
- Establecer una convención de nombres para archivos, clases, rutas y vistas.

### 2. Configuración inicial del entorno
- Crear archivos de configuración por ambiente.
- Definir variables de entorno para base de datos, URL base, zona horaria y parámetros globales.
- Preparar la configuración inicial de PHP para manejo de errores, sesiones y codificación.
- Definir la estrategia de arranque de la aplicación.

### 3. Conexión reutilizable a MySQL
- Implementar una capa central de conexión usando PDO.
- Configurar manejo de excepciones y errores de base de datos.
- Definir helpers o utilidades para consultas comunes.
- Asegurar compatibilidad con transacciones para módulos sensibles como ventas, caja e inventario.

### 4. Enrutamiento base de la aplicación
- Crear el router principal del sistema.
- Definir la forma en que se registrarán rutas públicas y privadas.
- Preparar soporte para parámetros de ruta.
- Definir la resolución entre ruta, controlador y método.

### 5. Layout base administrativo y línea visual del sistema
- Construir la plantilla base del panel.
- Definir encabezado, menú lateral, contenedor principal y pie de página.
- Preparar una estructura visual reutilizable para los módulos futuros.
- Integrar una base de estilos inicial con Bootstrap o el framework visual elegido.
- Diseñar una línea visual moderna, limpia y atractiva desde este primer hito.
- Definir componentes base reutilizables: tarjetas, tablas, formularios, botones, alertas, badges, modales y paneles de resumen.
- Preparar una identidad visual coherente en colores, espaciados, tipografía, iconografía y estados visuales.
- Dejar una base estética lista para que los siguientes módulos mantengan la misma experiencia visual.

### 6. Autenticación y control de acceso
- Implementar el login del sistema.
- Preparar el manejo de sesión de usuario.
- Definir el control de acceso a rutas privadas.
- Establecer una base para roles y permisos.
- Proteger el acceso al panel administrativo.

### 7. Sistema de migraciones y seeders
- Definir la estrategia para versionar la base de datos.
- Crear la estructura para migraciones en PHP.
- Implementar seeders iniciales para datos base.
- Permitir reconstruir el entorno desde cero de manera repetible.

### 8. Diseño e implementación de la base de datos mínima
- Definir las tablas necesarias para soportar el MVP inicial.
- Crear migraciones para dichas tablas.
- Establecer claves primarias, índices y relaciones.
- Preparar datos iniciales mínimos para pruebas y arranque.

### 9. Base mínima de módulos transversales
- Crear usuarios iniciales.
- Crear roles base del sistema.
- Crear tienda principal o entidad equivalente.
- Preparar datos semilla mínimos para categorías y configuraciones básicas.

### 10. Validaciones y pruebas técnicas iniciales
- Verificar que el sistema arranque correctamente.
- Confirmar conexión a base de datos.
- Probar login y protección de rutas.
- Probar ejecución de migraciones y seeders.
- Validar que la estructura sirva de soporte para los siguientes hitos.

---

## Requerimientos técnicos

### Tecnologías base
- **PHP** como tecnología principal del backend y renderizado de vistas.
- **MySQL** como motor de base de datos.
- **HTML, CSS, JavaScript y Bootstrap 5** para la interfaz administrativa.
- **Bootstrap Icons** o **Font Awesome** para iconografía.
- **PDO** para acceso a base de datos.

### Arquitectura técnica
- Estructura modular y organizada por responsabilidades.
- Separación entre vistas, lógica de negocio, acceso a datos y configuración.
- Posibilidad de crecimiento sin reestructurar completamente el sistema.
- Enfoque DRY para evitar duplicación de lógica.
- Construcción de vistas a partir de layouts, parciales y componentes reutilizables.

### Base de datos
- Uso de **InnoDB** para permitir integridad referencial y transacciones.
- Definición de claves primarias en todas las tablas.
- Índices en campos de búsqueda y relación.
- Uso de `utf8mb4` para compatibilidad completa con caracteres especiales.
- Preparación para relaciones entre productos, ventas, clientes, caja y movimientos de inventario.

### Seguridad básica
- Manejo de sesiones seguras.
- Validación y sanitización de entradas.
- Uso de consultas preparadas.
- Protección de rutas privadas.
- Contraseñas almacenadas con hash seguro.

### Mantenibilidad
- Configuración centralizada.
- Código reutilizable para conexión, validaciones, respuestas y utilidades comunes.
- Sistema de migraciones que permita actualizar la base de datos sin manipular tablas manualmente.
- Logs básicos de errores técnicos.

### Requerimientos visuales y de experiencia de usuario
- La interfaz debe transmitir una imagen moderna, profesional y fácil de usar.
- El panel debe ser visualmente atractivo, con énfasis en claridad, limpieza y rapidez operativa.
- Las vistas deben ser responsivas para escritorio, tablet y móvil.
- Los formularios deben ser claros, con buena jerarquía visual y validaciones comprensibles.
- Las tablas deben ser legibles, con acciones visibles y consistentes.
- Se deben establecer componentes visuales base reutilizables desde este hito.
- Se recomienda usar **Bootstrap 5** como base principal por su velocidad de implementación, buena documentación, responsividad nativa y facilidad de personalización.
- Como complemento, se puede crear una capa propia de estilos para darle identidad visual al sistema y evitar que se vea como una plantilla genérica.

### Preparación para módulos futuros
Este hito debe dejar lista la base técnica para soportar, en los siguientes hitos, los módulos de:
- productos
- clientes
- caja
- punto de venta
- ventas
- gastos
- reportes
- cancelaciones y devoluciones

---

## Requerimientos funcionales

Aunque este hito es principalmente técnico, sí debe entregar capacidades funcionales mínimas para que el sistema pueda comenzar a operar internamente como plataforma de desarrollo.

### Funcionalidades mínimas esperadas
- El sistema debe poder iniciar desde un punto de entrada único.
- Debe existir un login funcional.
- Debe poder iniciarse sesión con un usuario administrador.
- Las rutas privadas deben requerir autenticación.
- Debe existir un layout administrativo base visible tras iniciar sesión.
- Debe existir una propuesta visual atractiva y consistente para el panel.
- Deben existir componentes visuales reutilizables para formularios, tablas, botones, alertas y tarjetas.
- Deben poder ejecutarse migraciones para crear la base de datos inicial.
- Deben poder ejecutarse seeders para insertar datos mínimos.
- Debe existir al menos una tienda o entidad principal registrada.
- Debe existir al menos un usuario administrador creado por seeder.
- Debe quedar lista la estructura para que los siguientes módulos se monten sobre esta base.

### Tablas mínimas esperadas en este hito
Este hito debe dejar creadas, como mínimo, las tablas base que permitan soportar el resto del sistema. La lista exacta podrá ajustarse, pero idealmente debe contemplar:

- `shops`
- `users`
- `roles`
- `user_roles` o equivalente si se maneja relación separada
- `categories`
- `products`
- `customers`
- `sales`
- `sale_items`
- `sale_payments`
- `cash_sessions`
- `cash_movements`
- `cash_audits`
- `expense_categories`
- `expenses`
- `inventory_movements`

Estas tablas no necesariamente deben tener toda su complejidad final en este hito, pero sí deben quedar establecidas en su forma base.

---

## Definición del hito

El **Hito 1** se considerará terminado cuando el proyecto cuente con una base técnica estable, consistente y lista para soportar el desarrollo de los módulos funcionales posteriores.

### Criterios de cumplimiento
- Existe una estructura ordenada del proyecto en PHP.
- La aplicación arranca correctamente desde su punto de entrada.
- La conexión a MySQL funciona correctamente.
- Existe un sistema básico de rutas.
- Existe un login funcional con control de sesión.
- Las rutas privadas están protegidas.
- Existe un layout administrativo base.
- Existe una base visual atractiva, moderna y consistente para el sistema.
- Existen componentes visuales reutilizables listos para ser usados en los módulos siguientes.
- Las migraciones pueden ejecutarse correctamente.
- Los seeders pueden ejecutarse correctamente.
- La base de datos mínima queda creada con sus tablas esenciales.
- Existe al menos un usuario administrador y datos semilla mínimos.
- El sistema queda listo para iniciar el **Hito 2: Módulo de catálogo de productos**.

### Resultado del negocio
Aunque este hito aún no entrega valor directo al usuario final del POS en forma de ventas o reportes, sí entrega valor estratégico al proyecto, porque reduce el riesgo técnico, establece orden en el desarrollo y evita retrabajos graves en etapas posteriores.

---

## Resumen ejecutivo del hito

El Hito 1 consiste en construir los cimientos técnicos del POS SaaS. Su meta principal es dejar lista la arquitectura base, la autenticación, el sistema de rutas, la conexión a base de datos y la primera versión de la base de datos mínima. Una vez completado, el equipo podrá avanzar a los módulos funcionales con una base sólida, manteniendo consistencia técnica y velocidad de desarrollo.

