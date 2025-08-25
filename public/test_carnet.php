<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once 'config/database.php';
require_once 'utils/response.php';
require_once 'utils/jwt.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Método no permitido', 405);
}

$payload = JWT::validateToken();

$stmt = $pdo->prepare("
    SELECT s.id, s.numero_socio, s.nombre, s.apellidos, s.email, s.telefono,
           s.fecha_ingreso, s.fecha_vencimiento, s.estado, s.foto_perfil, s.qr_code,
           s.documento_identidad, s.tipo_documento, s.direccion, s.ciudad,
           tm.nombre as tipo_membresia, tm.color_hex
    FROM socios s
    LEFT JOIN tipos_membresia tm ON s.tipo_membresia_id = tm.id
    WHERE s.id = ?
");
$stmt->execute([$payload['user_id']]);
$socio = $stmt->fetch();

if (!$socio) {
    Response::notFound('Socio no encontrado');
}

$carnet = [
    'numero_socio' => $socio['numero_socio'],
    'nombre_completo' => $socio['nombre'] . ' ' . $socio['apellidos'],
    'email' => $socio['email'],
    'telefono' => $socio['telefono'],
    'fecha_ingreso' => $socio['fecha_ingreso'],
    'fecha_vencimiento' => $socio['fecha_vencimiento'],
    'tipo_membresia' => $socio['tipo_membresia'],
    'color_membresia' => $socio['color_hex'],
    'estado' => $socio['estado'],
    'qr_code' => $socio['qr_code']
];

Response::success($carnet, 'Carnet obtenido exitosamente');
?>