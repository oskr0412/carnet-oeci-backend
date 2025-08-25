<?php
global $pdo, $segments, $method;

switch ($method) {
    case 'POST':
        if (isset($segments[2])) {
            switch ($segments[2]) {
                case 'login':
                    login();
                    break;
                case 'logout':
                    logout();
                    break;
                default:
                    Response::notFound();
            }
        } else {
            Response::error('Acción no especificada', 400);
        }
        break;
        
    case 'GET':
        if (isset($segments[2]) && $segments[2] === 'verify') {
            verifyToken();
        } else {
            Response::notFound();
        }
        break;
        
    default:
        Response::error('Método no permitido', 405);
}

function login() {
    global $pdo;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['usuario']) || !isset($input['password'])) {
        Response::validation(['usuario' => 'Usuario requerido', 'password' => 'Contraseña requerida']);
    }
    
    $usuario = trim($input['usuario']);
    $password = $input['password'];
    
    // Buscar en tabla socios
    $stmt = $pdo->prepare("
        SELECT id, usuario, email, password_hash, nombre, apellidos, 
               numero_socio, estado, tipo_membresia_id, fecha_vencimiento,
               'socio' as tipo_usuario
        FROM socios 
        WHERE usuario = ? OR email = ?
    ");
    $stmt->execute([$usuario, $usuario]);
    $user = $stmt->fetch();
    
    // Si no es socio, buscar en administradores
    if (!$user) {
        $stmt = $pdo->prepare("
            SELECT id, usuario, email, password_hash, nombre, apellidos, 
                   rol, activo,
                   'admin' as tipo_usuario
            FROM administradores 
            WHERE usuario = ? OR email = ?
        ");
        $stmt->execute([$usuario, $usuario]);
        $user = $stmt->fetch();
    }
    
    if (!$user || !password_verify($password, $user['password_hash'])) {
        Response::error('Credenciales incorrectas', 401);
    }
    
    // Verificar estado del usuario
    if ($user['tipo_usuario'] === 'socio' && $user['estado'] !== 'activo') {
        Response::error('Tu cuenta está ' . $user['estado'], 403);
    }
    
    if ($user['tipo_usuario'] === 'admin' && !$user['activo']) {
        Response::error('Tu cuenta está desactivada', 403);
    }
    
    // Crear payload del JWT
    $payload = [
        'user_id' => $user['id'],
        'usuario' => $user['usuario'],
        'email' => $user['email'],
        'nombre' => $user['nombre'],
        'apellidos' => $user['apellidos'],
        'tipo_usuario' => $user['tipo_usuario']
    ];
    
    if ($user['tipo_usuario'] === 'socio') {
        $payload['numero_socio'] = $user['numero_socio'];
        $payload['tipo_membresia_id'] = $user['tipo_membresia_id'];
        $payload['fecha_vencimiento'] = $user['fecha_vencimiento'];
    } else {
        $payload['rol'] = $user['rol'];
    }
    
    // Generar token
    $token = JWT::encode($payload);
    
    // Guardar sesión en base de datos
    $jti = $payload['jti'];
    $stmt = $pdo->prepare("
        INSERT INTO sesiones_jwt (token_jti, usuario_id, usuario_tipo, ip_address, user_agent, fecha_expiracion)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $jti,
        $user['id'],
        $user['tipo_usuario'],
        $_SERVER['REMOTE_ADDR'] ?? '',
        $_SERVER['HTTP_USER_AGENT'] ?? '',
        date('Y-m-d H:i:s', time() + (24 * 3600))
    ]);
    
    // Actualizar último acceso
    if ($user['tipo_usuario'] === 'socio') {
        $stmt = $pdo->prepare("UPDATE socios SET ultimo_acceso = NOW() WHERE id = ?");
    } else {
        $stmt = $pdo->prepare("UPDATE administradores SET ultimo_acceso = NOW() WHERE id = ?");
    }
    $stmt->execute([$user['id']]);
    
    // Log de actividad
    $stmt = $pdo->prepare("
        INSERT INTO logs_actividad (usuario_id, usuario_tipo, accion, descripcion, ip_address)
        VALUES (?, ?, 'login', 'Inicio de sesión exitoso', ?)
    ");
    $stmt->execute([$user['id'], $user['tipo_usuario'], $_SERVER['REMOTE_ADDR'] ?? '']);
    
    Response::success([
        'token' => $token,
        'user' => [
            'id' => $user['id'],
            'usuario' => $user['usuario'],
            'email' => $user['email'],
            'nombre' => $user['nombre'],
            'apellidos' => $user['apellidos'],
            'tipo_usuario' => $user['tipo_usuario'],
            'numero_socio' => $user['numero_socio'] ?? null,
            'rol' => $user['rol'] ?? null
        ]
    ], 'Login exitoso');
}

function logout() {
    $payload = JWT::validateToken();
    global $pdo;
    
    // Desactivar token en base de datos
    if (isset($payload['jti'])) {
        $stmt = $pdo->prepare("UPDATE sesiones_jwt SET activo = FALSE WHERE token_jti = ?");
        $stmt->execute([$payload['jti']]);
    }
    
    // Log de actividad
    $stmt = $pdo->prepare("
        INSERT INTO logs_actividad (usuario_id, usuario_tipo, accion, descripcion, ip_address)
        VALUES (?, ?, 'logout', 'Cierre de sesión', ?)
    ");
    $stmt->execute([$payload['user_id'], $payload['tipo_usuario'], $_SERVER['REMOTE_ADDR'] ?? '']);
    
    Response::success(null, 'Sesión cerrada exitosamente');
}

function verifyToken() {
    $payload = JWT::validateToken();
    global $pdo;
    
    // Verificar que el token esté activo en la base de datos
    if (isset($payload['jti'])) {
        $stmt = $pdo->prepare("SELECT activo FROM sesiones_jwt WHERE token_jti = ?");
        $stmt->execute([$payload['jti']]);
        $session = $stmt->fetch();
        
        if (!$session || !$session['activo']) {
            Response::unauthorized('Sesión inválida');
        }
    }
    
    Response::success([
        'user' => [
            'id' => $payload['user_id'],
            'usuario' => $payload['usuario'],
            'email' => $payload['email'],
            'nombre' => $payload['nombre'],
            'apellidos' => $payload['apellidos'],
            'tipo_usuario' => $payload['tipo_usuario'],
            'numero_socio' => $payload['numero_socio'] ?? null,
            'rol' => $payload['rol'] ?? null
        ]
    ], 'Token válido');
}