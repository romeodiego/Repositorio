# Memoria del proyecto

> Leer este archivo al inicio de cada sesión para no perder el contexto del proyecto.

## Quién soy

**Diego Romeo.** Mi negocio es **VNA — Vía Navegable Argentina**.

## Qué estamos construyendo

**Modernización Tecnológica - Gestión de Actividades**, una herramienta para
gestionar las actividades de mantenimiento del equipamiento instalado en la
Vía Navegable Troncal: actividades **preventivas**, **correctivas** y
**evolutivas**.

Categorías de equipamiento que cubre: Centro de Monitoreo, Punto de
Monitoreo Remoto, Equipamiento de Campo, Boyas Multiparamétricas y SiMon.

## Estado actual

- Implementación actual: `mantenimientos_vnt.html` — archivo único,
  autocontenido, sin dependencias externas (HTML/CSS/JS embebidos, logo VNA
  en base64, datos persistidos en `localStorage` del navegador).
- Funcionalidades ya resueltas: alta/edición/baja de actividades
  (Preventivo, Correctivo o Evolutivo), calendario mensual con doble clic
  para ver/editar o crear en una fecha (muestra tipo de equipamiento,
  proveedor y descripción de cada actividad), listado filtrable (incluye
  columna Descripción), descarga de reporte en PDF vía impresión del
  navegador (reporte individual y listado completo, con todos los campos).
- Campos del formulario: tipo de actividad, fecha, proveedor, tipo de
  equipamiento, descripción, responsable VNA, Inspector AAyC, Inspector
  VNA, nota de pedido, orden de servicio y comentarios.
- Pestaña **Catálogos**: alta/baja/modificación de Proveedores, Tipos de
  equipamiento, Inspectores AAyC e Inspectores VNA. Los combos
  correspondientes del formulario se alimentan de estos catálogos (no son
  texto libre ni listas fijas), con un botón "+" para cargar un ítem nuevo
  sin salir del formulario. Cada tipo de equipamiento puede tener un
  proveedor habitual que se autocompleta al elegirlo. Renombrar un ítem
  actualiza en cascada las actividades existentes; eliminarlo avisa cuántos
  registros lo usan pero no borra esos datos históricos. La lógica es
  genérica (mapa `CATALOG_DEFS` en el JS) para los 4 catálogos.
- Trabajo en curso en la rama `claude/vnt-maintenance-form-plt36o`,
  [PR #1](https://github.com/romeodiego/Repositorio/pull/1).
- Pendiente de definir: si se necesita una bitácora realmente compartida
  entre varios usuarios (hoy los datos quedan en el navegador de cada uno,
  no en un backend común).

## Preferencias de diseño

- La paleta de colores debe inspirarse en el **logo de VNA** (azules/marino
  oscuro, en línea con los tonos ya usados en la interfaz actual).
- El enfoque es iterativo: seguir refinando la herramienta sesión a sesión
  hasta llegar a una solución sólida y prolija, no quedarse con la primera
  versión.
