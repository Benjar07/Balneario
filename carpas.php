<?php
$titulo = "Gestión de carpas";
require_once "conexion.php";

$mensaje = "";
$error = "";

$accion = $_GET["accion"] ?? "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $operacion = $_POST["operacion"] ?? "";
    $id = (int)($_POST["id"] ?? 0);
    $numero = trim($_POST["numero"] ?? "");
    $sector = trim($_POST["sector"] ?? "");
    $fila = (int)($_POST["fila"] ?? 1);
    $columna = (int)($_POST["columna"] ?? 1);
    $x = (int)($_POST["x"] ?? 40);
    $y = (int)($_POST["y"] ?? 40);
    $activa = isset($_POST["activa"]) ? 1 : 0;

    if ($operacion === "guardar") {

        if ($id > 0) {

            try {
                $stmt = $conexion->prepare(
                    "UPDATE carpas 
                     SET numero=?, sector=?, fila=?, columna=?, pos_x=?, pos_y=?, activa=? 
                     WHERE id=?"
                );

                $stmt->bind_param(
                    "ssiiiiii",
                    $numero,
                    $sector,
                    $fila,
                    $columna,
                    $x,
                    $y,
                    $activa,
                    $id
                );

                $stmt->execute();

                $mensaje = "Carpa actualizada correctamente.";

            } catch (mysqli_sql_exception $e) {

                if ($e->getCode() == 1062) {
                    $error = "Ya existe otra carpa con el número \"$numero\". Elegí otro número.";
                } else {
                    $error = "No se pudo actualizar la carpa.";
                }
            }

        } else {

            try {
                $stmt = $conexion->prepare(
                    "INSERT INTO carpas 
                    (numero, sector, fila, columna, pos_x, pos_y, activa) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)"
                );

                $stmt->bind_param(
                    "ssiiiii",
                    $numero,
                    $sector,
                    $fila,
                    $columna,
                    $x,
                    $y,
                    $activa
                );

                $stmt->execute();

                $mensaje = "Carpa creada correctamente.";

            } catch (mysqli_sql_exception $e) {

                if ($e->getCode() == 1062) {
                    $error = "Ya existe una carpa con el número \"$numero\". Elegí otro número.";
                } else {
                    $error = "No se pudo crear la carpa.";
                }
            }
        }
    }

    if ($operacion === "eliminar" && $id > 0) {

        try {
            $stmt = $conexion->prepare("DELETE FROM carpas WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            $mensaje = "Carpa eliminada.";

        } catch (mysqli_sql_exception $e) {

            if ($e->getCode() == 1451) {
                $error = "No se puede eliminar la carpa porque tiene reservas asociadas.";
            } else {
                $error = "No se puede eliminar la carpa.";
            }
        }
    }
}

$editar = null;

if ($accion === "editar" && isset($_GET["id"])) {
    $id = (int)$_GET["id"];

    $stmt = $conexion->prepare("SELECT * FROM carpas WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $editar = $stmt->get_result()->fetch_assoc();
}

$lista = $conexion->query("SELECT * FROM carpas ORDER BY sector, numero");

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

    <div class="col-lg-4">
        <div class="card-soft">

            <h2 class="section-title">
                <?= $editar ? 'Editar carpa' : 'Nueva carpa' ?>
            </h2>

            <form method="post">

                <input type="hidden" name="operacion" value="guardar">

                <input 
                    type="hidden" 
                    name="id" 
                    value="<?= (int)($editar["id"] ?? 0) ?>"
                >

                <div class="mb-3">
                    <label class="form-label">Número</label>

                    <input 
                        class="form-control" 
                        name="numero" 
                        required 
                        value="<?= htmlspecialchars($editar["numero"] ?? "") ?>"
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">Sector</label>

                    <input 
                        class="form-control" 
                        name="sector" 
                        required 
                        value="<?= htmlspecialchars($editar["sector"] ?? "Sector A") ?>"
                    >
                </div>

                <div class="row">

                    <div class="col-6 mb-3">
                        <label class="form-label">Fila</label>

                        <input 
                            type="number" 
                            min="1" 
                            class="form-control" 
                            name="fila" 
                            value="<?= (int)($editar["fila"] ?? 1) ?>"
                        >
                    </div>

                    <div class="col-6 mb-3">
                        <label class="form-label">Columna</label>

                        <input 
                            type="number" 
                            min="1" 
                            class="form-control" 
                            name="columna" 
                            value="<?= (int)($editar["columna"] ?? 1) ?>"
                        >
                    </div>

                </div>

                <div class="row">

                    <div class="col-6 mb-3">
                        <label class="form-label">Posición X</label>

                        <input 
                            type="number" 
                            class="form-control" 
                            name="x" 
                            value="<?= (int)($editar["pos_x"] ?? 40) ?>"
                        >
                    </div>

                    <div class="col-6 mb-3">
                        <label class="form-label">Posición Y</label>

                        <input 
                            type="number" 
                            class="form-control" 
                            name="y" 
                            value="<?= (int)($editar["pos_y"] ?? 40) ?>"
                        >
                    </div>

                </div>

                <div class="form-check form-switch mb-3">

                    <input 
                        class="form-check-input" 
                        type="checkbox" 
                        name="activa" 
                        <?= (!isset($editar) || $editar["activa"]) ? "checked" : "" ?>
                    >

                    <label class="form-check-label">
                        Carpa activa
                    </label>

                </div>

                <button class="btn btn-primary w-100">
                    Guardar
                </button>

                <?php if ($editar): ?>

                    <a 
                        href="carpas.php" 
                        class="btn btn-light w-100 mt-2"
                    >
                        Cancelar
                    </a>

                <?php endif; ?>

            </form>

        </div>
    </div>


    <div class="col-lg-8">

        <div class="card-soft">

            <div class="d-flex justify-content-between align-items-center mb-3">

                <div>

                    <h2 class="section-title mb-1">
                        Listado de carpas
                    </h2>

                    <div class="text-muted small">
                        Numeración, sector y posición del mapa
                    </div>

                </div>

                <a 
                    href="configurador.php" 
                    class="btn btn-outline-primary btn-sm"
                >
                    Configurar mapa
                </a>

            </div>


            <div class="table-responsive">

                <table class="table align-middle">

                    <thead>

                        <tr>
                            <th>Número</th>
                            <th>Sector</th>
                            <th>Fila</th>
                            <th>Col.</th>
                            <th>Posición</th>
                            <th>Activa</th>
                            <th></th>
                        </tr>

                    </thead>

                    <tbody>

                    <?php while ($fila = $lista->fetch_assoc()): ?>

                        <tr>

                            <td>
                                <strong>
                                    #<?= htmlspecialchars($fila["numero"]) ?>
                                </strong>
                            </td>

                            <td>
                                <?= htmlspecialchars($fila["sector"]) ?>
                            </td>

                            <td>
                                <?= (int)$fila["fila"] ?>
                            </td>

                            <td>
                                <?= (int)$fila["columna"] ?>
                            </td>

                            <td>
                                <?= (int)$fila["pos_x"] ?> /
                                <?= (int)$fila["pos_y"] ?>
                            </td>

                            <td>
                                <?= $fila["activa"] ? '✅' : '⛔' ?>
                            </td>

                            <td class="text-end">

                                <a 
                                    href="carpas.php?accion=editar&id=<?= $fila["id"] ?>" 
                                    class="btn btn-sm btn-outline-primary"
                                >
                                    Editar
                                </a>

                                <form 
                                    method="post" 
                                    class="d-inline" 
                                    onsubmit="return confirm('¿Eliminar esta carpa?')"
                                >

                                    <input 
                                        type="hidden" 
                                        name="operacion" 
                                        value="eliminar"
                                    >

                                    <input 
                                        type="hidden" 
                                        name="id" 
                                        value="<?= $fila["id"] ?>"
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

<?php require_once "footer.php"; ?>