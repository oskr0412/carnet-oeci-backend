<?php
// Configuración de base de datos para Railway y desarrollo local
if (isset($_ENV['MYSQLDATABASE'])) {
    // Producción en Railway
    $host = $_ENV['MYSQLHOST'];
    $port = $_ENV['MYSQLPORT'];
    $dbname = 'carnet_socios';  // Base de datos que importamos
    $username = $_ENV['MYSQLUSER'];
    $password = $_ENV['MYSQLPASSWORD'];
} else {
    // Desarrollo local con XAMPP
    $host = 'localhost';
    $port = 3306;
    $dbname = 'carnet_socios';
    $username = 'root';
    $password = '';
}

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión a la base de datos: ' . $e->getMessage()]);
    exit();
}
?>