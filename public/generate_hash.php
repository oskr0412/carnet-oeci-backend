<?php
$password = "password";
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "Password: " . $password . "<br>";
echo "Hash generado: " . $hash . "<br>";

// Verificar el hash que tienes en BD
$stored_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
echo "<br>Hash en BD: " . $stored_hash . "<br>";

// Probar verificación
if (password_verify($password, $stored_hash)) {
    echo "<br>✅ El password 'password' COINCIDE con el hash en BD";
} else {
    echo "<br>❌ El password 'password' NO coincide con el hash en BD";
}

// Probar con 123456
if (password_verify("123456", $stored_hash)) {
    echo "<br>✅ El password '123456' COINCIDE con el hash en BD";
} else {
    echo "<br>❌ El password '123456' NO coincide con el hash en BD";
}
?>