<?php
require_once __DIR__ . '/helpers.php';
bootstrap_api();

// Catálogos administrables desde la pestaña "Catálogos" de la aplicación.
// Cada uno define en qué tabla vive, a qué columna de "actividades" está
// enlazado (para poder avisar cuántos registros lo usan antes de borrar) y
// si esa columna es obligatoria (ON DELETE RESTRICT) u opcional (SET NULL).
const CATALOGOS = [
    'proveedores' => [
        'tabla' => 'proveedores',
        'campoActividad' => 'proveedor_id',
        'obligatorio' => true,
        'tieneProveedorSugerido' => false,
    ],
    'tipos_equipamiento' => [
        'tabla' => 'tipos_equipamiento',
        'campoActividad' => 'tipo_equipamiento_id',
        'obligatorio' => true,
        'tieneProveedorSugerido' => true,
    ],
    'inspectores_aayc' => [
        'tabla' => 'inspectores_aayc',
        'campoActividad' => 'inspector_aayc_id',
        'obligatorio' => false,
        'tieneProveedorSugerido' => false,
    ],
    'inspectores_vna' => [
        'tabla' => 'inspectores_vna',
        'campoActividad' => 'inspector_vna_id',
        'obligatorio' => false,
        'tieneProveedorSugerido' => false,
    ],
    'responsables_vna' => [
        'tabla' => 'responsables_vna',
        'campoActividad' => 'responsable_vna_id',
        'obligatorio' => true,
        'tieneProveedorSugerido' => false,
    ],
];

require_login();

$tipo = $_GET['tipo'] ?? '';
if (!isset(CATALOGOS[$tipo])) {
    json_error('Catálogo desconocido. Use uno de: ' . implode(', ', array_keys(CATALOGOS)), 404);
}
$def = CATALOGOS[$tipo];
$tabla = $def['tabla'];
$method = $_SERVER['REQUEST_METHOD'];

function fila_a_json(array $row, array $def): array {
    $out = ['id' => (int)$row['id'], 'nombre' => $row['nombre']];
    if ($def['tieneProveedorSugerido']) {
        $out['proveedorSugeridoId'] = $row['proveedor_sugerido_id'] !== null ? (int)$row['proveedor_sugerido_id'] : null;
        $out['proveedorSugerido'] = $row['proveedor_sugerido_nombre'] ?? null;
    }
    return $out;
}

if ($method === 'GET') {
    if ($def['tieneProveedorSugerido']) {
        $sql = "SELECT t.id, t.nombre, t.proveedor_sugerido_id, p.nombre AS proveedor_sugerido_nombre
                FROM {$tabla} t LEFT JOIN proveedores p ON p.id = t.proveedor_sugerido_id
                ORDER BY t.nombre";
    } else {
        $sql = "SELECT id, nombre FROM {$tabla} ORDER BY nombre";
    }
    $rows = db()->query($sql)->fetchAll();
    json_response(array_map(fn($r) => fila_a_json($r, $def), $rows));
}

require_admin();

if ($method === 'POST') {
    $body = read_json_body();
    $nombre = trim($body['nombre'] ?? '');
    if ($nombre === '') {
        json_error('El nombre es obligatorio.', 400);
    }

    try {
        if ($def['tieneProveedorSugerido']) {
            $proveedorSugeridoId = isset($body['proveedorSugeridoId']) && $body['proveedorSugeridoId'] !== ''
                ? (int)$body['proveedorSugeridoId'] : null;
            $stmt = db()->prepare("INSERT INTO {$tabla} (nombre, proveedor_sugerido_id) VALUES (?, ?)");
            $stmt->execute([$nombre, $proveedorSugeridoId]);
        } else {
            $stmt = db()->prepare("INSERT INTO {$tabla} (nombre) VALUES (?)");
            $stmt->execute([$nombre]);
        }
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            json_error("Ya existe un registro con ese nombre en este catálogo.", 409);
        }
        throw $e;
    }

    $id = (int)db()->lastInsertId();
    $stmt = db()->prepare("SELECT id, nombre FROM {$tabla} WHERE id = ?");
    $stmt->execute([$id]);
    json_response(fila_a_json($stmt->fetch() + ['proveedor_sugerido_id' => $proveedorSugeridoId ?? null], $def), 201);
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    json_error('Falta el parámetro id.', 400);
}

if ($method === 'PUT') {
    $body = read_json_body();
    $nombre = trim($body['nombre'] ?? '');
    if ($nombre === '') {
        json_error('El nombre es obligatorio.', 400);
    }

    try {
        if ($def['tieneProveedorSugerido']) {
            $proveedorSugeridoId = isset($body['proveedorSugeridoId']) && $body['proveedorSugeridoId'] !== ''
                ? (int)$body['proveedorSugeridoId'] : null;
            $stmt = db()->prepare("UPDATE {$tabla} SET nombre = ?, proveedor_sugerido_id = ? WHERE id = ?");
            $stmt->execute([$nombre, $proveedorSugeridoId, $id]);
        } else {
            $stmt = db()->prepare("UPDATE {$tabla} SET nombre = ? WHERE id = ?");
            $stmt->execute([$nombre, $id]);
        }
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            json_error("Ya existe un registro con ese nombre en este catálogo.", 409);
        }
        throw $e;
    }

    if ($def['tieneProveedorSugerido']) {
        $sql = "SELECT t.id, t.nombre, t.proveedor_sugerido_id, p.nombre AS proveedor_sugerido_nombre
                FROM {$tabla} t LEFT JOIN proveedores p ON p.id = t.proveedor_sugerido_id WHERE t.id = ?";
    } else {
        $sql = "SELECT id, nombre FROM {$tabla} WHERE id = ?";
    }
    $stmt = db()->prepare($sql);
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        json_error('No encontrado.', 404);
    }
    json_response(fila_a_json($row, $def));
}

if ($method === 'DELETE') {
    $campo = $def['campoActividad'];
    $stmt = db()->prepare("SELECT COUNT(*) AS n FROM actividades WHERE {$campo} = ?");
    $stmt->execute([$id]);
    $enUso = (int)$stmt->fetch()['n'];

    if ($enUso > 0 && $def['obligatorio']) {
        json_error(
            "No se puede eliminar: está usado en {$enUso} actividad(es) y es un dato obligatorio. " .
            "Reasigne esas actividades a otro valor antes de eliminarlo.",
            409
        );
    }

    $stmt = db()->prepare("DELETE FROM {$tabla} WHERE id = ?");
    $stmt->execute([$id]);

    $mensaje = $enUso > 0
        ? "Eliminado. {$enUso} actividad(es) que lo usaban quedaron sin ese dato."
        : "Eliminado.";
    json_response(['ok' => true, 'mensaje' => $mensaje]);
}

json_error('Método no soportado.', 405);
