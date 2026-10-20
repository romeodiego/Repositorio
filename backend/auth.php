<?php
require_once __DIR__ . '/helpers.php';
bootstrap_api();

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

if ($action === 'me' && $method === 'GET') {
    $user = current_user();
    if (!$user) {
        json_error('No autenticado.', 401);
    }
    json_response(['user' => $user]);
}

if ($action === 'login' && $method === 'POST') {
    $body = read_json_body();
    $usuario = trim($body['usuario'] ?? '');
    $password = (string)($body['password'] ?? '');

    if ($usuario === '' || $password === '') {
        json_error('Usuario y contraseña son obligatorios.', 400);
    }

    $stmt = db()->prepare(
        'SELECT id, nombre_usuario, password_hash, nombre_completo, rol, activo
         FROM usuarios WHERE nombre_usuario = ? LIMIT 1'
    );
    $stmt->execute([$usuario]);
    $row = $stmt->fetch();

    if (!$row || !$row['activo'] || !password_verify($password, $row['password_hash'])) {
        json_error('Usuario o contraseña incorrectos.', 401);
    }

    $user = [
        'id' => (int)$row['id'],
        'nombreUsuario' => $row['nombre_usuario'],
        'nombreCompleto' => $row['nombre_completo'],
        'rol' => $row['rol'],
    ];
    $_SESSION['user'] = $user;
    session_regenerate_id(true);
    $_SESSION['user'] = $user;
    json_response(['user' => $user]);
}

if ($action === 'logout' && $method === 'POST') {
    $_SESSION = [];
    session_destroy();
    json_response(['ok' => true]);
}

if ($action === 'change_password' && $method === 'POST') {
    $user = require_login();
    $body = read_json_body();
    $actual = (string)($body['passwordActual'] ?? '');
    $nueva = (string)($body['passwordNueva'] ?? '');

    if ($actual === '' || $nueva === '') {
        json_error('Debe indicar la contraseña actual y la nueva.', 400);
    }
    if (strlen($nueva) < 8) {
        json_error('La nueva contraseña debe tener al menos 8 caracteres.', 400);
    }

    $stmt = db()->prepare('SELECT password_hash FROM usuarios WHERE id = ? LIMIT 1');
    $stmt->execute([$user['id']]);
    $row = $stmt->fetch();
    if (!$row || !password_verify($actual, $row['password_hash'])) {
        json_error('La contraseña actual no es correcta.', 401);
    }

    $stmt = db()->prepare('UPDATE usuarios SET password_hash = ? WHERE id = ?');
    $stmt->execute([password_hash($nueva, PASSWORD_BCRYPT), $user['id']]);
    json_response(['ok' => true]);
}

json_error('Acción no encontrada.', 404);
