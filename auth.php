<?php
/**
 * Seguridad básica de sesión.
 * Todos los módulos administrativos incluyen este archivo.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}
?>
