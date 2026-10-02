<?php
header("Content-Type: application/json; charset=utf-8");
require_once "conexion.php";

/**
 * Endpoint que entrega las carpas con su estado para que mapa.js
 * pueda actualizar el plano sin recargar toda la página.
 */
$fecha = $_GET["fecha"] ?? date("Y-m-d");

$stmt = $conexion->prepare("
    SELECT c.id, c.numero, c.sector, c.pos_x, c.pos_y, c.fila, c.columna, c.activa,
           r.id reserva_id, r.fecha_inicio, r.fecha_fin, r.estado, r.tipo_reserva, r.costo_total,
           cl.nombre cliente_nombre, cl.dni cliente_dni, cl.telefono cliente_telefono
    FROM carpas c
    LEFT JOIN reservas r
      ON r.carpa_id = c.id
     AND r.estado IN ('pendiente','confirmada')
     AND ? BETWEEN r.fecha_inicio AND r.fecha_fin
    LEFT JOIN clientes cl ON cl.id = r.cliente_id
    WHERE c.activa = 1
    ORDER BY c.sector, c.numero
");
$stmt->bind_param("s", $fecha);
$stmt->execute();

$resultado = $stmt->get_result();
$datos = [];
while ($fila = $resultado->fetch_assoc()) {
    $estado = "disponible";
    if (!empty($fila["reserva_id"])) {
        $estado = ($fila["estado"] === "pendiente") ? "pendiente" : "ocupado";
    }

    $datos[] = [
        "id" => (int)$fila["id"],
        "numero" => $fila["numero"],
        "sector" => $fila["sector"],
        "x" => (int)$fila["pos_x"],
        "y" => (int)$fila["pos_y"],
        "fila" => (int)$fila["fila"],
        "columna" => (int)$fila["columna"],
        "estado" => $estado,
        "cliente" => $fila["cliente_nombre"],
        "dni" => $fila["cliente_dni"],
        "telefono" => $fila["cliente_telefono"],
        "fecha_inicio" => $fila["fecha_inicio"],
        "fecha_fin" => $fila["fecha_fin"],
        "tipo_reserva" => $fila["tipo_reserva"],
        "costo_total" => $fila["costo_total"],
        "reserva_id" => $fila["reserva_id"]
    ];
}

echo json_encode($datos, JSON_UNESCAPED_UNICODE);
?>
