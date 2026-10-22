# Gestión de Actividades — variante con base de datos MySQL

Esta es la versión conectada a una base de datos relacional MySQL. Convive
con la versión original (`mantenimientos_vnt.html`), que sigue funcionando
100% offline con `localStorage` y no requiere nada de lo de abajo.

Esta variante sí requiere un servidor con PHP y MySQL (no es "un solo
archivo sin dependencias"): hace falta desplegar un backend y una base de
datos para que funcione.

## Qué incluye

- `db/schema.sql` — esquema relacional completo (tablas, claves foráneas,
  usuario administrador inicial, datos de catálogo de ejemplo).
- `backend/` — API en PHP puro (sin frameworks ni Composer), con sesiones
  y login. Pensada para correr en cualquier hosting con PHP 8+ y MySQL/MariaDB,
  incluido hosting compartido tipo cPanel.
- `gestion_actividades_mysql.html` — la misma aplicación (calendario,
  listado, catálogos, exportación a PDF/Excel), pero con pantalla de login
  y los datos guardados en la base en vez de en el navegador.

## 1. Crear la base de datos

```bash
mysql -u <usuario> -p < db/schema.sql
```

(o importar `db/schema.sql` desde phpMyAdmin / el panel de tu hosting).

Esto crea la base `vnt_actividades`, todas las tablas y un usuario inicial:

- **Usuario:** `admin`
- **Contraseña:** `CambiarAhora123!`

**Cambiar esta contraseña (o crear tu propio usuario admin y desactivar
este) antes de usar la aplicación con datos reales.**

## 2. Configurar el backend

Editar `backend/config.php` con los datos reales de conexión:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'vnt_actividades');
define('DB_USER', 'tu_usuario_mysql');
define('DB_PASS', 'tu_password');
```

Subir toda la carpeta `backend/` al servidor, junto con
`gestion_actividades_mysql.html`, de modo que queden en la misma carpeta
(la página espera encontrar el backend en `./backend/`).

**Recomendación:** servir el HTML y el backend desde el mismo dominio.
Evita tener que lidiar con configuración de CORS y cookies entre dominios
distintos.

## 3. Primer ingreso

Abrir `gestion_actividades_mysql.html` en el navegador, iniciar sesión con
`admin` / `CambiarAhora123!`, y:

1. Crear tu usuario real desde la pestaña **Usuarios** (ver abajo) con rol
   `admin`.
2. Desactivar o cambiar la contraseña del usuario `admin` inicial.

## Roles de usuario

- **admin**: puede gestionar actividades, los catálogos (Proveedores,
  Tipos de equipamiento, Inspectores AAyC/VNA, Responsables VNA) y los
  usuarios.
- **editor**: puede cargar, editar y eliminar actividades, pero no ve las
  pestañas "Catálogos" ni "Usuarios", ni puede crear ítems de catálogo
  nuevos desde el formulario (los botones "+" quedan ocultos).
- **observador**: solo puede recorrer el calendario y el listado de
  actividades, filtrar, y descargar los reportes en Excel/PDF. No ve
  "Catálogos" ni "Usuarios", no tiene el botón "+ Nueva Actividad", y al
  abrir una actividad la ve en modo solo lectura (sin poder guardar ni
  eliminar). El backend rechaza cualquier intento de alta/edición/baja
  aunque se lo intente saltear llamando a la API directamente.

Todos los roles, incluido Observador, pueden cambiar su propia contraseña
desde el botón **"🔑 Mi contraseña"** en el encabezado (pide la contraseña
actual + la nueva, con confirmación).

## Gestión de usuarios

Los administradores tienen una pestaña **"👤 Usuarios"** (no visible para
el rol `editor`) para:

- Crear usuarios nuevos (usuario, nombre completo, contraseña de al menos
  8 caracteres, rol).
- Editar nombre, rol o resetear la contraseña de un usuario existente.
- Activar/Desactivar un usuario (un usuario desactivado no puede iniciar
  sesión, pero sus actividades ya cargadas se conservan intactas).

Por seguridad, un administrador no puede cambiarse el rol ni desactivarse
a sí mismo desde esa pantalla (esos campos quedan deshabilitados al editar
su propio usuario), para evitar quedar bloqueado accidentalmente.

Si se prefiere hacerlo por línea de comandos, la misma API queda
disponible directamente, por ejemplo con `curl`:

```bash
# Crear un usuario (ejecutar logueado como admin, con la cookie de sesión)
curl -b cookies.txt -X POST https://tu-dominio/backend/usuarios.php \
  -H "Content-Type: application/json" \
  -d '{"nombreUsuario":"jperez","nombreCompleto":"Juan Pérez","password":"UnaClaveSegura123","rol":"editor"}'
```

## Fechas y Estado de una actividad

El campo "Fecha de realización" de la versión offline se divide acá en dos:

- **Fecha de Inicio** (obligatoria): cuándo arranca o está prevista la
  actividad.
- **Fecha de Finalización** (opcional): se completa cuando la actividad ya
  se hizo. Completarla es la forma de "confirmar" que terminó.

El **Estado** se calcula solo, no se elige a mano (salvo "Cancelado"), y se
ve como una columna/badge en el listado y como filtro:

| Estado | Cuándo se asigna |
|---|---|
| Planificado | Fecha de Inicio es futura y no está cancelada ni finalizada |
| En Proceso | Llegó la Fecha de Inicio (es hoy o ya pasó) y no tiene Fecha de Finalización |
| Finalizado | Se cargó una Fecha de Finalización |
| Cancelado | Se tildó manualmente "Cancelar esta actividad" en el formulario |

El pasaje de Planificado a En Proceso ocurre solo porque el Estado se
recalcula en cada lectura contra la fecha de hoy — no hace falta ningún
proceso en segundo plano. "Cancelado" tiene prioridad sobre los demás: si
se cancela una actividad ya finalizada, el registro completo se conserva,
solo cambia la etiqueta.

## Formulario de actividad

- El formulario de **Nueva actividad** solo se cierra con los botones
  **Guardar** o **Cancelar**: un clic fuera del formulario o la tecla Escape
  ya no lo descartan por accidente (antes sí).
- El campo "Tipo de equipamiento" pasó a llamarse **"Tipo de
  Equipamiento/Servicio"**, y ahora aparece **antes** que el campo
  Proveedor (antes el orden era Proveedor y luego Tipo de equipamiento).
- La **Fecha de Finalización** no admite una fecha futura (debe ser hoy o
  anterior); se valida tanto en el formulario como en el backend.
- El selector de **Tipo de Mantenimiento** (Preventivo / Correctivo /
  Evolutivo) usa los mismos colores que las etiquetas del listado de
  Detalle de Actividades, con la opción elegida resaltada.

## Gráficos del Calendario

En el margen izquierdo de la pestaña **Calendario** se muestran dos
gráficos de torta con las actividades del mes que se está mostrando (se
actualizan al navegar entre meses):

- **Por Estado**: Planificado, En Proceso, Finalizado, Cancelado.
- **Por Subcontrato**: Crux Marine, ACG (incluye a "American Consulting"),
  TecPortOne, y el resto de los proveedores agrupados en "Otros".

Si el mes mostrado no tiene actividades cargadas, cada gráfico muestra el
mensaje "Sin actividades este mes" en lugar de un gráfico vacío.

La semana del calendario empieza en **lunes** (Lun a Dom). Las pestañas
**Calendario** y **Detalle de Actividades** ocupan el ancho completo de
la página; las demás (Catálogos, Usuarios) mantienen un ancho máximo
centrado. Las columnas de los días de la semana usan ancho flexible, de
modo que las 7 columnas siempre se ven completas y parejas, sin scroll
horizontal, sea cual sea el ancho de la ventana.

## Paginado del Detalle de Actividades

La pestaña **Detalle de Actividades** también ocupa el ancho completo de
la página; su tabla incluye, además de los campos originales, las
columnas **Nota de Pedido** y **Orden de Servicio**.

El listado se pagina de a 20 filas. Los controles "‹ Anterior" / "Siguiente ›" y el indicador de página
aparecen debajo de la tabla solo cuando hay más de 20 resultados con los
filtros aplicados. Cambiar cualquier filtro vuelve a la página 1. Las
descargas de Excel y PDF siempre incluyen **todos** los resultados
filtrados, no solo los de la página visible.

La columna **Acciones** usa íconos (✏️ editar / ver, 📄 descargar PDF, 🗑️
eliminar) en lugar de texto, alineados en una sola línea horizontal; cada
botón conserva su texto como tooltip.

El nombre del **Equipamiento / Servicio** (antes "Equipamiento") se
muestra en negrita; el **Proveedor** usa fuente normal. La etiqueta de
**Estado** siempre queda en una sola línea (por ejemplo "En Proceso"),
sin importar el ancho de la columna.

## Diferencias respecto a la versión offline

- El **Id de Actividad** (clave primaria de la base) no aparece en el
  formulario, pero sí en los nombres y en el contenido de las descargas de
  Excel y PDF (primera columna / primera fila del reporte individual).
- Eliminar un **Proveedor**, **Tipo de equipamiento** o **Responsable VNA**
  que esté en uso ahora lo **bloquea** con un mensaje claro (antes, en la
  versión offline, se permitía igual y se conservaba el texto). Esto es
  porque la base de datos relacional exige integridad referencial en esos
  campos, que son obligatorios en una actividad.
- Eliminar un **Inspector AAyC** o **Inspector VNA** en uso sigue estando
  permitido: las actividades que lo usaban quedan sin ese dato, igual que
  antes.
- Los datos ahora son compartidos entre todas las personas que entren con
  sus credenciales, en vez de guardarse por separado en el navegador de
  cada una.

## Seguridad

- Las contraseñas se guardan con `password_hash` (bcrypt), nunca en texto
  plano.
- Las sesiones usan cookies HttpOnly de PHP; servir el sitio por **HTTPS**
  en producción.
- Cambiar `CORS_ALLOWED_ORIGIN` en `backend/config.php` si el backend se
  sirve desde un dominio distinto al del HTML.
