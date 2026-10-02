```php
<?php
/**
 * Conexion central a MySQL.
 * Compatible con XAMPP y Railway.
 */

$host = getenv("MYSQLHOST") ?: "localhost";
$usuario = getenv("MYSQLUSER") ?: "root";
$password = getenv("MYSQLPASSWORD") ?: "";
$bd = getenv("MYSQLDATABASE") ?: "balneario_reservas";
$puerto = getenv("MYSQLPORT") ?: 3306;

$conexion = new mysqli(
    $host,
    $usuario,
    $password,
    $bd,
    $puerto
);

if ($conexion->connect_errno) {
    die("Error de conexión a MySQL: " . $conexion->connect_error);
}

$conexion->set_charset("utf8mb4");
?>
```

Esto
