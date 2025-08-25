<?php
class Response {
    
    public static function success($data = null, $message = 'Operación exitosa', $code = 200) {
        http_response_code($code);
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    public static function error($message = 'Error en la operación', $code = 400, $details = null) {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'message' => $message,
            'details' => $details,
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    public static function unauthorized($message = 'No autorizado') {
        self::error($message, 401);
    }
    
    public static function forbidden($message = 'Prohibido') {
        self::error($message, 403);
    }
    
    public static function notFound($message = 'Recurso no encontrado') {
        self::error($message, 404);
    }
    
    public static function validation($errors) {
        self::error('Errores de validación', 422, $errors);
    }
}