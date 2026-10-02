<?php
session_start();
require_once "conexion.php";

if (isset($_SESSION["admin_id"])) {
    header("Location: index.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $usuario = trim($_POST["usuario"] ?? "");
    $password = $_POST["password"] ?? "";

    $stmt = $conexion->prepare("SELECT id, usuario, password, nombre FROM administradores WHERE usuario = ? AND activo = 1 LIMIT 1");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $admin = $resultado->fetch_assoc();

    if ($admin && password_verify($password, $admin["password"])) {
        $_SESSION["admin_id"] = $admin["id"];
        $_SESSION["admin_usuario"] = $admin["usuario"];
        $_SESSION["admin_nombre"] = $admin["nombre"];

        header("Location: index.php");
        exit;
    }

    $error = "Usuario o contraseña incorrectos.";
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acceso | Balneario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="estilos.css" rel="stylesheet">
</head>
<body class="login-body">
<div class="login-card">
    <div class="text-center mb-4">
        <div class="brand-logo mx-auto mb-3">🏖️</div>
        <h1 class="h3 fw-bold">Administración del Balneario</h1>
        <p class="text-muted mb-0">Gestión de carpas y reservas</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" autocomplete="off">
        <div class="mb-3">
            <label class="form-label">Usuario</label>
            <input type="text" name="usuario" class="form-control form-control-lg" required autofocus>
        </div>
        <div class="mb-4">
            <label class="form-label">Contraseña</label>
            <input type="password" name="password" class="form-control form-control-lg" required>
        </div>
        <button class="btn btn-primary btn-lg w-100">Ingresar</button>
    </form>

    <div class="small text-muted mt-4">
        Usuario inicial: <strong>admin</strong><br>
        Contraseña inicial: <strong>admin123</strong><br>
        Cambialos en producción.
    </div>
</div>
</body>
</html>
