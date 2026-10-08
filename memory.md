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
  proveedor y descripción de cada actividad), pestaña **Detalle de
  Actividades** (listado filtrable, columna Descripción justo después de
  Equipamiento), descarga de reporte en PDF vía impresión del navegador y
  descarga a **Excel** (.xls / SpreadsheetML, sin librerías externas) —
  ambos respetan los filtros aplicados.
- Campos del formulario, en este orden: tipo de actividad, fecha,
  proveedor, tipo de equipamiento, descripción, Nota de Pedido y Orden de
  Servicio (misma fila), Inspector AAyC e Inspector VNA (misma fila),
  Responsable VNA, y comentarios al final.
- Pestaña **Catálogos**: alta/baja/modificación de Proveedores, Tipos de
  equipamiento, Inspectores AAyC, Inspectores VNA y Responsables VNA. Los
  combos correspondientes del formulario se alimentan de estos catálogos
  (no son texto libre ni listas fijas), con un botón "+" para cargar un
  ítem nuevo sin salir del formulario. Cada tipo de equipamiento puede
  tener un proveedor habitual que se autocompleta al elegirlo. Renombrar un
  ítem actualiza en cascada las actividades existentes; eliminarlo avisa
  cuántos registros lo usan pero no borra esos datos históricos. La lógica
  es genérica (mapa `CATALOG_DEFS` en el JS) para los 5 catálogos.
- En el listado principal, la columna "Nota de Pedido" se reemplazó por
  "Inspector VNA" (la Nota de Pedido sigue existiendo como dato, visible en
  el formulario y en las descargas).
- **Nueva variante con base de datos real**: `gestion_actividades_mysql.html`
  + `backend/` (API en PHP puro) + `db/schema.sql` (esquema MySQL
  relacional). Resuelve "¿cómo comparto esto entre varios usuarios?": login
  con usuarios y roles (`admin` ve y gestiona Catálogos; `editor` solo
  actividades), datos compartidos en una base real en vez de
  `localStorage`. Incluye `id_actividad` como clave primaria oculta en el
  formulario pero presente en las descargas de Excel/PDF. Documentado en
  `MYSQL_README.md` (cómo importar el esquema, configurar
  `backend/config.php`, usuario admin inicial `admin` / `CambiarAhora123!`
  a cambiar cuanto antes). Probada de punta a punta con PHP+MariaDB real
  antes de subir el código. La versión offline (`mantenimientos_vnt.html`)
  sigue intacta y no depende de nada de esto.
- Pendiente: desplegar esta variante en un hosting real (la sesión de
  trabajo es un contenedor temporal sin MySQL persistente); decidir si el
  stack final es PHP (como está armado) u otra cosa si el hosting elegido
  no lo soporta.
- Trabajo en curso en la rama `claude/vnt-maintenance-form-plt36o`,
  [PR #1](https://github.com/romeodiego/Repositorio/pull/1).

## Preferencias de diseño

- La paleta de colores debe inspirarse en el **logo de VNA** (azules/marino
  oscuro, en línea con los tonos ya usados en la interfaz actual).
- El enfoque es iterativo: seguir refinando la herramienta sesión a sesión
  hasta llegar a una solución sólida y prolija, no quedarse con la primera
  versión.
