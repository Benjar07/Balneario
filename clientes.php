<?php
$titulo = "Clientes";
require_once "conexion.php";

$mensaje = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $operacion = $_POST["operacion"] ?? "";
    $id = (int)($_POST["id"] ?? 0);
    $nombre = trim($_POST["nombre"] ?? "");
    $dni = trim($_POST["dni"] ?? "");
    $telefono = trim($_POST["telefono"] ?? "");
    $email = trim($_POST["email"] ?? "");

    if ($operacion === "guardar") {
        if ($id > 0) {
            $stmt = $conexion->prepare("UPDATE clientes SET nombre=?, dni=?, telefono=?, email=? WHERE id=?");
            $stmt->bind_param("ssssi", $nombre, $dni, $telefono, $email, $id);
            $stmt->execute();
            $mensaje = "Cliente actualizado.";
        } else {
            $stmt = $conexion->prepare("INSERT INTO clientes (nombre, dni, telefono, email) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $nombre, $dni, $telefono, $email);
            $stmt->execute();
            $mensaje = "Cliente creado.";
        }
    }

    if ($operacion === "eliminar" && $id > 0) {
        $stmt = $conexion->prepare("DELETE FROM clientes WHERE id=?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) $mensaje = "Cliente eliminado.";
        else $error = "No se puede eliminar un cliente con reservas.";
    }
}

$editar = null;
if (isset($_GET["editar"])) {
    $id = (int)$_GET["editar"];
    $stmt = $conexion->prepare("SELECT * FROM clientes WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $editar = $stmt->get_result()->fetch_assoc();
}

$lista = $conexion->query("SELECT * FROM clientes ORDER BY nombre");
require_once "header.php";
?>

<?php if ($mensaje): ?><div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card-soft">
            <h2 class="section-title"><?= $editar ? "Editar cliente" : "Nuevo cliente" ?></h2>
            <form method="post">
                <input type="hidden" name="operacion" value="guardar">
                <input type="hidden" name="id" value="<?= (int)($editar["id"] ?? 0) ?>">
                <div class="mb-3"><label class="form-label">Nombre completo</label><input class="form-control" name="nombre" required value="<?= htmlspecialchars($editar["nombre"] ?? "") ?>"></div>
                <div class="mb-3"><label class="form-label">DNI / Documento</label><input class="form-control" name="dni" required value="<?= htmlspecialchars($editar["dni"] ?? "") ?>"></div>
                <div class="mb-3"><label class="form-label">Teléfono</label><input class="form-control" name="telefono" value="<?= htmlspecialchars($editar["telefono"] ?? "") ?>"></div>
                <div class="mb-3"><label class="form-label">Email</label><input type="email" class="form-control" name="email" value="<?= htmlspecialchars($editar["email"] ?? "") ?>"></div>
                <button class="btn btn-primary w-100">Guardar cliente</button>
                <?php if ($editar): ?><a class="btn btn-light w-100 mt-2" href="clientes.php">Cancelar</a><?php endif; ?>
            </form>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card-soft">
            <h2 class="section-title">Listado de clientes</h2>
            <div class="table-responsive mt-3">
                <table class="table align-middle">
                    <thead><tr><th>Nombre</th><th>DNI</th><th>Teléfono</th><th>Email</th><th></th></tr></thead>
                    <tbody>
                    <?php while ($fila = $lista->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($fila["nombre"]) ?></td>
                            <td><?= htmlspecialchars($fila["dni"]) ?></td>
                            <td><?= htmlspecialchars($fila["telefono"]) ?></td>
                            <td><?= htmlspecialchars($fila["email"]) ?></td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="clientes.php?editar=<?= $fila["id"] ?>">Editar</a>
                                <form method="post" class="d-inline" onsubmit="return confirm('¿Eliminar cliente?')">
                                    <input type="hidden" name="operacion" value="eliminar">
                                    <input type="hidden" name="id" value="<?= $fila["id"] ?>">
                                    <button class="btn btn-sm btn-outline-danger">Eliminar</button>
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
