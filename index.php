<?php
$titulo = "Dashboard";
require_once "conexion.php";

$totalCarpas = (int)$conexion->query("SELECT COUNT(*) c FROM carpas WHERE activa = 1")->fetch_assoc()["c"];
$disponibles = (int)$conexion->query("
    SELECT COUNT(*) c
    FROM carpas c
    WHERE c.activa = 1
      AND NOT EXISTS (
          SELECT 1 FROM reservas r
          WHERE r.carpa_id = c.id
            AND r.estado IN ('pendiente','confirmada')
            AND CURDATE() BETWEEN r.fecha_inicio AND r.fecha_fin
      )
")->fetch_assoc()["c"];

$ocupadas = $totalCarpas - $disponibles;
$ocupacion = $totalCarpas > 0 ? round(($ocupadas / $totalCarpas) * 100, 1) : 0;

$ingresos = (float)$conexion->query("
    SELECT COALESCE(SUM(costo_total), 0) total
    FROM reservas
    WHERE estado IN ('pendiente','confirmada')
      AND MONTH(fecha_inicio) = MONTH(CURDATE())
      AND YEAR(fecha_inicio) = YEAR(CURDATE())
")->fetch_assoc()["total"];

$reservasActivas = (int)$conexion->query("
    SELECT COUNT(*) c FROM reservas
    WHERE estado IN ('pendiente','confirmada') AND fecha_fin >= CURDATE()
")->fetch_assoc()["c"];

$ultimas = $conexion->query("
    SELECT r.id, c.numero, cl.nombre, r.fecha_inicio, r.fecha_fin, r.estado, r.costo_total
    FROM reservas r
    INNER JOIN carpas c ON c.id = r.carpa_id
    INNER JOIN clientes cl ON cl.id = r.cliente_id
    ORDER BY r.creado_en DESC
    LIMIT 8
");

require_once "header.php";
?>

<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon">⛺</div>
            <div class="text-muted">Carpas totales</div>
            <div class="stat-number"><?= $totalCarpas ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon">🟢</div>
            <div class="text-muted">Carpas libres hoy</div>
            <div class="stat-number"><?= $disponibles ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon">📈</div>
            <div class="text-muted">Ocupación diaria</div>
            <div class="stat-number"><?= $ocupacion ?>%</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon">💰</div>
            <div class="text-muted">Ingresos del mes</div>
            <div class="stat-number">$<?= number_format($ingresos, 2, ',', '.') ?></div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card-soft">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="section-title mb-1">Últimas reservas</h2>
                    <div class="text-muted small">Reservas registradas recientemente</div>
                </div>
                <a href="reservas.php" class="btn btn-primary btn-sm">Ver todas</a>
            </div>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Carpa</th>
                            <th>Cliente</th>
                            <th>Desde</th>
                            <th>Hasta</th>
                            <th>Estado</th>
                            <th>Costo</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($fila = $ultimas->fetch_assoc()): ?>
                        <tr>
                            <td><strong>#<?= htmlspecialchars($fila["numero"]) ?></strong></td>
                            <td><?= htmlspecialchars($fila["nombre"]) ?></td>
                            <td><?= date("d/m/Y", strtotime($fila["fecha_inicio"])) ?></td>
                            <td><?= date("d/m/Y", strtotime($fila["fecha_fin"])) ?></td>
                            <td><span class="status status-<?= htmlspecialchars($fila["estado"]) ?>"><?= ucfirst($fila["estado"]) ?></span></td>
                            <td>$<?= number_format($fila["costo_total"], 2, ',', '.') ?></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card-soft h-100">
            <h2 class="section-title">Acciones rápidas</h2>
            <div class="d-grid gap-2 mt-3">
                <a href="reservas.php?accion=nueva" class="btn btn-primary">➕ Nueva reserva</a>
                <a href="carpas.php?accion=nueva" class="btn btn-outline-primary">⛺ Nueva carpa</a>
                <a href="clientes.php?accion=nuevo" class="btn btn-outline-secondary">👤 Nuevo cliente</a>
                <a href="mapa.php" class="btn btn-outline-success">🗺️ Abrir mapa</a>
            </div>
            <hr>
            <div class="small text-muted">Reservas activas</div>
            <div class="fs-3 fw-bold"><?= $reservasActivas ?></div>
        </div>
    </div>
</div>

<?php require_once "footer.php"; ?>
