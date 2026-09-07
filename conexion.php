<?php
/**
 * Conexion central a MySQL.
 * Ajustá estos datos según tu instalación de XAMPP.
 */
$host = "localhost";
$usuario = "root";
$password = "";
$bd = "balneario_reservas";

$conexion = new mysqli($host, $usuario, $password, $bd);

if ($conexion->connect_errno) {
    die("Error de conexión a MySQL: " . $conexion->connect_error);
}

$conexion->set_charset("utf8mb4");
?>
