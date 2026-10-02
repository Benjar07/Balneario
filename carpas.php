<?php

ob_start();

$titulo = "Gestión de carpas";

require_once "conexion.php";

$archivoMapa = __DIR__ . "/mapa_config.json";

$mensaje = "";
$error = "";

$accion = $_GET["accion"] ?? "";


/*
=========================================================
RESPONDER JSON
=========================================================
*/

function responderJSON($datos, $codigo = 200)
{
    http_response_code($codigo);

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header("Content-Type: application/json; charset=utf-8");

    echo json_encode(
        $datos,
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


/*
=========================================================
CARGAR MAPA
=========================================================
*/

function cargarMapa($archivo)
{
    if (!file_exists($archivo)) {

        $datos = [

            "secciones" => [

                [
                    "id" => 1,
                    "nombre" => "SECCIÓN 1",
                    "x" => 30,
                    "y" => 120,
                    "ancho" => 400,
                    "alto" => 200
                ],

                [
                    "id" => 2,
                    "nombre" => "SECCIÓN 2",
                    "x" => 30,
                    "y" => 350,
                    "ancho" => 400,
                    "alto" => 200
                ],

                [
                    "id" => 3,
                    "nombre" => "SECCIÓN 3",
                    "x" => 30,
                    "y" => 580,
                    "ancho" => 400,
                    "alto" => 200
                ]

            ],

            "caminos" => [

                [
                    "id" => 1,
                    "nombre" => "CAMINO 1",
                    "x" => 250,
                    "ancho" => 55
                ],

                [
                    "id" => 2,
                    "nombre" => "CAMINO 2",
                    "x" => 450,
                    "ancho" => 55
                ],

                [
                    "id" => 3,
                    "nombre" => "CAMINO 3",
                    "x" => 650,
                    "ancho" => 55
                ]

            ]

        ];

        guardarMapa(
            $archivo,
            $datos
        );

        return $datos;
    }


    $contenido =
        file_get_contents($archivo);


    $datos =
        json_decode(
            $contenido,
            true
        );


    if (!is_array($datos)) {

        $datos = [
            "secciones" => [],
            "caminos" => []
        ];

    }


    if (!isset($datos["secciones"])) {
        $datos["secciones"] = [];
    }


    if (!isset($datos["caminos"])) {
        $datos["caminos"] = [];
    }


    return $datos;
}


/*
=========================================================
GUARDAR MAPA
=========================================================
*/

function guardarMapa($archivo, $datos)
{
    return file_put_contents(
        $archivo,
        json_encode(
            $datos,
            JSON_PRETTY_PRINT |
            JSON_UNESCAPED_UNICODE
        ),
        LOCK_EX
    ) !== false;
}


$mapa =
    cargarMapa($archivoMapa);


/*
=========================================================
ACCIONES DEL CONFIGURADOR
=========================================================
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["accion"])
) {

    $accionMapa =
        $_POST["accion"];


    /*
    =====================================================
    GUARDAR POSICIÓN DE CARPA
    =====================================================
    */

    if (
        $accionMapa === "guardar_carpa"
    ) {

        $id =
            (int)($_POST["id"] ?? 0);

        $x =
            (int)($_POST["x"] ?? 0);

        $y =
            (int)($_POST["y"] ?? 0);


        $stmt =
            $conexion->prepare("
                UPDATE carpas
                SET pos_x = ?, pos_y = ?
                WHERE id = ?
            ");


        if (!$stmt) {

            responderJSON([
                "ok" => false,
                "mensaje" =>
                    "Error preparando consulta"
            ], 500);

        }


        $stmt->bind_param(
            "iii",
            $x,
            $y,
            $id
        );


        $ok =
            $stmt->execute();


        $stmt->close();


        responderJSON([
            "ok" => $ok
        ]);

    }


    /*
    =====================================================
    CREAR SECCIÓN
    =====================================================
    */

    if (
        $accionMapa === "crear_seccion"
    ) {

        $nombre =
            trim(
                $_POST["nombre"] ?? ""
            );


        if ($nombre === "") {
            $nombre = "NUEVA SECCIÓN";
        }


        $ultimoID = 0;


        foreach (
            $mapa["secciones"]
            as $seccion
        ) {

            if (
                isset($seccion["id"]) &&
                (int)$seccion["id"] > $ultimoID
            ) {

                $ultimoID =
                    (int)$seccion["id"];

            }

        }


        $nuevoID =
            $ultimoID + 1;


        $cantidad =
            count(
                $mapa["secciones"]
            );


        $columna =
            $cantidad % 3;


        $fila =
            floor(
                $cantidad / 3
            );


        $x =
            30 +
            ($columna * 280);


        $y =
            120 +
            ($fila * 220);


        if ($x > 620) {
            $x = 620;
        }


        if ($y > 650) {
            $y = 650;
        }


        $nuevaSeccion = [

            "id" =>
                $nuevoID,

            "nombre" =>
                $nombre,

            "x" =>
                $x,

            "y" =>
                $y,

            "ancho" =>
                250,

            "alto" =>
                180

        ];


        $mapa["secciones"][] =
            $nuevaSeccion;


        $guardado =
            guardarMapa(
                $archivoMapa,
                $mapa
            );


        if (!$guardado) {

            responderJSON([
                "ok" => false,
                "mensaje" =>
                    "No se pudo escribir mapa_config.json"
            ], 500);

        }


        responderJSON([

            "ok" =>
                true,

            "id" =>
                $nuevoID,

            "nombre" =>
                $nombre,

            "x" =>
                $x,

            "y" =>
                $y,

            "ancho" =>
                250,

            "alto" =>
                180

        ]);

    }


    /*
    =====================================================
    GUARDAR SECCIÓN
    =====================================================
    */

    if (
        $accionMapa === "guardar_seccion"
    ) {

        $id =
            (int)($_POST["id"] ?? 0);

        $x =
            (int)($_POST["x"] ?? 0);

        $y =
            (int)($_POST["y"] ?? 90);

        $ancho =
            (int)($_POST["ancho"] ?? 150);

        $alto =
            (int)($_POST["alto"] ?? 100);


        $ancho =
            max(150, $ancho);


        $alto =
            max(100, $alto);


        $encontrada =
            false;


        foreach (
            $mapa["secciones"]
            as &$seccion
        ) {

            if (
                (int)$seccion["id"] === $id
            ) {

                $seccion["x"] =
                    $x;

                $seccion["y"] =
                    $y;

                $seccion["ancho"] =
                    $ancho;

                $seccion["alto"] =
                    $alto;

                $encontrada =
                    true;

                break;

            }

        }


        unset($seccion);


        if (!$encontrada) {

            responderJSON([
                "ok" => false,
                "mensaje" =>
                    "Sección no encontrada"
            ], 404);

        }


        $guardado =
            guardarMapa(
                $archivoMapa,
                $mapa
            );


        responderJSON([
            "ok" =>
                $guardado
        ]);

    }


    /*
    =====================================================
    RENOMBRAR SECCIÓN
    =====================================================
    */

    if (
        $accionMapa === "renombrar_seccion"
    ) {

        $id =
            (int)($_POST["id"] ?? 0);


        $nombre =
            trim(
                $_POST["nombre"] ?? ""
            );


        if ($nombre === "") {
            $nombre = "SECCIÓN";
        }


        $encontrada =
            false;


        foreach (
            $mapa["secciones"]
            as &$seccion
        ) {

            if (
                (int)$seccion["id"] === $id
            ) {

                $seccion["nombre"] =
                    $nombre;

                $encontrada =
                    true;

                break;

            }

        }


        unset($seccion);


        if (!$encontrada) {

            responderJSON([
                "ok" => false,
                "mensaje" =>
                    "Sección no encontrada"
            ], 404);

        }


        $guardado =
            guardarMapa(
                $archivoMapa,
                $mapa
            );


        responderJSON([

            "ok" =>
                $guardado,

            "nombre" =>
                $nombre

        ]);

    }


    /*
    =====================================================
    ELIMINAR SECCIÓN
    =====================================================
    */

    if (
        $accionMapa === "eliminar_seccion"
    ) {

        $id =
            (int)($_POST["id"] ?? 0);


        $nuevasSecciones = [];


        foreach (
            $mapa["secciones"]
            as $seccion
        ) {

            if (
                (int)$seccion["id"] !== $id
            ) {

                $nuevasSecciones[] =
                    $seccion;

            }

        }


        $mapa["secciones"] =
            $nuevasSecciones;


        $guardado =
            guardarMapa(
                $archivoMapa,
                $mapa
            );


        responderJSON([
            "ok" =>
                $guardado
        ]);

    }


    /*
    =====================================================
    GUARDAR CAMINO
    =====================================================
    */

    if (
        $accionMapa === "guardar_camino"
    ) {

        $id =
            (int)($_POST["id"] ?? 0);


        $x =
            (int)($_POST["x"] ?? 0);


        $encontrado =
            false;


        foreach (
            $mapa["caminos"]
            as &$camino
        ) {

            if (
                (int)$camino["id"] === $id
            ) {

                $camino["x"] =
                    $x;

                $encontrado =
                    true;

                break;

            }

        }


        unset($camino);


        if (!$encontrado) {

            responderJSON([
                "ok" => false,
                "mensaje" =>
                    "Camino no encontrado"
            ], 404);

        }


        $guardado =
            guardarMapa(
                $archivoMapa,
                $mapa
            );


        responderJSON([
            "ok" =>
                $guardado
        ]);

    }


    responderJSON([
        "ok" => false,
        "mensaje" =>
            "Acción desconocida"
    ], 400);

}


/*
=========================================================
GESTIÓN NORMAL DE CARPAS
=========================================================
*/

$operacion =
    $_POST["operacion"] ?? "";


/*
=========================================================
GUARDAR / CREAR CARPA
=========================================================
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    $operacion === "guardar"
) {

    $id =
        (int)($_POST["id"] ?? 0);

    $numero =
        trim(
            $_POST["numero"] ?? ""
        );

    $sector =
        trim(
            $_POST["sector"] ?? ""
        );

    $fila =
        (int)($_POST["fila"] ?? 1);

    $columna =
        (int)($_POST["columna"] ?? 1);

    $x =
        (int)($_POST["x"] ?? 40);

    $y =
        (int)($_POST["y"] ?? 40);

    $activa =
        isset($_POST["activa"])
            ? 1
            : 0;


    if ($id > 0) {

        try {

            $stmt =
                $conexion->prepare(
                    "UPDATE carpas
                     SET numero=?,
                         sector=?,
                         fila=?,
                         columna=?,
                         pos_x=?,
                         pos_y=?,
                         activa=?
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


            $mensaje =
                "Carpa actualizada correctamente.";


        }
        catch (
            mysqli_sql_exception $e
        ) {

            if (
                $e->getCode() == 1062
            ) {

                $error =
                    "Ya existe otra carpa con el número \"$numero\". Elegí otro número.";

            }
            else {

                $error =
                    "No se pudo actualizar la carpa.";

            }

        }

    }
    else {

        try {

            $stmt =
                $conexion->prepare(
                    "INSERT INTO carpas
                    (
                        numero,
                        sector,
                        fila,
                        columna,
                        pos_x,
                        pos_y,
                        activa
                    )
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


            $mensaje =
                "Carpa creada correctamente.";


        }
        catch (
            mysqli_sql_exception $e
        ) {

            if (
                $e->getCode() == 1062
            ) {

                $error =
                    "Ya existe una carpa con el número \"$numero\". Elegí otro número.";

            }
            else {

                $error =
                    "No se pudo crear la carpa.";

            }

        }

    }

}


/*
=========================================================
ELIMINAR CARPA
=========================================================
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    $operacion === "eliminar"
) {

    $id =
        (int)($_POST["id"] ?? 0);


    if ($id > 0) {

        try {

            $stmt =
                $conexion->prepare(
                    "DELETE FROM carpas WHERE id=?"
                );


            $stmt->bind_param(
                "i",
                $id
            );


            $stmt->execute();


            $mensaje =
                "Carpa eliminada.";

        }
        catch (
            mysqli_sql_exception $e
        ) {

            if (
                $e->getCode() == 1451
            ) {

                $error =
                    "No se puede eliminar la carpa porque tiene reservas asociadas.";

            }
            else {

                $error =
                    "No se puede eliminar la carpa.";

            }

        }

    }

}


/*
=========================================================
EDITAR CARPA
=========================================================
*/

$editar = null;


if (
    $accion === "editar" &&
    isset($_GET["id"])
) {

    $id =
        (int)$_GET["id"];


    $stmt =
        $conexion->prepare(
            "SELECT *
             FROM carpas
             WHERE id=?"
        );


    $stmt->bind_param(
        "i",
        $id
    );


    $stmt->execute();


    $editar =
        $stmt
        ->get_result()
        ->fetch_assoc();

}


/*
=========================================================
LISTA DE CARPAS
=========================================================
*/

$lista =
    $conexion->query(
        "SELECT *
         FROM carpas
         ORDER BY sector, numero"
    );


require_once "header.php";

?>


<?php if ($mensaje): ?>

    <div class="alert alert-success">

        <?= htmlspecialchars(
            $mensaje,
            ENT_QUOTES,
            "UTF-8"
        ) ?>

    </div>

<?php endif; ?>


<?php if ($error): ?>

    <div class="alert alert-danger">

        <?= htmlspecialchars(
            $error,
            ENT_QUOTES,
            "UTF-8"
        ) ?>

    </div>

<?php endif; ?>


<div class="row g-4">


    <!-- =====================================================
         FORMULARIO
         ===================================================== -->

    <div class="col-lg-4">

        <div class="card-soft">

            <h2 class="section-title">

                <?= $editar
                    ? "Editar carpa"
                    : "Nueva carpa"
                ?>

            </h2>


            <form method="post">

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


                <div class="mb-3">

                    <label class="form-label">
                        Número
                    </label>


                    <input
                        class="form-control"
                        name="numero"
                        required
                        value="<?= htmlspecialchars(
                            $editar["numero"] ?? "",
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>"
                    >

                </div>


                <div class="mb-3">

                    <label class="form-label">
                        Sector
                    </label>


                    <input
                        class="form-control"
                        name="sector"
                        required
                        value="<?= htmlspecialchars(
                            $editar["sector"] ?? "Sector A",
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>"
                    >

                </div>


                <div class="row">


                    <div class="col-6 mb-3">

                        <label class="form-label">
                            Fila
                        </label>


                        <input
                            type="number"
                            min="1"
                            class="form-control"
                            name="fila"
                            value="<?= (int)($editar["fila"] ?? 1) ?>"
                        >

                    </div>


                    <div class="col-6 mb-3">

                        <label class="form-label">
                            Columna
                        </label>


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

                        <label class="form-label">
                            Posición X
                        </label>


                        <input
                            type="number"
                            class="form-control"
                            name="x"
                            value="<?= (int)($editar["pos_x"] ?? 40) ?>"
                        >

                    </div>


                    <div class="col-6 mb-3">

                        <label class="form-label">
                            Posición Y
                        </label>


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
                        <?= (
                            !isset($editar) ||
                            $editar["activa"]
                        )
                            ? "checked"
                            : ""
                        ?>
                    >


                    <label class="form-check-label">
                        Carpa activa
                    </label>

                </div>


                <button
                    class="btn btn-primary w-100"
                >
                    Guardar
                </button>


                <?php if ($editar): ?>

                    <a
                        href="mapa.php"
                        class="btn btn-light w-100 mt-2"
                    >
                        Cancelar
                    </a>

                <?php endif; ?>

            </form>

        </div>

    </div>


    <!-- =====================================================
         LISTADO
         ===================================================== -->

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

                            <th>
                                Número
                            </th>

                            <th>
                                Sector
                            </th>

                            <th>
                                Fila
                            </th>

                            <th>
                                Col.
                            </th>

                            <th>
                                Posición
                            </th>

                            <th>
                                Activa
                            </th>

                            <th>
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php while (
                        $fila =
                        $lista->fetch_assoc()
                    ): ?>


                        <tr>


                            <td>

                                <strong>

                                    #<?= htmlspecialchars(
                                        $fila["numero"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>

                                </strong>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $fila["sector"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>

                            </td>


                            <td>

                                <?= (int)$fila["fila"] ?>

                            </td>


                            <td>

                                <?= (int)$fila["columna"] ?>

                            </td>


                            <td>

                                <?= (int)$fila["pos_x"] ?>

                                /

                                <?= (int)$fila["pos_y"] ?>

                            </td>


                            <td>

                                <?= $fila["activa"]
                                    ? "✅"
                                    : "⛔"
                                ?>

                            </td>


                            <td class="text-end">


                                <a
                                    href="mapa.php?accion=editar&id=<?= (int)$fila["id"] ?>"
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
                                        value="<?= (int)$fila["id"] ?>"
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