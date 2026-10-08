<?php
require_once __DIR__ . '/helpers.php';
bootstrap_api();
require_admin();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $rows = db()->query(
        'SELECT id, nombre_usuario AS nombreUsuario, nombre_completo AS nombreCompleto, rol, activo
         FROM usuarios ORDER BY nombre_completo'
    )->fetchAll();
    json_response($rows);
}

if ($method === 'POST') {
    $body = read_json_body();
    $usuario = trim($body['nombreUsuario'] ?? '');
    $nombreCompleto = trim($body['nombreCompleto'] ?? '');
    $password = (string)($body['password'] ?? '');
    $rol = $body['rol'] ?? 'editor';

    if ($usuario === '' || $nombreCompleto === '' || $password === '') {
        json_error('Usuario, nombre completo y contraseña son obligatorios.', 400);
    }
    if (strlen($password) < 8) {
        json_error('La contraseña debe tener al menos 8 caracteres.', 400);
    }
    if (!in_array($rol, ['admin', 'editor'], true)) {
        json_error('Rol inválido.', 400);
    }

    try {
        $stmt = db()->prepare(
            'INSERT INTO usuarios (nombre_usuario, password_hash, nombre_completo, rol)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$usuario, password_hash($password, PASSWORD_BCRYPT), $nombreCompleto, $rol]);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            json_error('Ya existe un usuario con ese nombre de usuario.', 409);
        }
        throw $e;
    }

    json_response(['id' => (int)db()->lastInsertId()], 201);
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    json_error('Falta el parámetro id.', 400);
}

if ($method === 'PUT') {
    $body = read_json_body();
    $sets = [];
    $params = [];

    if (isset($body['nombreCompleto']) && trim($body['nombreCompleto']) !== '') {
        $sets[] = 'nombre_completo = ?';
        $params[] = trim($body['nombreCompleto']);
    }
    if (isset($body['rol']) && in_array($body['rol'], ['admin', 'editor'], true)) {
        $sets[] = 'rol = ?';
        $params[] = $body['rol'];
    }
    if (isset($body['activo'])) {
        $sets[] = 'activo = ?';
        $params[] = $body['activo'] ? 1 : 0;
    }
    if (!empty($body['password'])) {
        if (strlen($body['password']) < 8) {
            json_error('La contraseña debe tener al menos 8 caracteres.', 400);
        }
        $sets[] = 'password_hash = ?';
        $params[] = password_hash($body['password'], PASSWORD_BCRYPT);
    }

    if (empty($sets)) {
        json_error('No hay cambios para aplicar.', 400);
    }

    $params[] = $id;
    $stmt = db()->prepare('UPDATE usuarios SET ' . implode(', ', $sets) . ' WHERE id = ?');
    $stmt->execute($params);
    json_response(['ok' => true]);
}

if ($method === 'DELETE') {
    // Baja lógica: se desactiva en lugar de borrar la fila, para no romper
    // la referencia "creado_por" de las actividades ya cargadas por ese usuario.
    $stmt = db()->prepare('UPDATE usuarios SET activo = 0 WHERE id = ?');
    $stmt->execute([$id]);
    json_response(['ok' => true]);
}

json_error('Método no soportado.', 405);
