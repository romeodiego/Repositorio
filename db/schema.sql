-- ============================================================================
-- Modernización Tecnológica - Gestión de Actividades (VNT / VNA)
-- Esquema de base de datos relacional MySQL
--
-- Cómo usarlo:
--   mysql -u <usuario> -p < db/schema.sql
-- o importarlo desde phpMyAdmin / el panel de tu hosting.
--
-- Crea la base "vnt_actividades", todas las tablas, claves foráneas,
-- un usuario administrador inicial y los datos de catálogo de ejemplo
-- que ya traía la versión offline de la aplicación.
--
-- IMPORTANTE: el SET NAMES de abajo evita que los acentos/ñ se corrompan
-- si el cliente mysql se conecta con un charset distinto a utf8mb4.
-- ============================================================================

SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS vnt_actividades
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE vnt_actividades;

-- ----------------------------------------------------------------------------
-- Usuarios (login de la aplicación)
-- ----------------------------------------------------------------------------
CREATE TABLE usuarios (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre_usuario   VARCHAR(100)  NOT NULL UNIQUE,
  password_hash    VARCHAR(255)  NOT NULL,
  nombre_completo  VARCHAR(150)  NOT NULL,
  rol              ENUM('admin', 'editor') NOT NULL DEFAULT 'editor',
  activo           TINYINT(1)    NOT NULL DEFAULT 1,
  creado_en        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Usuario inicial: admin / CambiarAhora123!
-- IMPORTANTE: iniciar sesión con estas credenciales y cambiar la contraseña
-- (o crear tu propio usuario admin y desactivar este) antes de usar la
-- aplicación en producción.
INSERT INTO usuarios (nombre_usuario, password_hash, nombre_completo, rol) VALUES
  ('admin', '$2y$12$F2NDmd8aPb8HilMk7FaRqe6lg.z4Ju5BWhmDUVuGMPPwwCkS6IV4m', 'Administrador', 'admin');

-- ----------------------------------------------------------------------------
-- Catálogos (alta/baja/modificación desde la pestaña "Catálogos")
-- ----------------------------------------------------------------------------
CREATE TABLE proveedores (
  id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre  VARCHAR(150) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE tipos_equipamiento (
  id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre                  VARCHAR(150) NOT NULL UNIQUE,
  proveedor_sugerido_id   INT UNSIGNED NULL,
  CONSTRAINT fk_tipoequipo_proveedor
    FOREIGN KEY (proveedor_sugerido_id) REFERENCES proveedores(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE inspectores_aayc (
  id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre  VARCHAR(150) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE inspectores_vna (
  id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre  VARCHAR(150) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE responsables_vna (
  id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre  VARCHAR(150) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- Actividades (tabla principal)
--
-- id_actividad es la clave primaria solicitada: no se muestra en el
-- formulario de alta/edición, pero la API la incluye siempre en las
-- respuestas y por lo tanto queda disponible en las descargas de
-- Excel y PDF generadas por la aplicación.
-- ----------------------------------------------------------------------------
CREATE TABLE actividades (
  id_actividad          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tipo_mantenimiento    ENUM('Preventivo', 'Correctivo', 'Evolutivo') NOT NULL,
  fecha                 DATE NOT NULL,
  proveedor_id          INT UNSIGNED NOT NULL,
  tipo_equipamiento_id  INT UNSIGNED NOT NULL,
  descripcion           TEXT NOT NULL,
  responsable_vna_id    INT UNSIGNED NOT NULL,
  inspector_aayc_id     INT UNSIGNED NULL,
  inspector_vna_id      INT UNSIGNED NULL,
  nota_pedido           VARCHAR(100) NULL,
  orden_servicio        VARCHAR(100) NULL,
  comentarios           TEXT NULL,
  creado_por            INT UNSIGNED NULL,
  creado_en             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                        ON UPDATE CURRENT_TIMESTAMP,

  CONSTRAINT fk_act_proveedor
    FOREIGN KEY (proveedor_id) REFERENCES proveedores(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_act_tipoequipo
    FOREIGN KEY (tipo_equipamiento_id) REFERENCES tipos_equipamiento(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_act_responsable
    FOREIGN KEY (responsable_vna_id) REFERENCES responsables_vna(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_act_inspector_aayc
    FOREIGN KEY (inspector_aayc_id) REFERENCES inspectores_aayc(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_act_inspector_vna
    FOREIGN KEY (inspector_vna_id) REFERENCES inspectores_vna(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_act_creado_por
    FOREIGN KEY (creado_por) REFERENCES usuarios(id)
    ON DELETE SET NULL ON UPDATE CASCADE,

  INDEX idx_act_fecha (fecha),
  INDEX idx_act_tipo_mant (tipo_mantenimiento),
  INDEX idx_act_tipo_equipo (tipo_equipamiento_id)
) ENGINE=InnoDB;

-- Nota sobre integridad referencial:
-- Proveedor, Tipo de equipamiento y Responsable VNA son obligatorios en una
-- actividad (ON DELETE RESTRICT): si alguno está en uso, la API devuelve un
-- error claro en vez de permitir borrarlo (a diferencia de la versión
-- offline, que permitía borrar igual y conservaba el texto). Inspector AAyC
-- e Inspector VNA son opcionales: si se borran, las actividades que los
-- usaban quedan sin ese dato (ON DELETE SET NULL).

-- ----------------------------------------------------------------------------
-- Datos de catálogo de ejemplo (los mismos que la versión offline)
-- ----------------------------------------------------------------------------
INSERT INTO proveedores (nombre) VALUES
  ('Crux Marine'),
  ('American Consulting');

INSERT INTO tipos_equipamiento (nombre, proveedor_sugerido_id) VALUES
  ('Centro de Monitoreo', (SELECT id FROM proveedores WHERE nombre = 'Crux Marine')),
  ('Punto de Monitoreo Remoto', (SELECT id FROM proveedores WHERE nombre = 'American Consulting')),
  ('Equipamiento de Campo', NULL),
  ('Boyas Multiparamétricas', (SELECT id FROM proveedores WHERE nombre = 'Crux Marine')),
  ('SiMon', NULL);

INSERT INTO inspectores_aayc (nombre) VALUES
  ('María Gómez'),
  ('Carlos Ruiz');

INSERT INTO inspectores_vna (nombre) VALUES
  ('Martín Suárez');

INSERT INTO responsables_vna (nombre) VALUES
  ('Juan Pérez'),
  ('Laura Díaz');
