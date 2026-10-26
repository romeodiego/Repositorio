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
  con usuarios y roles, datos compartidos en una base real en vez de
  `localStorage`. Incluye `id_actividad` como clave primaria oculta en el
  formulario pero presente en las descargas de Excel/PDF. Documentado en
  `MYSQL_README.md` (cómo importar el esquema, configurar
  `backend/config.php`, usuario admin inicial `admin` / `CambiarAhora123!`
  a cambiar cuanto antes). Probada de punta a punta con PHP+MariaDB real
  antes de subir el código. La versión offline (`mantenimientos_vnt.html`)
  sigue intacta y no depende de nada de esto.
- Dentro de esa variante, pestaña **"👤 Usuarios"** (solo admin): alta,
  edición (nombre/rol/reseteo de contraseña, con confirmación) y
  activar/desactivar usuarios desde la propia app, sin necesidad de usar
  curl contra la API. Un admin no puede cambiarse el rol ni desactivarse a
  sí mismo desde ahí.
- Tercer rol de usuario: **observador** (solo lectura: recorrer
  actividades y descargar PDF/Excel, sin crear/editar/borrar nada; no ve
  Catálogos ni Usuarios). Son 3 roles en total: admin, editor, observador.
  Cualquier usuario, de cualquier rol, puede cambiar su propia contraseña
  desde "🔑 Mi contraseña" en el encabezado.
- El rol **editor** tiene permiso completo sobre los **Catálogos**
  (alta/baja/modificación, incluidos los botones "+" inline), igual que
  `admin`; la única pestaña que sigue siendo exclusiva de `admin` es
  "Usuarios".
- En esa variante, "Fecha de realización" se separó en **Fecha de
  Inicio** (obligatoria) y **Fecha de Finalización** (opcional, confirma
  que la actividad terminó). Se agregó **Estado** (Planificado / En
  Proceso / Finalizado / Cancelado / Reprogramada), calculado por la API
  en cada lectura (no se guarda ni necesita un proceso en segundo plano):
  Planificado si la fecha de inicio es futura, En Proceso al llegar esa
  fecha, Finalizado al cargar fecha de finalización, Cancelado por un
  checkbox manual que anula la actividad sin borrar el registro,
  Reprogramada al reprogramar la actividad (ver más abajo). Visible en el
  listado, el calendario y como filtro.
- En esa variante, el formulario de **Nueva Actividad** ya no se cierra al
  hacer clic afuera o apretar Escape (solo con Guardar/Cancelar); el campo
  "Tipo de Equipamiento" se renombró a **"Tipo de Equipamiento/Servicio"**
  y pasó a ubicarse antes que Proveedor; la **Fecha de Finalización** no
  admite fechas futuras (validado en el formulario y en el backend); el
  selector de **Tipo de Mantenimiento** usa los mismos colores que las
  etiquetas de Detalle de Actividades, con la opción elegida resaltada.
- En la pestaña **Calendario** de esa variante, a la izquierda del
  calendario hay dos gráficos de torta (CSS puro, sin librerías) con las
  actividades del mes que se está mostrando: uno por **Estado** y otro por
  **Subcontrato** (Crux Marine, ACG —incluye "American Consulting",
  TecPortOne, y el resto agrupado en "Otros"). Muestran "Sin actividades
  este mes" cuando corresponde y se actualizan al navegar entre meses.
  La semana empieza en **lunes** (Lun a Dom). Las columnas de los días
  son de ancho flexible y parejo: la semana completa (7 días) se ve
  siempre sin scroll horizontal.
- En esa variante, el texto de las etiquetas del selector de **Tipo de
  Mantenimiento** (en Nueva Actividad) es negro intenso y negrita, para
  mejor contraste sobre los colores de fondo.
- El listado de **Detalle de Actividades** de esa variante está paginado
  de a 20 filas (controles Anterior/Siguiente debajo de la tabla, se
  resetea a la página 1 al cambiar un filtro); las descargas de
  Excel/PDF siguen incluyendo todos los resultados filtrados, no solo la
  página visible. La columna Acciones usa íconos (✏️ / 📄 / 🗑️) en una
  sola línea en vez de texto, con el texto disponible como tooltip. Esa
  pestaña también ocupa ahora el ancho completo de la página (igual que
  Calendario), y su tabla incluye además las columnas **Nota de Pedido**
  y **Orden de Servicio**. La columna "Equipamiento" se renombró a
  **"Equipamiento / Servicio"** y su dato se muestra en negrita; el
  Proveedor usa fuente normal (sin negrita). Las etiquetas de Estado
  (p. ej. "En Proceso") nunca se parten en dos líneas.
- Pendiente de decisión del usuario: en esa misma pestaña había pedido
  cambiar el nombre de la columna "Inspector VNA" por "Responsable VNA",
  pero esa última ya existe como columna separada (campo obligatorio,
  distinto de Inspector VNA); al consultarle, el usuario pidió no
  considerar ese cambio por ahora, así que ambas columnas se mantienen
  tal cual estaban.
- Nuevo catálogo **Motivos** (pestaña Catálogos, un único catálogo
  compartido entre Cancelación y Reprogramación): cada motivo tiene
  nombre y un tilde "Responsabilidad del proveedor". Al cancelar una
  actividad ahora es obligatorio elegir un **Motivo de Cancelación**. Se
  agregó la posibilidad de **Reprogramar** una actividad (botón "↻
  Reprogramar", solo para Planificada/En Proceso): pide Motivo de
  Reprogramación y Nueva Fecha de Inicio; la actividad origen pasa a
  estado **Reprogramada** (nuevo estado, quinto además de Planificado/En
  Proceso/Finalizado/Cancelado) y se crea una actividad nueva con la
  nueva fecha, los mismos datos, y una referencia a la actividad origen.
  Todo esto corre dentro de una transacción en el backend. El botón "↻
  Reprogramar" tiene fondo amarillo suave para distinguirlo del resto.
- En el selector de **Tipo de Mantenimiento** (Nueva Actividad), las
  opciones no seleccionadas se ven con colores menos atenuados que antes
  (opacidad .75 en vez de .4), y la opción elegida muestra el punto de
  selección (radio) con relleno sólido (antes dependía del navegador).
- En la pestaña **Calendario**, debajo de los dos gráficos de torta hay un
  tercer gráfico de **barras**: "Performance de Subcontratos" (Crux
  Marine, ACG, TecPortOne — no incluye "Otros"). Por cada uno, sobre las
  actividades del mes que se está mostrando: `1 − ((reprogramadas por
  responsabilidad del proveedor + canceladas por responsabilidad del
  proveedor) / total de actividades de ese subcontrato ese mes)`. Una
  actividad cuenta en contra solo si su motivo (de Cancelación o de
  Reprogramación) tiene tildada "Responsabilidad del proveedor" en el
  catálogo de Motivos. Barra verde (≥80%), ámbar (50–79%) o roja (<50%);
  "Sin datos" si no hubo actividades ese mes para ese subcontrato.
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
