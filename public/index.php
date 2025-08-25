<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'config/database.php';
require_once 'utils/response.php';
require_once 'utils/jwt.php';

// Obtener la ruta solicitada
$request_uri = $_SERVER['REQUEST_URI'];
$base_path = '/carnet_app/backend_php';
$route = str_replace($base_path, '', $request_uri);
$route = parse_url($route, PHP_URL_PATH);

// Dividir la ruta en segmentos
$segments = array_filter(explode('/', $route));
$segments = array_values($segments);

$method = $_SERVER['REQUEST_METHOD'];

// Router principal
try {
    if (empty($segments)) {
        Response::success(['message' => 'API Carnet Digital v1.0', 'timestamp' => date('Y-m-d H:i:s')]);
    }
    
    switch ($segments[0]) {
        case 'api':
            if (!isset($segments[1])) {
                Response::error('Endpoint no especificado', 400);
            }
            
            switch ($segments[1]) {
                case 'auth':
                    require_once 'api/auth/auth_controller.php';
                    break;
                    
                case 'socios':
                    require_once 'api/socios/socios_controller.php';
                    break;
                    
                case 'admin':
                    require_once 'api/admin/admin_controller.php';
                    break;
                    
                default:
                    Response::error('Endpoint no encontrado', 404);
            }
            break;
            
        default:
            Response::error('Ruta no encontrada', 404);
    }
    
} catch (Exception $e) {
    Response::error('Error interno del servidor: ' . $e->getMessage(), 500);
}