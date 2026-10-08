<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

function send_cors_headers(): void {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
    $allowed = CORS_ALLOWED_ORIGIN === '*' ? $origin : CORS_ALLOWED_ORIGIN;
    header('Access-Control-Allow-Origin: ' . $allowed);
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    header('Content-Type: application/json; charset=utf-8');
}

function start_api_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function bootstrap_api(): void {
    send_cors_headers();
    start_api_session();
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

function json_response($data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function json_error(string $message, int $status = 400): void {
    json_response(['error' => $message], $status);
}

function read_json_body(): array {
    $raw = file_get_contents('php://input');
    if ($raw === '' || $raw === false) {
        return [];
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        json_error('Cuerpo de la solicitud inválido (se esperaba JSON).', 400);
    }
    return $data;
}

function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function require_login(): array {
    $user = current_user();
    if (!$user) {
        json_error('No autenticado. Inicie sesión.', 401);
    }
    return $user;
}

function require_admin(): array {
    $user = require_login();
    if ($user['rol'] !== 'admin') {
        json_error('No autorizado. Esta acción requiere rol de administrador.', 403);
    }
    return $user;
}
