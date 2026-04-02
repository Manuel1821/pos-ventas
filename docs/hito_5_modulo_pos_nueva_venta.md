# Hito 5: Módulo POS / Nueva venta del POS SaaS

## Objetivo del hito

Construir el módulo principal del sistema POS SaaS: la captura, validación, cobro y confirmación de nuevas ventas dentro del punto de venta. Este hito debe dejar listo el flujo operativo completo para que el negocio pueda vender productos desde una interfaz rápida, clara, atractiva y confiable, conectando el catálogo de productos, los clientes, la caja y los movimientos de inventario.

La meta principal de este hito es transformar la base técnica y los catálogos previos en una operación comercial real. El módulo POS debe permitir buscar productos, agregarlos al carrito, modificar cantidades, aplicar descuentos según las reglas definidas, asociar un cliente, registrar el cobro, generar el comprobante y persistir correctamente toda la venta con sus efectos relacionados.

Este hito es crítico porque representa el flujo más importante del sistema. Por ello, además de una experiencia visual moderna y ágil, debe contemplar consistencia transaccional, validaciones sólidas y una operación preparada para uso real en mostrador.

---

## Tareas del hito

### 1. Diseño del flujo completo de nueva venta
- Definir el recorrido operativo de una venta desde el ingreso al módulo hasta la confirmación final.
- Determinar estados temporales del carrito y de la venta.
- Definir el comportamiento del sistema cuando no exista caja abierta.
- Establecer las reglas para ventas normales, descuentos y tipos de cobro permitidos.
- Preparar el flujo para ventas rápidas y operación continua.

### 2. Construcción de la interfaz principal del POS
- Diseñar la pantalla principal del punto de venta.
- Organizar visualmente el buscador de productos, el carrito, los totales y las acciones principales.
- Mantener la interfaz limpia, rápida y orientada a uso intensivo.
- Asegurar consistencia con la línea visual definida en hitos anteriores.
- Optimizar la experiencia para escritorio y uso táctil cuando sea necesario.

### 3. Búsqueda y selección de productos
- Implementar búsqueda rápida por nombre, SKU o código de barras.
- Permitir agregar productos al carrito desde resultados de búsqueda.
- Validar disponibilidad del producto según stock y estado.
- Evitar que productos inactivos aparezcan para venta.
- Garantizar tiempos de respuesta ágiles en búsqueda y selección.

### 4. Gestión del carrito de venta
- Permitir agregar productos al carrito.
- Permitir modificar cantidades.
- Permitir eliminar productos del carrito.
- Mostrar precio unitario, subtotal por línea, impuestos y total.
- Validar stock disponible al modificar cantidades.
- Mantener cálculo automático de totales en tiempo real.

### 5. Aplicación de descuentos y reglas comerciales
- Definir el alcance del descuento dentro de este hito.
- Permitir aplicar descuentos si forman parte del MVP aprobado.
- Validar límites o reglas de descuento según perfil de usuario o política del negocio.
- Reflejar el descuento correctamente en subtotal, impuesto y total cuando corresponda.

### 6. Selección de cliente
- Permitir asociar la venta a un cliente registrado.
- Permitir utilizar el cliente genérico para ventas rápidas.
- Integrar búsqueda de cliente dentro del flujo del POS.
- Asegurar que la venta quede ligada correctamente al cliente seleccionado.

### 7. Proceso de cobro
- Construir el flujo o modal de cobro.
- Mostrar total a pagar de forma clara.
- Permitir registrar uno o varios métodos de pago, según alcance definido.
- Capturar importes pagados y validar totales.
- Calcular cambio cuando aplique.
- Validar que la venta no pueda confirmarse si el cobro es inconsistente.

### 8. Persistencia transaccional de la venta
- Registrar la venta en la tabla `sales`.
- Registrar cada partida en `sale_items`.
- Registrar pagos en `sale_payments`.
- Descontar stock y registrar movimientos en `inventory_movements`.
- Relacionar la venta con la sesión de caja activa.
- Asegurar que todo el proceso se ejecute de forma transaccional para evitar inconsistencias.

### 9. Generación de comprobante o ticket
- Generar ticket o comprobante de venta.
- Preparar versión imprimible o exportable según alcance.
- Mostrar resumen final de la venta confirmada.
- Dejar lista la base para reimpresión futura desde el módulo de ventas.

### 10. Reglas operativas del POS
- Definir si una venta requiere caja abierta obligatoria.
- Definir qué ocurre cuando un producto no tiene stock suficiente.
- Definir si se permiten ventas con stock en cero o negativo.
- Definir la lógica de precios, impuestos y redondeos.
- Determinar si se podrán capturar observaciones o notas en la venta.

### 11. Integración con módulos previos
- Consumir correctamente productos del catálogo.
- Consumir correctamente clientes registrados o cliente genérico.
- Impactar correctamente la caja abierta.
- Generar movimientos válidos para inventario.
- Preparar base para consultas posteriores en el módulo de ventas y reportes.

### 12. Definición visual del módulo
- Diseñar una interfaz atractiva, moderna y altamente operativa.
- Priorizar claridad visual en productos, carrito y cobro.
- Usar componentes reutilizables del sistema.
- Preparar estados visuales claros para errores, productos sin stock, venta exitosa y validaciones.
- Mantener responsividad y una experiencia fluida.

### 13. Validaciones funcionales del módulo
- Verificar búsqueda correcta de productos.
- Verificar altas y bajas dentro del carrito.
- Verificar cálculo correcto de totales.
- Verificar selección correcta de cliente.
- Verificar cobro correcto.
- Verificar persistencia correcta de venta, pagos e inventario.
- Verificar relación con caja activa.
- Confirmar generación del ticket.

---

## Requerimientos técnicos

### Tecnologías base
- **PHP** para lógica del módulo y renderizado de vistas.
- **MySQL** para persistencia de ventas, pagos e inventario.
- **HTML, CSS, JavaScript y Bootstrap 5** para interfaz.
- **PDO** para acceso a datos.
- **Bootstrap Icons** o **Font Awesome** para reforzar la interfaz.

### Estructura técnica del módulo
- Separación entre controlador, servicio, acceso a datos y vistas.
- Reutilización del layout y componentes definidos en el Hito 1.
- Lógica de ventas centralizada en servicios para asegurar consistencia.
- Preparación del flujo para transacciones atómicas.
- Integración limpia con productos, clientes, caja e inventario.

### Base de datos y persistencia
- Uso de tablas `sales`, `sale_items`, `sale_payments` e `inventory_movements`.
- Relación con `products`, `customers`, `cash_sessions` y `users`.
- Índices adecuados para búsquedas por fecha, folio, cliente y producto.
- Uso de InnoDB y `utf8mb4`.
- Soporte para transacciones SQL durante el guardado de la venta.

### Reglas técnicas recomendadas
- La confirmación de venta debe ser transaccional.
- No debe guardarse una venta parcial si falla alguna de sus partes.
- Deben validarse existencias antes de confirmar.
- Deben usarse consultas preparadas y validación de entradas.
- Los cálculos de subtotal, descuento, impuesto y total deben seguir una regla única y consistente.
- Debe guardarse suficiente información para consulta histórica futura, incluso si cambian datos del producto después.

### Requerimientos visuales
- La interfaz debe ser rápida, limpia y pensada para operación continua.
- El área del carrito debe ser fácil de leer y manipular.
- Los totales deben destacarse visualmente.
- El modal o vista de cobro debe ser claro y seguro de usar.
- Deben existir estados visuales para validaciones, errores y venta completada.
- El módulo debe ser consistente con el resto del sistema y responsivo.

### Preparación para módulos futuros
Este módulo debe quedar listo para ser utilizado por:
- Ventas
- Reportes
- Cancelaciones y devoluciones
- Cierres de caja
- Funciones futuras relacionadas con crédito, apartados o facturación

---

## Requerimientos funcionales

### Funcionalidades mínimas esperadas
- El usuario autorizado debe poder ingresar al módulo POS.
- Debe poder buscar productos por nombre, SKU o código de barras.
- Debe poder agregar productos al carrito.
- Debe poder modificar cantidades y eliminar partidas del carrito.
- Debe poder visualizar subtotal, descuento, impuesto y total.
- Debe poder asociar un cliente registrado o cliente genérico.
- Debe poder registrar el cobro de la venta.
- Debe poder usar los métodos de pago definidos en el sistema.
- Debe poder confirmar la venta.
- El sistema debe registrar correctamente la venta, sus partidas, pagos y movimientos de inventario.
- Debe generarse un ticket o comprobante de la venta.

### Datos funcionales sugeridos para la venta
El módulo idealmente debe contemplar:
- folio de venta
- fecha y hora
- usuario vendedor
- cliente
- productos vendidos
- cantidad por producto
- precio unitario
- descuento
- impuesto
- subtotal
- total
- método o métodos de pago
- monto pagado
- cambio
- caja asociada
- observaciones, si aplica

### Comportamientos esperados
- No debe poder confirmarse una venta inconsistente.
- Una venta confirmada debe impactar inventario.
- Una venta confirmada debe quedar relacionada con caja.
- Un producto inactivo no debe poder venderse.
- El sistema debe impedir errores típicos de mostrador, como cantidades inválidas o cobros incompletos.
- La experiencia debe ser suficientemente ágil para uso real.

---

## Definición del hito

El **Hito 5** se considerará terminado cuando el sistema cuente con un módulo POS funcional, visualmente sólido y técnicamente consistente, capaz de registrar ventas reales de punta a punta.

### Criterios de cumplimiento
- Existe una interfaz principal del POS.
- Existe búsqueda funcional de productos.
- Existe carrito de venta operativo.
- Existen cálculos correctos de totales.
- Existe selección de cliente.
- Existe flujo de cobro.
- Existe confirmación de venta con persistencia correcta.
- Existe impacto correcto en inventario.
- Existe relación correcta con la caja activa.
- Existe generación de ticket o comprobante.
- El módulo mantiene consistencia visual con el sistema.
- El flujo queda listo para integrarse al **Hito 6: Módulo de ventas**.

### Resultado del negocio
Al terminar este hito, el negocio contará con la capacidad real de registrar ventas dentro del sistema POS, conectando productos, clientes, caja y movimientos de inventario en una sola operación confiable.

---

## Resumen ejecutivo del hito

El Hito 5 consiste en desarrollar el módulo principal del POS: la nueva venta. Su meta es dejar listo el flujo completo de búsqueda de productos, armado de carrito, selección de cliente, cobro, confirmación y emisión de ticket, asegurando además el impacto correcto en caja e inventario. Este hito representa el corazón operativo del sistema y marca el paso del desarrollo técnico a la operación comercial real.

