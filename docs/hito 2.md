# Hito 2: Módulo de catálogo de productos del POS SaaS

## Objetivo del hito

Construir el módulo de catálogo de productos del sistema POS SaaS para permitir la administración completa de los artículos que serán utilizados en ventas, inventario y reportes. Este hito debe dejar listo el flujo base para registrar, editar, consultar, organizar y visualizar productos dentro del sistema, asegurando una estructura clara tanto a nivel de datos como de interfaz.

La finalidad principal de este hito es que el sistema cuente con un catálogo confiable, ordenado y fácil de operar, que sirva como insumo directo para el punto de venta. Además, este módulo debe prepararse con una experiencia visual atractiva, rápida y entendible para el usuario administrativo.

---

## Tareas del hito

### 1. Revisión y ajuste del modelo de datos de productos
- Validar que la tabla de productos creada en el Hito 1 cubra las necesidades del catálogo.
- Ajustar campos base como nombre, SKU, código de barras, descripción, precio, costo, impuesto, stock, unidad y categoría.
- Definir reglas para campos obligatorios y opcionales.
- Asegurar índices de búsqueda para SKU, código de barras y nombre.
- Verificar relaciones con categorías, imágenes y movimientos de inventario.

### 2. Gestión de categorías de productos
- Crear el flujo de alta, edición, consulta y desactivación de categorías.
- Validar unicidad de nombre o slug si aplica.
- Relacionar categorías con productos.
- Preparar la interfaz de administración de categorías.

### 3. Construcción del listado de productos
- Implementar la vista principal del catálogo.
- Mostrar información clave del producto: nombre, SKU, código, categoría, precio, stock y estado.
- Agregar paginación, búsqueda y filtros.
- Definir acciones visibles por producto: ver, editar, desactivar o eliminar lógicamente.
- Preparar una tabla clara, rápida y visualmente atractiva.

### 4. Desarrollo del formulario de alta de producto
- Crear el formulario de captura de productos.
- Incorporar validaciones del lado servidor y del lado cliente cuando aplique.
- Permitir registrar imagen principal del producto.
- Permitir asignar categoría y unidad.
- Definir manejo de campos relacionados con precio, costo, impuesto y stock inicial.
- Preparar una experiencia de captura clara y usable.

### 5. Desarrollo del formulario de edición de producto
- Permitir modificar la información principal del producto.
- Permitir cambiar imagen, categoría, precio, costo y demás datos editables.
- Conservar historial básico del estado actual del producto cuando sea necesario.
- Validar integridad de la información antes de guardar cambios.

### 6. Manejo de estatus del producto
- Definir si el producto se podrá eliminar físicamente o solo desactivar.
- Implementar eliminación lógica o cambio de estado.
- Asegurar que productos desactivados no aparezcan en el POS, salvo que se indique lo contrario en administración.

### 7. Integración básica con inventario
- Registrar stock inicial desde el alta del producto o desde un flujo definido.
- Asegurar que el producto quede preparado para relacionarse con movimientos de inventario.
- Validar que el catálogo permita distinguir productos inventariables y no inventariables, si esa regla fue definida.

### 8. Definición visual del módulo
- Diseñar vistas atractivas, modernas y operativamente claras para el catálogo.
- Aplicar la línea visual definida en el Hito 1.
- Crear componentes reutilizables para tablas, formularios, badges de stock y acciones rápidas.
- Asegurar consistencia visual con el resto del sistema.

### 9. Validaciones funcionales del módulo
- Verificar altas correctas.
- Verificar edición correcta.
- Verificar búsqueda y filtros.
- Verificar relación correcta con categorías.
- Verificar carga y visualización de imagen.
- Confirmar que el catálogo queda listo para ser consumido por el POS.

---

## Requerimientos técnicos

### Tecnologías base
- **PHP** para lógica del módulo y renderizado de vistas.
- **MySQL** para persistencia del catálogo.
- **HTML, CSS, JavaScript y Bootstrap 5** para interfaz.
- **PDO** para acceso a datos.
- **Bootstrap Icons** o **Font Awesome** para reforzar la experiencia visual.

### Estructura técnica del módulo
- Separación entre controlador, servicio, acceso a datos y vistas.
- Reutilización de layout y componentes base definidos en el Hito 1.
- Validaciones centralizadas para evitar duplicidad.
- Posibilidad de reutilizar lógica del catálogo desde otros módulos como POS, reportes e inventario.

### Base de datos y persistencia
- Tabla `products` correctamente indexada.
- Relación con tabla `categories`.
- Preparación para relación con `inventory_movements`.
- Soporte para imagen principal del producto.
- Uso de claves foráneas, índices y constraints según el alcance definido.
- Uso de `utf8mb4` e InnoDB.

### Reglas técnicas recomendadas
- SKU único si el negocio así lo requiere.
- Código de barras único cuando se capture.
- Nombre del producto obligatorio.
- Precio y costo numéricos con precisión adecuada.
- Validación de stock inicial no negativo.
- Sanitización de entradas y consultas preparadas.
- Manejo seguro de archivos si se permite subir imágenes.

### Requerimientos visuales
- El listado debe ser limpio, moderno y fácil de operar.
- Los formularios deben ser claros, con jerarquía visual adecuada.
- Deben existir estados visuales para stock, activo/inactivo y errores de validación.
- La experiencia debe priorizar rapidez de captura y consulta.
- El módulo debe ser responsivo.

### Preparación para módulos futuros
Este módulo debe quedar listo para ser utilizado por:
- POS / Nueva venta
- Ventas
- Inventario
- Reportes
- Cancelaciones y devoluciones

---

## Requerimientos funcionales

### Funcionalidades mínimas esperadas
- El usuario administrador debe poder ver un listado de productos.
- Debe poder crear nuevos productos.
- Debe poder editar productos existentes.
- Debe poder buscar productos por nombre, SKU o código de barras.
- Debe poder filtrar productos por categoría o estado, si se define.
- Debe poder asignar una categoría al producto.
- Debe poder capturar precio, costo, impuesto y stock inicial.
- Debe poder subir o asignar una imagen principal del producto.
- Debe poder activar o desactivar productos.
- Debe poder administrar categorías relacionadas con los productos.

### Campos funcionales sugeridos para productos
La lista puede ajustarse, pero el módulo idealmente debe contemplar estos campos base:
- nombre
- SKU
- código de barras
- descripción
- categoría
- unidad
- precio de venta
- costo
- porcentaje de impuesto
- stock inicial o stock actual base
- imagen principal
- estatus del producto

### Comportamientos esperados
- Un producto desactivado no debe estar disponible para venta en el POS.
- El sistema debe evitar registros incompletos o inconsistentes.
- Las búsquedas deben devolver resultados útiles y rápidos.
- La edición debe respetar la integridad del producto ya registrado.
- El módulo debe servir como catálogo maestro para los módulos posteriores.

---

## Definición del hito

El **Hito 2** se considerará terminado cuando el sistema cuente con un módulo de productos funcional, visualmente sólido y técnicamente estable, listo para alimentar el flujo de ventas del POS.

### Criterios de cumplimiento
- Existe un listado funcional de productos.
- Existe alta de productos con validaciones.
- Existe edición de productos.
- Existe administración básica de categorías.
- Existen búsquedas y filtros funcionales.
- Existe manejo de estado activo/inactivo.
- Existe carga o asignación de imagen principal.
- Los productos quedan correctamente persistidos en la base de datos.
- El módulo mantiene consistencia visual con el sistema.
- El catálogo queda listo para integrarse al **Hito 3 o Hito 4**, según el orden operativo definido por el proyecto.

### Resultado del negocio
Al terminar este hito, el negocio contará con un catálogo organizado de productos, lo que permitirá comenzar a cargar información real y preparar la operación comercial dentro del sistema POS.

---

## Resumen ejecutivo del hito

El Hito 2 consiste en desarrollar el módulo de catálogo de productos del POS SaaS. Su meta es dejar lista la administración de productos con una interfaz clara, atractiva y funcional, incluyendo categorías, búsqueda, formularios, control de estado e integración básica con inventario. Este módulo será la base operativa para que el sistema pueda avanzar al punto de venta y al registro real de ventas.

