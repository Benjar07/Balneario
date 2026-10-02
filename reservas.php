<?php
$titulo = "Gestión de reservas";
require_once "conexion.php";

$mensaje = "";
$error = "";

function calcularCostoReserva(mysqli $conexion, int $carpaId, string $tipo, string $inicio, string $fin): float {
    $stmt = $conexion->prepare("SELECT precio_diario, precio_quincenal, precio_mensual, precio_temporada FROM carpas WHERE id=?");
    $stmt->bind_param("i", $carpaId);
    $stmt->execute();
    $carpa = $stmt->get_result()->fetch_assoc();

    if (!$carpa) return 0;

    $dias = max(1, (int)((strtotime($fin) - strtotime($inicio)) / 86400) + 1);

    switch ($tipo) {
        case "quincenal":
            return (float)$carpa["precio_quincenal"];

        case "mensual":
            return (float)$carpa["precio_mensual"];

        case "temporada":
            return (float)$carpa["precio_temporada"];

        default:
            return (float)$carpa["precio_diario"] * $dias;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $operacion = $_POST["operacion"] ?? "";
    $id = (int)($_POST["id"] ?? 0);
    $carpaId = (int)($_POST["carpa_id"] ?? 0);
    $clienteId = (int)($_POST["cliente_id"] ?? 0);
    $tipo = $_POST["tipo_reserva"] ?? "diaria";
    $inicio = $_POST["fecha_inicio"] ?? "";
    $fin = $_POST["fecha_fin"] ?? "";
    $estado = $_POST["estado"] ?? "pendiente";
    $observaciones = trim($_POST["observaciones"] ?? "");

    // Monto ingresado manualmente
    $montoIngresado = str_replace(",", ".", trim($_POST["costo_total"] ?? ""));

    if ($montoIngresado === "" || !is_numeric($montoIngresado)) {
        $montoIngresado = 0;
    }

    $costo = (float)$montoIngresado;

    if ($operacion === "guardar") {

        if (!$inicio || !$fin || $fin < $inicio) {

            $error = "El rango de fechas no es válido.";

        } elseif ($carpaId <= 0) {

            $error = "Debés seleccionar una carpa.";

        } elseif ($clienteId <= 0) {

            $error = "Debés seleccionar un cliente.";

        } elseif ($costo < 0) {

            $error = "El monto no puede ser negativo.";

        } else {

            // Validamos superposición para evitar dos reservas
            // confirmadas/pendientes simultáneas.
            $stmt = $conexion->prepare("
                SELECT COUNT(*) c FROM reservas
                WHERE carpa_id=? AND id<>?
                  AND estado IN ('pendiente','confirmada')
                  AND fecha_inicio <= ? AND fecha_fin >= ?
            ");

            $stmt->bind_param("iiss", $carpaId, $id, $fin, $inicio);
            $stmt->execute();

            $solapadas = (int)$stmt->get_result()->fetch_assoc()["c"];

            if ($solapadas > 0) {

                $error = "La carpa ya tiene una reserva en ese rango.";

            } else {

                if ($id > 0) {

                    $stmt = $conexion->prepare("
                        UPDATE reservas
                        SET carpa_id=?,
                            cliente_id=?,
                            tipo_reserva=?,
                            fecha_inicio=?,
                            fecha_fin=?,
                            estado=?,
                            costo_total=?,
                            observaciones=?
                        WHERE id=?
                    ");

                    $stmt->bind_param(
                        "iissssdsi",
                        $carpaId,
                        $clienteId,
                        $tipo,
                        $inicio,
                        $fin,
                        $estado,
                        $costo,
                        $observaciones,
                        $id
                    );

                    if ($stmt->execute()) {
                        $mensaje = "Reserva actualizada correctamente.";
                    } else {
                        $error = "No se pudo actualizar la reserva.";
                    }

                } else {

                    $stmt = $conexion->prepare("
                        INSERT INTO reservas
                        (
                            carpa_id,
                            cliente_id,
                            tipo_reserva,
                            fecha_inicio,
                            fecha_fin,
                            estado,
                            costo_total,
                            observaciones
                        )
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");

                    $stmt->bind_param(
                        "iissssds",
                        $carpaId,
                        $clienteId,
                        $tipo,
                        $inicio,
                        $fin,
                        $estado,
                        $costo,
                        $observaciones
                    );

                    if ($stmt->execute()) {
                        $mensaje = "Reserva creada correctamente.";
                    } else {
                        $error = "No se pudo crear la reserva.";
                    }
                }
            }
        }
    }

    if ($operacion === "eliminar" && $id > 0) {

        $stmt = $conexion->prepare("DELETE FROM reservas WHERE id=?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $mensaje = "Reserva eliminada.";
        } else {
            $error = "No se pudo eliminar la reserva.";
        }
    }
}


$editar = null;

if (isset($_GET["editar"])) {

    $id = (int)$_GET["editar"];

    $stmt = $conexion->prepare("SELECT * FROM reservas WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $editar = $stmt->get_result()->fetch_assoc();
}


$carpas = $conexion->query("
    SELECT
        id,
        numero,
        sector,
        precio_diario,
        precio_quincenal,
        precio_mensual,
        precio_temporada
    FROM carpas
    WHERE activa=1
    ORDER BY sector, numero
");


$clientes = $conexion->query("
    SELECT id, nombre, dni
    FROM clientes
    ORDER BY nombre
");


$lista = $conexion->query("
    SELECT
        r.*,
        c.numero,
        c.sector,
        cl.nombre,
        cl.dni,
        cl.telefono
    FROM reservas r
    INNER JOIN carpas c ON c.id = r.carpa_id
    INNER JOIN clientes cl ON cl.id = r.cliente_id
    ORDER BY r.fecha_inicio DESC, r.id DESC
");


require_once "header.php";
?>


<?php if ($mensaje): ?>

    <div class="alert alert-success">
        <?= htmlspecialchars($mensaje) ?>
    </div>

<?php endif; ?>


<?php if ($error): ?>

    <div class="alert alert-danger">
        <?= htmlspecialchars($error) ?>
    </div>

<?php endif; ?>


<div class="row g-4">

    <div class="col-xl-4">

        <div class="card-soft">

            <h2 class="section-title">
                <?= $editar ? "Editar reserva" : "Nueva reserva" ?>
            </h2>

            <form method="post" id="formReserva">

                <input
                    type="hidden"
                    name="operacion"
                    value="guardar"
                >

                <input
                    type="hidden"
                    name="id"
                    value="<?= (int)($editar["id"] ?? 0) ?>"
                >


                <!-- CLIENTE -->

                <div class="mb-3">

                    <label class="form-label">
                        Cliente
                    </label>

                    <select
                        class="form-select"
                        name="cliente_id"
                        required
                    >

                        <option value="">
                            Seleccionar...
                        </option>

                        <?php while ($c = $clientes->fetch_assoc()): ?>

                            <option
                                value="<?= $c["id"] ?>"
                                <?= ((int)($editar["cliente_id"] ?? 0) === (int)$c["id"]) ? "selected" : "" ?>
                            >

                                <?= htmlspecialchars($c["nombre"]) ?>
                                -
                                <?= htmlspecialchars($c["dni"]) ?>

                            </option>

                        <?php endwhile; ?>

                    </select>

                </div>


                <!-- CARPA -->

                <div class="mb-3">

                    <label class="form-label">
                        Carpa
                    </label>

                    <select
                        class="form-select"
                        name="carpa_id"
                        id="carpaId"
                        required
                    >

                        <option value="">
                            Seleccionar...
                        </option>

                        <?php while ($c = $carpas->fetch_assoc()): ?>

                            <option
                                value="<?= $c["id"] ?>"
                                data-diario="<?= $c["precio_diario"] ?>"
                                data-quincenal="<?= $c["precio_quincenal"] ?>"
                                data-mensual="<?= $c["precio_mensual"] ?>"
                                data-temporada="<?= $c["precio_temporada"] ?>"
                                <?= ((int)($editar["carpa_id"] ?? 0) === (int)$c["id"]) ? "selected" : "" ?>
                            >

                                #<?= htmlspecialchars($c["numero"]) ?>
                                -
                                <?= htmlspecialchars($c["sector"]) ?>

                            </option>

                        <?php endwhile; ?>

                    </select>

                </div>


                <!-- TIPO -->

                <div class="mb-3">

                    <label class="form-label">
                        Tipo de reserva
                    </label>

                    <select
                        class="form-select"
                        name="tipo_reserva"
                        id="tipoReserva"
                    >

                        <?php foreach (
                            [
                                "diaria" => "Diaria",
                                "quincenal" => "Quincenal",
                                "mensual" => "Mensual",
                                "temporada" => "Temporada completa"
                            ] as $v => $txt
                        ): ?>

                            <option
                                value="<?= $v ?>"
                                <?= (($editar["tipo_reserva"] ?? "diaria") === $v) ? "selected" : "" ?>
                            >

                                <?= $txt ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- FECHAS -->

                <div class="row">

                    <div class="col-6 mb-3">

                        <label class="form-label">
                            Inicio
                        </label>

                        <input
                            type="date"
                            class="form-control"
                            name="fecha_inicio"
                            id="fechaInicio"
                            required
                            value="<?= htmlspecialchars($editar["fecha_inicio"] ?? date("Y-m-d")) ?>"
                        >

                    </div>


                    <div class="col-6 mb-3">

                        <label class="form-label">
                            Fin
                        </label>

                        <input
                            type="date"
                            class="form-control"
                            name="fecha_fin"
                            id="fechaFin"
                            required
                            value="<?= htmlspecialchars($editar["fecha_fin"] ?? date("Y-m-d")) ?>"
                        >

                    </div>

                </div>


                <!-- COSTO CALCULADO -->

                <div class="estimate-box mb-3">

                    <div class="small text-muted">
                        Costo calculado
                    </div>

                    <div
                        class="fs-3 fw-bold"
                        id="costoCalculado"
                    >
                        $0,00
                    </div>

                </div>


                <!-- MONTO A PAGAR -->

                <div class="mb-3">

                    <label class="form-label">
                        Monto a pagar
                    </label>

                    <div class="input-group">

                        <span class="input-group-text">
                            $
                        </span>

                        <input
                            type="number"
                            class="form-control"
                            name="costo_total"
                            id="costoTotal"
                            min="0"
                            step="0.01"
                            required
                            value="<?= htmlspecialchars($editar["costo_total"] ?? "0") ?>"
                        >

                    </div>

                    <div class="form-text">
                        Podés modificar este monto manualmente si acordaste
                        un precio diferente con el cliente.
                    </div>

                </div>


                <!-- ESTADO -->

                <div class="mb-3">

                    <label class="form-label">
                        Estado
                    </label>

                    <select
                        class="form-select"
                        name="estado"
                    >

                        <?php foreach (
                            [
                                "pendiente" => "Pendiente de pago",
                                "confirmada" => "Confirmada",
                                "cancelada" => "Cancelada",
                                "finalizada" => "Finalizada"
                            ] as $v => $txt
                        ): ?>

                            <option
                                value="<?= $v ?>"
                                <?= (($editar["estado"] ?? "pendiente") === $v) ? "selected" : "" ?>
                            >

                                <?= $txt ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- OBSERVACIONES -->

                <div class="mb-3">

                    <label class="form-label">
                        Observaciones
                    </label>

                    <textarea
                        class="form-control"
                        name="observaciones"
                        rows="3"
                    ><?= htmlspecialchars($editar["observaciones"] ?? "") ?></textarea>

                </div>


                <button class="btn btn-primary w-100">
                    Guardar reserva
                </button>

                <?php if ($editar): ?>

                    <a
                        href="reservas.php"
                        class="btn btn-light w-100 mt-2"
                    >
                        Cancelar
                    </a>

                <?php endif; ?>

            </form>

        </div>

    </div>


    <!-- HISTORIAL -->

    <div class="col-xl-8">

        <div class="card-soft">

            <div class="d-flex justify-content-between align-items-center mb-3">

                <div>

                    <h2 class="section-title mb-1">
                        Historial de reservas
                    </h2>

                    <div class="text-muted small">
                        Todas las reservas almacenadas en MySQL
                    </div>

                </div>

                <a
                    href="mapa.php"
                    class="btn btn-outline-primary btn-sm"
                >
                    Ver mapa
                </a>

            </div>


            <div class="table-responsive">

                <table class="table align-middle">

                    <thead>

                        <tr>
                            <th>Carpa</th>
                            <th>Cliente</th>
                            <th>Fechas</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                            <th>Costo</th>
                            <th></th>
                        </tr>

                    </thead>


                    <tbody>

                    <?php while ($r = $lista->fetch_assoc()): ?>

                        <tr>

                            <td>

                                <strong>
                                    #<?= htmlspecialchars($r["numero"]) ?>
                                </strong>

                                <div class="small text-muted">
                                    <?= htmlspecialchars($r["sector"]) ?>
                                </div>

                            </td>


                            <td>

                                <?= htmlspecialchars($r["nombre"]) ?>

                                <div class="small text-muted">
                                    <?= htmlspecialchars($r["telefono"]) ?>
                                </div>

                            </td>


                            <td>

                                <?= date("d/m/Y", strtotime($r["fecha_inicio"])) ?>

                                →

                                <?= date("d/m/Y", strtotime($r["fecha_fin"])) ?>

                            </td>


                            <td>
                                <?= ucfirst($r["tipo_reserva"]) ?>
                            </td>


                            <td>

                                <span class="status status-<?= htmlspecialchars($r["estado"]) ?>">

                                    <?= ucfirst($r["estado"]) ?>

                                </span>

                            </td>


                            <td>

                                <strong>
                                    $<?= number_format($r["costo_total"], 2, ",", ".") ?>
                                </strong>

                            </td>


                            <td class="text-end">

                                <a
                                    class="btn btn-sm btn-outline-primary"
                                    href="reservas.php?editar=<?= $r["id"] ?>"
                                >
                                    Editar
                                </a>


                                <form
                                    method="post"
                                    class="d-inline"
                                    onsubmit="return confirm('¿Eliminar reserva?')"
                                >

                                    <input
                                        type="hidden"
                                        name="operacion"
                                        value="eliminar"
                                    >

                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= $r["id"] ?>"
                                    >

                                    <button
                                        class="btn btn-sm btn-outline-danger"
                                    >
                                        Eliminar
                                    </button>

                                </form>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>


<script>

const carpaSelect = document.getElementById("carpaId");
const tipoSelect = document.getElementById("tipoReserva");
const inicio = document.getElementById("fechaInicio");
const fin = document.getElementById("fechaFin");

const costoCalculado = document.getElementById("costoCalculado");
const costoTotal = document.getElementById("costoTotal");


// Indica si estamos editando una reserva existente
const editandoReserva = <?= $editar ? "true" : "false" ?>;


// Calcula automáticamente el precio
function calcularCosto() {

    const opt = carpaSelect?.selectedOptions[0];

    if (!opt || !opt.value) {

        costoCalculado.textContent = "$0,00";

        if (!editandoReserva) {
            costoTotal.value = "";
        }

        return;
    }


    const tipo = tipoSelect.value;

    let precio = Number(opt.dataset[tipo] || 0);


    if (tipo === "diaria") {

        if (!inicio.value || !fin.value) {

            costoCalculado.textContent = "$0,00";

            return;
        }


        const d1 = new Date(inicio.value);
        const d2 = new Date(fin.value);

        const dias = Math.max(
            1,
            Math.round((d2 - d1) / 86400000) + 1
        );

        precio *= dias;
    }


    costoCalculado.textContent =
        "$" +
        precio.toLocaleString(
            "es-AR",
            {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }
        );


    /*
     * Si estamos creando una reserva,
     * el monto a pagar se completa automáticamente.
     *
     * El administrador puede modificarlo después.
     */
    if (!editandoReserva) {
        costoTotal.value = precio.toFixed(2);
    }

}


// Eventos
[
    carpaSelect,
    tipoSelect,
    inicio,
    fin
].forEach(el => {

    el?.addEventListener("change", calcularCosto);

});


// Calcular al cargar
calcularCosto();

</script>


<?php require_once "footer.php"; ?>