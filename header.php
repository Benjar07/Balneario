<?php
require_once "auth.php";
$pagina = basename($_SERVER["PHP_SELF"]);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Balneario - Administración</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="estilos.css" rel="stylesheet">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <div class="brand-box">
            <div class="brand-logo">🏖️</div>
            <div>
                <div class="brand-title">Balneario</div>
                <div class="brand-subtitle">Administración</div>
            </div>
        </div>

        <nav class="nav flex-column gap-1">
            <a class="nav-link <?= $pagina === 'index.php' ? 'active' : '' ?>" href="index.php">📊 Dashboard</a>
            <a class="nav-link <?= $pagina === 'mapa.php' ? 'active' : '' ?>" href="mapa.php">🗺️ Mapa de carpas</a>
            <a class="nav-link <?= $pagina === 'carpas.php' ? 'active' : '' ?>" href="carpas.php">⛺ Carpas</a>
            <a class="nav-link <?= $pagina === 'reservas.php' ? 'active' : '' ?>" href="reservas.php">📅 Reservas</a>
            <a class="nav-link <?= $pagina === 'clientes.php' ? 'active' : '' ?>" href="clientes.php">👤 Clientes</a>
            <a class="nav-link <?= $pagina === 'configurador.php' ? 'active' : '' ?>" href="configurador.php">⚙️ Configurador</a>
        </nav>

        <div class="sidebar-footer">
            <div class="small text-muted mb-2">Sesión iniciada como</div>
            <strong><?= htmlspecialchars($_SESSION["admin_nombre"] ?? $_SESSION["admin_usuario"]) ?></strong>
            <a href="logout.php" class="btn btn-outline-danger btn-sm w-100 mt-3">Cerrar sesión</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <button class="btn btn-light d-lg-none" id="btnMenu">☰</button>
            <div>
                <h1 class="page-title"><?= htmlspecialchars($titulo ?? 'Panel principal') ?></h1>
                <div class="text-muted small"><?= date("d/m/Y") ?></div>
            </div>
        </header>

        <div class="content-wrap">
