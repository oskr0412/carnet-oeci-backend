<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config/database.php';
require_once 'utils/response.php';
require_once 'utils/jwt.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Método no permitido', 405);
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['usuario']) || !isset($input['password'])) {
    Response::validation(['usuario' => 'Usuario requerido', 'password' => 'Contraseña requerida']);
}

$usuario = trim($input['usuario']);
$password = $input['password'];

// Buscar socio
$stmt = $pdo->prepare("SELECT id, usuario, email, password_hash, nombre, apellidos, numero_socio, estado FROM socios WHERE usuario = ? OR email = ?");
$stmt->execute([$usuario, $usuario]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    Response::error('Credenciales incorrectas', 401);
}

if ($user['estado'] !== 'activo') {
    Response::error('Cuenta ' . $user['estado'], 403);
}

$payload = [
    'user_id' => $user['id'],
    'usuario' => $user['usuario'],
    'email' => $user['email'],
    'nombre' => $user['nombre'],
    'apellidos' => $user['apellidos'],
    'numero_socio' => $user['numero_socio'],
    'tipo_usuario' => 'socio'
];

$token = JWT::encode($payload);

Response::success([
    'token' => $token,
    'user' => $payload
], 'Login exitoso');
?>