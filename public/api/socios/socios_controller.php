<?php
global $pdo, $segments, $method;

switch ($method) {
    case 'GET':
        if (isset($segments[2])) {
            switch ($segments[2]) {
                case 'carnet':
                    obtenerCarnet();
                    break;
                case 'perfil':
                    obtenerPerfil();
                    break;
                default:
                    if (is_numeric($segments[2])) {
                        obtenerSocio($segments[2]);
                    } else {
                        Response::notFound();
                    }
            }
        } else {
            listarSocios();
        }
        break;
        
    case 'PUT':
        if (isset($segments[2])) {
            if ($segments[2] === 'perfil') {
                actualizarPerfil();
            } elseif (is_numeric($segments[2])) {
                actualizarSocio($segments[2]);
            } else {
                Response::notFound();
            }
        } else {
            Response::error('ID no especificado', 400);
        }
        break;
        
    case 'POST':
        if (isset($segments[2]) && $segments[2] === 'upload-foto') {
            subirFoto();
        } else {
            crearSocio();
        }
        break;
        
    case 'DELETE':
        if (isset($segments[2]) && is_numeric($segments[2])) {
            eliminarSocio($segments[2]);
        } else {
            Response::error('ID no especificado', 400);
        }
        break;
        
    default:
        Response::error('Método no permitido', 405);
}

function obtenerCarnet() {
    $payload = JWT::requireAuth(['socio']);
    global $pdo;
    
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
    
    // Verificar estado de membresía
    $hoy = date('Y-m-d');
    $estado_membresia = 'vigente';
    
    if ($socio['fecha_vencimiento'] < $hoy) {
        $estado_membresia = 'vencida';
    } elseif ($socio['fecha_vencimiento'] <= date('Y-m-d', strtotime('+30 days'))) {
        $estado_membresia = 'por_vencer';
    }
    
    $carnet = [
        'numero_socio' => $socio['numero_socio'],
        'nombre_completo' => $socio['nombre'] . ' ' . $socio['apellidos'],
        'nombre' => $socio['nombre'],
        'apellidos' => $socio['apellidos'],
        'email' => $socio['email'],
        'telefono' => $socio['telefono'],
        'documento_identidad' => $socio['documento_identidad'],
        'tipo_documento' => $socio['tipo_documento'],
        'fecha_ingreso' => $socio['fecha_ingreso'],
        'fecha_vencimiento' => $socio['fecha_vencimiento'],
        'tipo_membresia' => $socio['tipo_membresia'],
        'color_membresia' => $socio['color_hex'],
        'estado' => $socio['estado'],
        'estado_membresia' => $estado_membresia,
        'foto_perfil' => $socio['foto_perfil'],
        'qr_code' => $socio['qr_code'],
        'qr_data' => generateQRData($socio),
        'direccion' => $socio['direccion'],
        'ciudad' => $socio['ciudad']
    ];
    
    Response::success($carnet, 'Carnet obtenido exitosamente');
}

function obtenerPerfil() {
    $payload = JWT::requireAuth(['socio']);
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT s.*, tm.nombre as tipo_membresia, tm.color_hex
        FROM socios s
        LEFT JOIN tipos_membresia tm ON s.tipo_membresia_id = tm.id
        WHERE s.id = ?
    ");
    $stmt->execute([$payload['user_id']]);
    $socio = $stmt->fetch();
    
    if (!$socio) {
        Response::notFound('Socio no encontrado');
    }
    
    unset($socio['password_hash']); // No enviar password
    
    Response::success($socio, 'Perfil obtenido exitosamente');
}

function actualizarPerfil() {
    $payload = JWT::requireAuth(['socio']);
    global $pdo;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $campos_permitidos = [
        'telefono', 'direccion', 'ciudad', 'email'
    ];
    
    $campos_actualizar = [];
    $valores = [];
    
    foreach ($campos_permitidos as $campo) {
        if (isset($input[$campo])) {
            $campos_actualizar[] = "$campo = ?";
            $valores[] = $input[$campo];
        }
    }
    
    if (empty($campos_actualizar)) {
        Response::error('No hay campos para actualizar', 400);
    }
    
    $valores[] = $payload['user_id'];
    
    $sql = "UPDATE socios SET " . implode(', ', $campos_actualizar) . ", updated_at = NOW() WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($valores);
    
    // Log de actividad
    $stmt = $pdo->prepare("
        INSERT INTO logs_actividad (usuario_id, usuario_tipo, accion, descripcion)
        VALUES (?, 'socio', 'perfil_actualizado', 'Perfil actualizado por el socio')
    ");
    $stmt->execute([$payload['user_id']]);
    
    Response::success(null, 'Perfil actualizado exitosamente');
}

function listarSocios() {
    JWT::requireAuth(['super_admin', 'admin']);
    global $pdo;
    
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $estado = isset($_GET['estado']) ? $_GET['estado'] : '';
    
    $offset = ($page - 1) * $limit;
    
    $where_conditions = [];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(s.nombre LIKE ? OR s.apellidos LIKE ? OR s.numero_socio LIKE ? OR s.email LIKE ?)";
        $search_param = "%$search%";
        $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    }
    
    if (!empty($estado)) {
        $where_conditions[] = "s.estado = ?";
        $params[] = $estado;
    }
    
    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    // Consulta principal
    $sql = "
        SELECT s.id, s.numero_socio, s.nombre, s.apellidos, s.email, s.telefono,
               s.fecha_ingreso, s.fecha_vencimiento, s.estado, s.ultimo_acceso,
               tm.nombre as tipo_membresia, tm.color_hex
        FROM socios s
        LEFT JOIN tipos_membresia tm ON s.tipo_membresia_id = tm.id
        $where_clause
        ORDER BY s.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $socios = $stmt->fetchAll();
    
    // Contar total
    $count_sql = "SELECT COUNT(*) as total FROM socios s $where_clause";
    $count_params = array_slice($params, 0, -2); // Quitar limit y offset
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($count_params);
    $total = $stmt->fetch()['total'];
    
    Response::success([
        'socios' => $socios,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ]
    ], 'Socios obtenidos exitosamente');
}

function obtenerSocio($id) {
    JWT::requireAuth(['super_admin', 'admin']);
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT s.*, tm.nombre as tipo_membresia
        FROM socios s
        LEFT JOIN tipos_membresia tm ON s.tipo_membresia_id = tm.id
        WHERE s.id = ?
    ");
    $stmt->execute([$id]);
    $socio = $stmt->fetch();
    
    if (!$socio) {
        Response::notFound('Socio no encontrado');
    }
    
    unset($socio['password_hash']);
    
    Response::success($socio, 'Socio obtenido exitosamente');
}

function crearSocio() {
    $payload = JWT::requireAuth(['super_admin', 'admin']);
    global $pdo;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $required_fields = ['usuario', 'email', 'password', 'nombre', 'apellidos', 'tipo_membresia_id'];
    $errors = [];
    
    foreach ($required_fields as $field) {
        if (!isset($input[$field]) || empty(trim($input[$field]))) {
            $errors[$field] = ucfirst($field) . ' es requerido';
        }
    }
    
    if (!empty($errors)) {
        Response::validation($errors);
    }
    
    // Verificar usuario único
    $stmt = $pdo->prepare("SELECT id FROM socios WHERE usuario = ? OR email = ?");
    $stmt->execute([$input['usuario'], $input['email']]);
    if ($stmt->fetch()) {
        Response::error('Usuario o email ya existe', 400);
    }
    
    // Generar número de socio
    $numero_socio = generateNumeroSocio();
    
    $password_hash = password_hash($input['password'], PASSWORD_DEFAULT);
    $qr_code = md5($numero_socio . $input['email'] . time());
    
    $stmt = $pdo->prepare("
        INSERT INTO socios (
            numero_socio, usuario, email, password_hash, nombre, apellidos,
            documento_identidad, tipo_documento, fecha_nacimiento, genero,
            telefono, direccion, ciudad, pais, tipo_membresia_id,
            fecha_ingreso, fecha_vencimiento, qr_code, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $fecha_ingreso = date('Y-m-d');
    $fecha_vencimiento = date('Y-m-d', strtotime('+12 months'));
    
    $stmt->execute([
        $numero_socio,
        $input['usuario'],
        $input['email'],
        $password_hash,
        $input['nombre'],
        $input['apellidos'],
        $input['documento_identidad'] ?? null,
        $input['tipo_documento'] ?? 'cedula',
        $input['fecha_nacimiento'] ?? null,
        $input['genero'] ?? null,
        $input['telefono'] ?? null,
        $input['direccion'] ?? null,
        $input['ciudad'] ?? null,
        $input['pais'] ?? 'Colombia',
        $input['tipo_membresia_id'],
        $fecha_ingreso,
        $fecha_vencimiento,
        $qr_code,
        $payload['user_id']
    ]);
    
    $socio_id = $pdo->lastInsertId();
    
    // Log de actividad
    $stmt = $pdo->prepare("
        INSERT INTO logs_actividad (usuario_id, usuario_tipo, accion, descripcion)
        VALUES (?, 'admin', 'socio_creado', ?)
    ");
    $stmt->execute([$payload['user_id'], "Socio creado: $numero_socio"]);
    
    Response::success([
        'id' => $socio_id,
        'numero_socio' => $numero_socio
    ], 'Socio creado exitosamente', 201);
}

function actualizarSocio($id) {
    $payload = JWT::requireAuth(['super_admin', 'admin']);
    global $pdo;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $campos_permitidos = [
        'nombre', 'apellidos', 'email', 'telefono', 'direccion', 'ciudad',
        'documento_identidad', 'tipo_documento', 'fecha_nacimiento', 'genero',
        'tipo_membresia_id', 'estado', 'fecha_vencimiento'
    ];
    
    $campos_actualizar = [];
    $valores = [];
    
    foreach ($campos_permitidos as $campo) {
        if (isset($input[$campo])) {
            $campos_actualizar[] = "$campo = ?";
            $valores[] = $input[$campo];
        }
    }
    
    if (empty($campos_actualizar)) {
        Response::error('No hay campos para actualizar', 400);
    }
    
    $valores[] = $id;
    
    $sql = "UPDATE socios SET " . implode(', ', $campos_actualizar) . ", updated_at = NOW() WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($valores);
    
    if ($stmt->rowCount() === 0) {
        Response::notFound('Socio no encontrado');
    }
    
    Response::success(null, 'Socio actualizado exitosamente');
}

function eliminarSocio($id) {
    $payload = JWT::requireAuth(['super_admin']);
    global $pdo;
    
    $stmt = $pdo->prepare("DELETE FROM socios WHERE id = ?");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() === 0) {
        Response::notFound('Socio no encontrado');
    }
    
    Response::success(null, 'Socio eliminado exitosamente');
}

function subirFoto() {
    $payload = JWT::requireAuth(['socio']);
    global $pdo;
    
    if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
        Response::error('Error al subir archivo', 400);
    }
    
    $file = $_FILES['foto'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowed_types)) {
        Response::error('Tipo de archivo no permitido. Use JPG o PNG', 400);
    }
    
    if ($file['size'] > $max_size) {
        Response::error('Archivo muy grande. Máximo 5MB', 400);
    }
    
    $upload_dir = __DIR__ . '/../../uploads/fotos/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'socio_' . $payload['user_id'] . '_' . time() . '.' . $file_extension;
    $filepath = $upload_dir . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        Response::error('Error al guardar archivo', 500);
    }
    
    $photo_url = '/carnet_app/backend_php/uploads/fotos/' . $filename;
    
    // Actualizar base de datos
    $stmt = $pdo->prepare("UPDATE socios SET foto_perfil = ? WHERE id = ?");
    $stmt->execute([$photo_url, $payload['user_id']]);
    
    Response::success(['foto_url' => $photo_url], 'Foto subida exitosamente');
}

// Funciones auxiliares
function generateNumeroSocio() {
    global $pdo;
    
    $year = date('Y');
    $stmt = $pdo->prepare("SELECT MAX(CAST(SUBSTRING(numero_socio, 8) AS UNSIGNED)) as max_num FROM socios WHERE numero_socio LIKE ?");
    $stmt->execute(["SOC{$year}%"]);
    $result = $stmt->fetch();
    
    $next_num = ($result['max_num'] ?? 0) + 1;
    return "SOC{$year}" . str_pad($next_num, 4, '0', STR_PAD_LEFT);
}

function generateQRData($socio) {
    return json_encode([
        'numero_socio' => $socio['numero_socio'],
        'nombre' => $socio['nombre'] . ' ' . $socio['apellidos'],
        'estado' => $socio['estado'],
        'fecha_vencimiento' => $socio['fecha_vencimiento'],
        'hash' => $socio['qr_code']
    ]);
}