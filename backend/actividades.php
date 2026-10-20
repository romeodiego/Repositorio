<?php
require_once __DIR__ . '/helpers.php';
bootstrap_api();
require_login();

$method = $_SERVER['REQUEST_METHOD'];

// El Estado se calcula en cada lectura (no se guarda en una columna aparte):
// esto evita necesitar un proceso en segundo plano que lo actualice a
// medianoche cuando una actividad "Planificada" pasa a "En Proceso".
const SELECT_ACTIVIDAD = "
    SELECT
        a.id_actividad    AS idActividad,
        a.tipo_mantenimiento AS tipoMantenimiento,
        a.fecha_inicio    AS fechaInicio,
        a.fecha_fin       AS fechaFin,
        a.cancelado,
        CASE
            WHEN a.cancelado = 1 THEN 'Cancelado'
            WHEN a.fecha_fin IS NOT NULL THEN 'Finalizado'
            WHEN a.fecha_inicio <= CURDATE() THEN 'En Proceso'
            ELSE 'Planificado'
        END AS estado,
        pr.nombre         AS proveedor,
        te.nombre         AS tipoEquipo,
        a.descripcion,
        rv.nombre         AS responsableVNA,
        ia.nombre         AS inspector,
        iv.nombre         AS inspectorVNA,
        a.nota_pedido     AS notaPedido,
        a.orden_servicio  AS ordenServicio,
        a.comentarios
    FROM actividades a
    JOIN proveedores pr          ON pr.id = a.proveedor_id
    JOIN tipos_equipamiento te   ON te.id = a.tipo_equipamiento_id
    JOIN responsables_vna rv     ON rv.id = a.responsable_vna_id
    LEFT JOIN inspectores_aayc ia ON ia.id = a.inspector_aayc_id
    LEFT JOIN inspectores_vna iv  ON iv.id = a.inspector_vna_id
";

function resolver_id(string $tabla, ?string $nombre, bool $obligatorio, string $etiqueta): ?int {
    $nombre = trim((string)$nombre);
    if ($nombre === '') {
        if ($obligatorio) {
            json_error("El campo \"{$etiqueta}\" es obligatorio.", 400);
        }
        return null;
    }
    $stmt = db()->prepare("SELECT id FROM {$tabla} WHERE nombre = ? LIMIT 1");
    $stmt->execute([$nombre]);
    $row = $stmt->fetch();
    if (!$row) {
        json_error("\"{$nombre}\" no existe en el catálogo de {$etiqueta}. Debe cargarlo primero en la pestaña Catálogos.", 400);
    }
    return (int)$row['id'];
}

function validar_fecha(string $valor, string $etiqueta, bool $obligatoria): ?string {
    if ($valor === '') {
        if ($obligatoria) {
            json_error("El campo \"{$etiqueta}\" es obligatorio.", 400);
        }
        return null;
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
        json_error("\"{$etiqueta}\" inválida (formato esperado AAAA-MM-DD).", 400);
    }
    return $valor;
}

function validar_y_resolver(array $body): array {
    $tipoMant = $body['tipoMantenimiento'] ?? '';
    if (!in_array($tipoMant, ['Preventivo', 'Correctivo', 'Evolutivo'], true)) {
        json_error('Tipo de mantenimiento inválido (debe ser Preventivo, Correctivo o Evolutivo).', 400);
    }
    $fechaInicio = validar_fecha((string)($body['fechaInicio'] ?? ''), 'Fecha de Inicio', true);
    $fechaFin = validar_fecha((string)($body['fechaFin'] ?? ''), 'Fecha de Finalización', false);
    if ($fechaFin !== null && $fechaFin < $fechaInicio) {
        json_error('La Fecha de Finalización no puede ser anterior a la Fecha de Inicio.', 400);
    }
    $descripcion = trim($body['descripcion'] ?? '');
    if ($descripcion === '') {
        json_error('La descripción es obligatoria.', 400);
    }

    return [
        'tipo_mantenimiento' => $tipoMant,
        'fecha_inicio' => $fechaInicio,
        'fecha_fin' => $fechaFin,
        'cancelado' => !empty($body['cancelado']) ? 1 : 0,
        'proveedor_id' => resolver_id('proveedores', $body['proveedor'] ?? '', true, 'Proveedor'),
        'tipo_equipamiento_id' => resolver_id('tipos_equipamiento', $body['tipoEquipo'] ?? '', true, 'Tipo de equipamiento'),
        'descripcion' => $descripcion,
        'responsable_vna_id' => resolver_id('responsables_vna', $body['responsableVNA'] ?? '', true, 'Responsable VNA'),
        'inspector_aayc_id' => resolver_id('inspectores_aayc', $body['inspector'] ?? '', false, 'Inspector AAyC'),
        'inspector_vna_id' => resolver_id('inspectores_vna', $body['inspectorVNA'] ?? '', false, 'Inspector VNA'),
        'nota_pedido' => trim($body['notaPedido'] ?? '') ?: null,
        'orden_servicio' => trim($body['ordenServicio'] ?? '') ?: null,
        'comentarios' => trim($body['comentarios'] ?? '') ?: null,
    ];
}

if ($method === 'GET') {
    if (isset($_GET['id'])) {
        $stmt = db()->prepare(SELECT_ACTIVIDAD . ' WHERE a.id_actividad = ?');
        $stmt->execute([(int)$_GET['id']]);
        $row = $stmt->fetch();
        if (!$row) {
            json_error('Actividad no encontrada.', 404);
        }
        json_response($row);
    }
    $rows = db()->query(SELECT_ACTIVIDAD . ' ORDER BY a.fecha_inicio DESC, a.id_actividad DESC')->fetchAll();
    json_response($rows);
}

require_writer();

if ($method === 'POST') {
    $user = current_user();
    $body = read_json_body();
    $datos = validar_y_resolver($body);

    $stmt = db()->prepare(
        'INSERT INTO actividades
            (tipo_mantenimiento, fecha_inicio, fecha_fin, cancelado, proveedor_id, tipo_equipamiento_id, descripcion,
             responsable_vna_id, inspector_aayc_id, inspector_vna_id, nota_pedido, orden_servicio,
             comentarios, creado_por)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $datos['tipo_mantenimiento'], $datos['fecha_inicio'], $datos['fecha_fin'], $datos['cancelado'],
        $datos['proveedor_id'], $datos['tipo_equipamiento_id'],
        $datos['descripcion'], $datos['responsable_vna_id'], $datos['inspector_aayc_id'], $datos['inspector_vna_id'],
        $datos['nota_pedido'], $datos['orden_servicio'], $datos['comentarios'], $user['id'],
    ]);

    $id = (int)db()->lastInsertId();
    $stmt = db()->prepare(SELECT_ACTIVIDAD . ' WHERE a.id_actividad = ?');
    $stmt->execute([$id]);
    json_response($stmt->fetch(), 201);
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    json_error('Falta el parámetro id.', 400);
}

if ($method === 'PUT') {
    $body = read_json_body();
    $datos = validar_y_resolver($body);

    $stmt = db()->prepare(
        'UPDATE actividades SET
            tipo_mantenimiento = ?, fecha_inicio = ?, fecha_fin = ?, cancelado = ?, proveedor_id = ?,
            tipo_equipamiento_id = ?, descripcion = ?,
            responsable_vna_id = ?, inspector_aayc_id = ?, inspector_vna_id = ?, nota_pedido = ?,
            orden_servicio = ?, comentarios = ?
         WHERE id_actividad = ?'
    );
    $stmt->execute([
        $datos['tipo_mantenimiento'], $datos['fecha_inicio'], $datos['fecha_fin'], $datos['cancelado'],
        $datos['proveedor_id'], $datos['tipo_equipamiento_id'],
        $datos['descripcion'], $datos['responsable_vna_id'], $datos['inspector_aayc_id'], $datos['inspector_vna_id'],
        $datos['nota_pedido'], $datos['orden_servicio'], $datos['comentarios'], $id,
    ]);

    if ($stmt->rowCount() === 0) {
        $check = db()->prepare('SELECT 1 FROM actividades WHERE id_actividad = ?');
        $check->execute([$id]);
        if (!$check->fetch()) {
            json_error('Actividad no encontrada.', 404);
        }
    }

    $stmt = db()->prepare(SELECT_ACTIVIDAD . ' WHERE a.id_actividad = ?');
    $stmt->execute([$id]);
    json_response($stmt->fetch());
}

if ($method === 'DELETE') {
    $stmt = db()->prepare('DELETE FROM actividades WHERE id_actividad = ?');
    $stmt->execute([$id]);
    if ($stmt->rowCount() === 0) {
        json_error('Actividad no encontrada.', 404);
    }
    json_response(['ok' => true]);
}

json_error('Método no soportado.', 405);
