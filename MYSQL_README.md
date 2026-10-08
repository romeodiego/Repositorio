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

1. Crear tu usuario real (ver "Gestión de usuarios" abajo) con rol `admin`.
2. Desactivar o cambiar la contraseña del usuario `admin` inicial.

## Roles de usuario

- **admin**: puede gestionar actividades y los catálogos (Proveedores,
  Tipos de equipamiento, Inspectores AAyC/VNA, Responsables VNA).
- **editor**: puede cargar, editar y eliminar actividades, pero no ve la
  pestaña "Catálogos" ni puede crear ítems de catálogo nuevos desde el
  formulario (los botones "+" quedan ocultos).

## Gestión de usuarios

No hay pantalla propia para administrar usuarios todavía. Un administrador
puede crear, editar o desactivar usuarios llamando a la API directamente,
por ejemplo con `curl` (reemplazar `tu-dominio` y ajustar según haga falta):

```bash
# Crear un usuario (ejecutar logueado como admin, con la cookie de sesión)
curl -b cookies.txt -X POST https://tu-dominio/backend/usuarios.php \
  -H "Content-Type: application/json" \
  -d '{"nombreUsuario":"jperez","nombreCompleto":"Juan Pérez","password":"UnaClaveSegura123","rol":"editor"}'
```

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
