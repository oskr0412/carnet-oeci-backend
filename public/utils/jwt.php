<?php
class JWT {
    private static $secret_key = 'carnet_digital_secret_key_2024';
    private static $algorithm = 'HS256';
    private static $expiration_hours = 24;
    
    public static function encode($payload) {
        // Header
        $header = json_encode(['typ' => 'JWT', 'alg' => self::$algorithm]);
        $header = base64url_encode($header);
        
        // Payload con expiración
        $payload['exp'] = time() + (self::$expiration_hours * 3600);
        $payload['iat'] = time();
        $payload['jti'] = uniqid(); // JWT ID único
        
        $payload = json_encode($payload);
        $payload = base64url_encode($payload);
        
        // Signature
        $signature = hash_hmac('sha256', $header . "." . $payload, self::$secret_key, true);
        $signature = base64url_encode($signature);
        
        return $header . "." . $payload . "." . $signature;
    }
    
    public static function decode($jwt) {
        $parts = explode('.', $jwt);
        
        if (count($parts) !== 3) {
            return null;
        }
        
        list($header, $payload, $signature) = $parts;
        
        // Verificar signature
        $expected_signature = hash_hmac('sha256', $header . "." . $payload, self::$secret_key, true);
        $expected_signature = base64url_encode($expected_signature);
        
        if (!hash_equals($signature, $expected_signature)) {
            return null;
        }
        
        // Decodificar payload
        $payload = json_decode(base64url_decode($payload), true);
        
        // Verificar expiración
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null;
        }
        
        return $payload;
    }
    
    public static function getAuthToken() {
        $headers = getallheaders();
        
        if (isset($headers['Authorization'])) {
            $auth_header = $headers['Authorization'];
            if (preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
                return $matches[1];
            }
        }
        
        return null;
    }
    
    public static function validateToken() {
        $token = self::getAuthToken();
        
        if (!$token) {
            Response::unauthorized('Token no proporcionado');
        }
        
        $payload = self::decode($token);
        
        if (!$payload) {
            Response::unauthorized('Token inválido o expirado');
        }
        
        return $payload;
    }
    
    public static function requireAuth($allowed_roles = []) {
        $payload = self::validateToken();
        
        if (!empty($allowed_roles) && !in_array($payload['rol'], $allowed_roles)) {
            Response::forbidden('No tienes permisos para esta acción');
        }
        
        return $payload;
    }
}

function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode($data) {
    return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
}