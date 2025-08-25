<?php
require_once 'config/database.php';

echo "Conexión a base de datos: EXITOSA<br>";
echo "Base de datos: " . DB_NAME . "<br>";

// Probar consulta
$stmt = $pdo->query("SELECT COUNT(*) as total FROM socios");
$result = $stmt->fetch();
echo "Total socios en BD: " . $result['total'] . "<br>";

$stmt = $pdo->query("SELECT COUNT(*) as total FROM administradores");
$result = $stmt->fetch();
echo "Total administradores en BD: " . $result['total'];
?>