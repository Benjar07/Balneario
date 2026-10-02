```php
<?php

ob_start();

$titulo = "Configurador del mapa";

$archivoMapa = __DIR__ . "/mapa_config.json";


/* =========================================================
   RESPONDER JSON
   ========================================================= */

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


/* =========================================================
   CARGAR CONFIGURACIÓN
   ========================================================= */

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

            "sectores" => [],

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

        guardarMapa($archivo, $datos);

        return $datos;
    }


    $contenido = file_get_contents($archivo);

    $datos = json_decode(
        $contenido,
        true
    );


    if (!is_array($datos)) {

        $datos = [
            "secciones" => [],
            "sectores" => [],
            "caminos" => []
        ];
    }


    if (!isset($datos["secciones"])) {
        $datos["secciones"] = [];
    }


    if (!isset($datos["sectores"])) {
        $datos["sectores"] = [];
    }


    if (!isset($datos["caminos"])) {
        $datos["caminos"] = [];
    }


    return $datos;
}


/* =========================================================
   GUARDAR CONFIGURACIÓN
   ========================================================= */

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


/* =========================================================
   CARGAR MAPA
   ========================================================= */

$mapa = cargarMapa($archivoMapa);


/* =========================================================
   ACCIONES POST
   ========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $accion = $_POST["accion"] ?? "";


    /* =====================================================
       GUARDAR CARPA
       ===================================================== */

    if ($accion === "guardar_carpa") {

        require_once "conexion.php";

        $id = (int)($_POST["id"] ?? 0);
        $x = (int)($_POST["x"] ?? 0);
        $y = (int)($_POST["y"] ?? 0);


        $stmt = $conexion->prepare("
            UPDATE carpas
            SET pos_x = ?, pos_y = ?
            WHERE id = ?
        ");


        if (!$stmt) {

            responderJSON([
                "ok" => false,
                "mensaje" => "Error preparando consulta"
            ], 500);
        }


        $stmt->bind_param(
            "iii",
            $x,
            $y,
            $id
        );


        $ok = $stmt->execute();

        $stmt->close();


        responderJSON([
            "ok" => $ok
        ]);
    }


    /* =====================================================
       CREAR SECCIÓN
       ===================================================== */

    if ($accion === "crear_seccion") {

        $nombre = trim(
            $_POST["nombre"] ?? ""
        );


        if ($nombre === "") {
            $nombre = "NUEVA SECCIÓN";
        }


        $ultimoID = 0;


        foreach ($mapa["secciones"] as $seccion) {

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
            count($mapa["secciones"]);


        $columna =
            $cantidad % 3;


        $fila =
            floor($cantidad / 3);


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

            "id" => $nuevoID,

            "nombre" => $nombre,

            "x" => $x,

            "y" => $y,

            "ancho" => 250,

            "alto" => 180
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

            "ok" => true,

            "id" => $nuevoID,

            "nombre" => $nombre,

            "x" => $x,

            "y" => $y,

            "ancho" => 250,

            "alto" => 180
        ]);
    }


    /* =====================================================
       GUARDAR SECCIÓN
       ===================================================== */

    if ($accion === "guardar_seccion") {

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


        $encontrada = false;


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

                $encontrada = true;

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
            "ok" => $guardado
        ]);
    }


    /* =====================================================
       CAMBIAR NOMBRE SECCIÓN
       ===================================================== */

    if ($accion === "renombrar_seccion") {

        $id =
            (int)($_POST["id"] ?? 0);


        $nombre =
            trim(
                $_POST["nombre"] ?? ""
            );


        if ($nombre === "") {
            $nombre = "SECCIÓN";
        }


        $encontrada = false;


        foreach (
            $mapa["secciones"]
            as &$seccion
        ) {

            if (
                (int)$seccion["id"] === $id
            ) {

                $seccion["nombre"] =
                    $nombre;

                $encontrada = true;

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


    /* =====================================================
       ELIMINAR SECCIÓN
       ===================================================== */

    if ($accion === "eliminar_seccion") {

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
            "ok" => $guardado
        ]);
    }


    /* =====================================================
       CREAR SECTOR
       ===================================================== */

    if ($accion === "crear_sector") {

        $nombre =
            trim(
                $_POST["nombre"] ?? ""
            );


        if ($nombre === "") {
            $nombre = "NUEVO SECTOR";
        }


        $ultimoID = 0;


        foreach (
            $mapa["sectores"]
            as $sector
        ) {

            if (
                isset($sector["id"]) &&
                (int)$sector["id"] > $ultimoID
            ) {

                $ultimoID =
                    (int)$sector["id"];
            }
        }


        $nuevoID =
            $ultimoID + 1;


        $cantidad =
            count($mapa["sectores"]);


        /*
           Los sectores tienen exactamente
           el mismo tamaño que una carpa.
        */

        $columna =
            $cantidad % 4;


        $fila =
            floor($cantidad / 4);


        $x =
            520 +
            ($columna * 110);


        $y =
            120 +
            ($fila * 80);


        if ($x > 810) {
            $x = 810;
        }


        if ($y > 780) {
            $y = 780;
        }


        $nuevoSector = [

            "id" => $nuevoID,

            "nombre" => $nombre,

            "x" => $x,

            "y" => $y,

            "ancho" => 90,

            "alto" => 60
        ];


        $mapa["sectores"][] =
            $nuevoSector;


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

            "ok" => true,

            "id" => $nuevoID,

            "nombre" => $nombre,

            "x" => $x,

            "y" => $y,

            "ancho" => 90,

            "alto" => 60
        ]);
    }


    /* =====================================================
       GUARDAR SECTOR
       ===================================================== */

    if ($accion === "guardar_sector") {

        $id =
            (int)($_POST["id"] ?? 0);

        $x =
            (int)($_POST["x"] ?? 0);

        $y =
            (int)($_POST["y"] ?? 90);


        $encontrado = false;


        foreach (
            $mapa["sectores"]
            as &$sector
        ) {

            if (
                (int)$sector["id"] === $id
            ) {

                $sector["x"] =
                    $x;

                $sector["y"] =
                    $y;

                /*
                   El sector siempre conserva
                   el mismo tamaño que una carpa.
                */

                $sector["ancho"] = 90;
                $sector["alto"] = 60;

                $encontrado = true;

                break;
            }
        }


        unset($sector);


        if (!$encontrado) {

            responderJSON([
                "ok" => false,
                "mensaje" =>
                    "Sector no encontrado"
            ], 404);
        }


        $guardado =
            guardarMapa(
                $archivoMapa,
                $mapa
            );


        responderJSON([
            "ok" => $guardado
        ]);
    }


    /* =====================================================
       CAMBIAR NOMBRE SECTOR
       ===================================================== */

    if ($accion === "renombrar_sector") {

        $id =
            (int)($_POST["id"] ?? 0);


        $nombre =
            trim(
                $_POST["nombre"] ?? ""
            );


        if ($nombre === "") {
            $nombre = "SECTOR";
        }


        $encontrado = false;


        foreach (
            $mapa["sectores"]
            as &$sector
        ) {

            if (
                (int)$sector["id"] === $id
            ) {

                $sector["nombre"] =
                    $nombre;

                $encontrado = true;

                break;
            }
        }


        unset($sector);


        if (!$encontrado) {

            responderJSON([
                "ok" => false,
                "mensaje" =>
                    "Sector no encontrado"
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


    /* =====================================================
       ELIMINAR SECTOR
       ===================================================== */

    if ($accion === "eliminar_sector") {

        $id =
            (int)($_POST["id"] ?? 0);


        $nuevosSectores = [];


        foreach (
            $mapa["sectores"]
            as $sector
        ) {

            if (
                (int)$sector["id"] !== $id
            ) {

                $nuevosSectores[] =
                    $sector;
            }
        }


        $mapa["sectores"] =
            $nuevosSectores;


        $guardado =
            guardarMapa(
                $archivoMapa,
                $mapa
            );


        responderJSON([
            "ok" => $guardado
        ]);
    }


    /* =====================================================
       GUARDAR CAMINO
       ===================================================== */

    if ($accion === "guardar_camino") {

        $id =
            (int)($_POST["id"] ?? 0);


        $x =
            (int)($_POST["x"] ?? 0);


        $encontrado = false;


        foreach (
            $mapa["caminos"]
            as &$camino
        ) {

            if (
                (int)$camino["id"] === $id
            ) {

                $camino["x"] =
                    $x;

                $encontrado = true;

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


    /* =====================================================
       ACCIÓN DESCONOCIDA
       ===================================================== */

    responderJSON([

        "ok" => false,

        "mensaje" =>
            "Acción desconocida"

    ], 400);
}


/* =========================================================
   CARGAR CARPAS
   ========================================================= */

require_once "conexion.php";


$carpas = $conexion->query("
    SELECT *
    FROM carpas
    WHERE activa = 1
    ORDER BY sector, numero
");


require_once "header.php";

?>

<style>

.editor-map {
    position: relative;
    width: 100%;
    min-width: 900px;
    height: 850px;
    overflow: hidden;
    background: #e8cf91;
    border: 3px solid #c5a86b;
    border-radius: 18px;
    box-shadow:
        inset 0 0 0 2px
        rgba(255,255,255,.25);
}


.map-sand {
    position: absolute;
    inset: 0;
    background: #e8cf91;
    z-index: 1;
}


.map-water {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 90px;
    background: #55a9d6;
    border-bottom: 5px solid #e2c078;
    z-index: 2;
}


/* =========================================================
   SECCIONES
   ========================================================= */

.map-section {
    position: absolute;
    box-sizing: border-box;
    min-width: 150px;
    min-height: 100px;
    border: 3px dashed rgba(100,70,30,.55);
    border-radius: 12px;
    background: rgba(255,255,255,.08);
    z-index: 4;
    cursor: move;
    user-select: none;
}


.map-section:hover {
    background: rgba(255,255,255,.14);
}


.map-section.moving {
    border-color: #333;
    background: rgba(255,255,255,.22);
    z-index: 30;
    opacity: .9;
}


.section-label {
    position: absolute;
    left: 10px;
    top: 8px;
    padding: 5px 9px;
    background: rgba(255,255,255,.85);
    border-radius: 5px;
    font-size: 13px;
    font-weight: bold;
    color: #5a421f;
    pointer-events: none;
}


.section-buttons {
    position: absolute;
    top: 7px;
    right: 7px;
    display: flex;
    gap: 4px;
    z-index: 25;
}


.section-button {
    width: 26px;
    height: 26px;
    border: none;
    border-radius: 5px;
    color: white;
    font-weight: bold;
    cursor: pointer;
}


.section-edit {
    background: #6b8e23;
}


.section-delete {
    background: #b33a3a;
}


.section-button:hover {
    filter: brightness(.88);
}


.section-resize {
    position: absolute;
    width: 18px;
    height: 18px;
    right: -3px;
    bottom: -3px;
    background: #6b4e2e;
    border: 2px solid white;
    border-radius: 4px;
    cursor: nwse-resize;
    z-index: 30;
}


/* =========================================================
   SECTORES
   ========================================================= */

/*
   LOS SECTORES AHORA SON VISUALMENTE IGUALES
   A LAS CARPAS.
*/

.editor-sector {
    position: absolute;

    width: 90px;
    height: 60px;

    background: white;

    border: 3px solid #6b4e2e;

    border-radius: 10px;

    display: flex;
    flex-direction: column;

    justify-content: center;
    align-items: center;

    text-align: center;

    font-size: 13px;

    cursor: grab;

    z-index: 10;

    box-shadow:
        0 3px 8px
        rgba(0,0,0,.25);

    user-select: none;
}


.editor-sector strong {
    font-size: 16px;
}


.editor-sector span {
    font-size: 11px;
    color: #666;

    max-width: 80px;

    overflow: hidden;

    text-overflow: ellipsis;

    white-space: nowrap;
}


.editor-sector.dragging {
    cursor: grabbing;
    opacity: .75;
    transform: scale(1.05);
    z-index: 35;
}


/* =========================================================
   BOTONES DEL SECTOR
   ========================================================= */

.sector-buttons {
    position: absolute;

    top: -10px;
    right: -10px;

    display: none;

    gap: 3px;

    z-index: 40;
}


.editor-sector:hover .sector-buttons {
    display: flex;
}


.sector-button {
    width: 22px;
    height: 22px;

    padding: 0;

    border: none;

    border-radius: 50%;

    color: white;

    font-size: 12px;

    font-weight: bold;

    cursor: pointer;
}


.sector-edit {
    background: #6b8e23;
}


.sector-delete {
    background: #b33a3a;
}


.sector-button:hover {
    filter: brightness(.88);
}


/* =========================================================
   CAMINOS
   ========================================================= */

.map-path {
    position: absolute;
    top: 0;
    bottom: 0;

    width: 55px;

    background: #c6a36b;

    border-left: 2px solid #a6814d;
    border-right: 2px solid #a6814d;

    box-shadow:
        inset 0 0 6px
        rgba(0,0,0,.10);

    z-index: 6;

    cursor: ew-resize;
}


.map-path::before {
    content: "";

    position: absolute;

    left: 50%;
    top: 0;
    bottom: 0;

    border-left: 2px dashed
        rgba(255,255,255,.60);
}


/* =========================================================
   CARPAS
   ========================================================= */

.editor-tent {
    position: absolute;

    width: 90px;
    height: 60px;

    background: white;

    border: 3px solid #6b4e2e;

    border-radius: 10px;

    display: flex;
    flex-direction: column;

    justify-content: center;
    align-items: center;

    text-align: center;

    font-size: 13px;

    cursor: grab;

    z-index: 10;

    box-shadow:
        0 3px 8px
        rgba(0,0,0,.25);

    user-select: none;
}


.editor-tent strong {
    font-size: 16px;
}


.editor-tent span {
    font-size: 11px;
    color: #666;
}


.editor-tent.dragging {
    cursor: grabbing;
    opacity: .75;
    transform: scale(1.05);
}


/* =========================================================
   BOTONES
   ========================================================= */

.add-section-button,
.add-sector-button {
    width: 100%;

    margin-top: 15px;

    padding: 11px 15px;

    border: none;

    border-radius: 8px;

    color: white;

    font-weight: bold;

    cursor: pointer;
}


.add-section-button {
    background: #6b4e2e;
}


.add-sector-button {
    background: #6b4e2e;
}


.add-section-button:hover,
.add-sector-button:hover {
    filter: brightness(.9);
}


.add-section-button:disabled,
.add-sector-button:disabled {
    opacity: .6;
    cursor: wait;
}


#saveMessage {
    min-height: 20px;
}

</style>


<div class="card-soft mb-4">

<h2 class="section-title">
    Editor visual de posiciones
</h2>

<p class="text-muted mb-0">
    Mové las carpas, sectores, secciones y caminos
    directamente sobre el plano.
    Los cambios se guardan automáticamente.
</p>

</div>


<div class="row g-4">


<div class="col-lg-9">

    <div class="card-soft">

        <div
            class="editor-map"
            id="editorMap"
        >

            <div class="map-sand"></div>

            <div class="map-water"></div>


            <!-- =====================================================
                 SECTORES
                 ===================================================== -->

            <?php foreach (
                $mapa["sectores"]
                as $sector
            ): ?>

                <div
                    class="editor-sector"
                    draggable="true"
                    data-id="<?= (int)$sector["id"] ?>"
                    style="
                        left: <?= (int)$sector["x"] ?>px;
                        top: <?= (int)$sector["y"] ?>px;
                    "
                >

                    <strong>
                        #<?= str_pad(
                            (int)$sector["id"],
                            2,
                            "0",
                            STR_PAD_LEFT
                        ) ?>
                    </strong>

                    <span>
                        <?= htmlspecialchars(
                            $sector["nombre"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </span>


                    <div class="sector-buttons">

                        <button
                            type="button"
                            class="sector-button sector-edit"
                            title="Cambiar nombre"
                        >
                            ✎
                        </button>


                        <button
                            type="button"
                            class="sector-button sector-delete"
                            title="Eliminar sector"
                        >
                            ×
                        </button>

                    </div>

                </div>

            <?php endforeach; ?>


            <!-- =====================================================
                 SECCIONES
                 ===================================================== -->

            <?php foreach (
                $mapa["secciones"]
                as $s
            ): ?>

                <div
                    class="map-section"
                    data-id="<?= (int)$s["id"] ?>"
                    style="
                        left: <?= (int)$s["x"] ?>px;
                        top: <?= (int)$s["y"] ?>px;
                        width: <?= (int)$s["ancho"] ?>px;
                        height: <?= (int)$s["alto"] ?>px;
                    "
                >

                    <div class="section-label">

                        <?= htmlspecialchars(
                            $s["nombre"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>

                    </div>


                    <div class="section-buttons">

                        <button
                            type="button"
                            class="section-button section-edit"
                            title="Cambiar nombre"
                        >
                            ✎
                        </button>


                        <button
                            type="button"
                            class="section-button section-delete"
                            title="Eliminar sección"
                        >
                            ×
                        </button>

                    </div>


                    <div class="section-resize"></div>

                </div>

            <?php endforeach; ?>


            <!-- =====================================================
                 CAMINOS
                 ===================================================== -->

            <?php foreach (
                $mapa["caminos"]
                as $camino
            ): ?>

                <div
                    class="map-path"
                    data-id="<?= (int)$camino["id"] ?>"
                    title="<?= htmlspecialchars(
                        $camino["nombre"],
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                    style="
                        left: <?= (int)$camino["x"] ?>px;
                        width: <?= (int)$camino["ancho"] ?>px;
                    "
                ></div>

            <?php endforeach; ?>


            <!-- =====================================================
                 CARPAS
                 ===================================================== -->

            <?php if ($carpas): ?>

                <?php while (
                    $c =
                    $carpas->fetch_assoc()
                ): ?>

                    <div
                        class="editor-tent"
                        draggable="true"
                        data-id="<?= (int)$c["id"] ?>"
                        style="
                            left: <?= (int)$c["pos_x"] ?>px;
                            top: <?= (int)$c["pos_y"] ?>px;
                        "
                    >

                        <strong>
                            #<?= htmlspecialchars(
                                $c["numero"],
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </strong>

                        <span>
                            <?= htmlspecialchars(
                                $c["sector"],
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </span>

                    </div>

                <?php endwhile; ?>

            <?php endif; ?>


        </div>

    </div>

</div>


<div class="col-lg-3">

    <div class="card-soft">

        <h3 class="h5">
            Editor del mapa
        </h3>


        <p class="small text-muted">
            Todos los cambios se guardan automáticamente.
        </p>


        <ul class="small text-muted ps-3">

            <li>🖱️ Mover carpas</li>

            <li>🟫 Mover sectores</li>

            <li>🟫 Mover secciones</li>

            <li>↔️ Cambiar tamaño de secciones</li>

            <li>✎ Cambiar nombre</li>

            <li>× Eliminar</li>

            <li>🛣️ Mover caminos</li>

        </ul>


        <button
            type="button"
            id="addSectorButton"
            class="add-sector-button"
        >
            + Añadir sector
        </button>


        <button
            type="button"
            id="addSectionButton"
            class="add-section-button"
        >
            + Añadir sección
        </button>


        <div
            id="saveMessage"
            class="small mt-3"
        ></div>

    </div>

</div>

</div>


<script>

/* =====================================================
   VARIABLES
   ===================================================== */

const editorMap =
    document.getElementById("editorMap");


const saveMessage =
    document.getElementById("saveMessage");


/* =====================================================
   MENSAJE
   ===================================================== */

function mostrarMensaje(
    texto,
    error = false
) {

    saveMessage.textContent =
        texto;


    saveMessage.className =
        error
            ? "small text-danger mt-3"
            : "small text-success mt-3";


    clearTimeout(
        window.mensajeTimer
    );


    window.mensajeTimer =
        setTimeout(
            () => {

                saveMessage.textContent = "";

            },
            2200
        );
}


/* =====================================================
   ESCAPAR HTML
   ===================================================== */

function escapeHTML(texto)
{
    const div =
        document.createElement("div");

    div.textContent =
        texto;

    return div.innerHTML;
}


/* =====================================================
   PETICIÓN
   ===================================================== */

async function enviar(datos)
{

    const respuesta =
        await fetch(
            window.location.href,
            {
                method: "POST",

                headers: {
                    "Content-Type":
                        "application/x-www-form-urlencoded;charset=UTF-8",

                    "X-Requested-With":
                        "XMLHttpRequest"
                },

                body:
                    new URLSearchParams(datos)
            }
        );


    const texto =
        await respuesta.text();


    console.log(
        "Respuesta del servidor:",
        texto
    );


    if (!respuesta.ok) {

        throw new Error(
            "Error HTTP " +
            respuesta.status
        );
    }


    let resultado;


    try {

        resultado =
            JSON.parse(texto);

    }
    catch (e) {

        console.error(
            "Respuesta que no es JSON:",
            texto
        );

        throw new Error(
            "El servidor no devolvió JSON válido"
        );
    }


    if (
        typeof resultado !== "object" ||
        resultado === null
    ) {

        throw new Error(
            "Respuesta inválida del servidor"
        );
    }


    return resultado;
}


/* =====================================================
   CARPAS
   ===================================================== */

let carpaArrastrada = null;


document
    .querySelectorAll(".editor-tent")
    .forEach(
        carpa => {

            carpa.addEventListener(
                "dragstart",
                () => {

                    carpaArrastrada =
                        carpa;

                    carpa.classList.add(
                        "dragging"
                    );

                }
            );


            carpa.addEventListener(
                "dragend",
                () => {

                    carpa.classList.remove(
                        "dragging"
                    );

                }
            );

        }
    );


editorMap.addEventListener(
    "dragover",
    e => {

        e.preventDefault();

    }
);


editorMap.addEventListener(
    "drop",
    async e => {

        e.preventDefault();


        const rect =
            editorMap.getBoundingClientRect();


        /* =================================================
           DROP DE CARPA
           ================================================= */

        if (carpaArrastrada) {

            let x =
                e.clientX -
                rect.left -
                45;


            let y =
                e.clientY -
                rect.top -
                30;


            const ancho =
                carpaArrastrada.offsetWidth;


            const alto =
                carpaArrastrada.offsetHeight;


            x = Math.max(
                0,
                Math.min(
                    Math.round(x),
                    editorMap.clientWidth -
                    ancho
                )
            );


            y = Math.max(
                90,
                Math.min(
                    Math.round(y),
                    editorMap.clientHeight -
                    alto
                )
            );


            const carpa =
                carpaArrastrada;


            carpa.style.left =
                x + "px";


            carpa.style.top =
                y + "px";


            carpaArrastrada = null;


            try {

                const resultado =
                    await enviar({

                        accion:
                            "guardar_carpa",

                        id:
                            carpa.dataset.id,

                        x:
                            x,

                        y:
                            y

                    });


                if (!resultado.ok) {

                    throw new Error(
                        resultado.mensaje ||
                        "No se pudo guardar la carpa"
                    );
                }


                mostrarMensaje(
                    "✓ Carpa guardada"
                );

            }
            catch (error) {

                console.error(error);

                mostrarMensaje(
                    "✕ " +
                    error.message,
                    true
                );
            }

            return;
        }


        /* =================================================
           DROP DE SECTOR
           ================================================= */

        if (sectorArrastrado) {

            let x =
                e.clientX -
                rect.left -
                45;


            let y =
                e.clientY -
                rect.top -
                30;


            const ancho =
                sectorArrastrado.offsetWidth;


            const alto =
                sectorArrastrado.offsetHeight;


            x = Math.max(
                0,
                Math.min(
                    Math.round(x),
                    editorMap.clientWidth -
                    ancho
                )
            );


            y = Math.max(
                90,
                Math.min(
                    Math.round(y),
                    editorMap.clientHeight -
                    alto
                )
            );


            const sector =
                sectorArrastrado;


            sector.style.left =
                x + "px";


            sector.style.top =
                y + "px";


            sectorArrastrado = null;


            try {

                const resultado =
                    await enviar({

                        accion:
                            "guardar_sector",

                        id:
                            sector.dataset.id,

                        x:
                            x,

                        y:
                            y

                    });


                if (!resultado.ok) {

                    throw new Error(
                        resultado.mensaje ||
                        "No se pudo guardar el sector"
                    );
                }


                mostrarMensaje(
                    "✓ Sector guardado"
                );

            }
            catch (error) {

                console.error(error);

                mostrarMensaje(
                    "✕ " +
                    error.message,
                    true
                );
            }

        }

    }
);


/* =====================================================
   SECTORES
   ===================================================== */

let sectorArrastrado = null;


function configurarSector(
    sector
)
{

    sector.setAttribute(
        "draggable",
        "true"
    );


    sector.addEventListener(
        "dragstart",
        e => {

            /*
               Si se arrastra desde un botón,
               no iniciamos el movimiento.
            */

            if (
                e.target.closest(
                    ".sector-buttons"
                )
            ) {

                e.preventDefault();

                return;
            }


            sectorArrastrado =
                sector;


            sector.classList.add(
                "dragging"
            );

        }
    );


    sector.addEventListener(
        "dragend",
        () => {

            sector.classList.remove(
                "dragging"
            );

        }
    );


    /* =================================================
       EDITAR NOMBRE
       ================================================= */

    const editar =
        sector.querySelector(
            ".sector-edit"
        );


    editar.addEventListener(
        "click",
        async e => {

            e.preventDefault();
            e.stopPropagation();


            const label =
                sector.querySelector(
                    "span"
                );


            const nombre =
                prompt(
                    "Nombre del sector:",
                    label.textContent.trim()
                );


            if (
                nombre === null ||
                nombre.trim() === ""
            ) {
                return;
            }


            try {

                const resultado =
                    await enviar({

                        accion:
                            "renombrar_sector",

                        id:
                            sector.dataset.id,

                        nombre:
                            nombre.trim()

                    });


                if (!resultado.ok) {

                    throw new Error(
                        resultado.mensaje ||
                        "No se pudo cambiar el nombre"
                    );
                }


                label.textContent =
                    resultado.nombre;


                mostrarMensaje(
                    "✓ Sector renombrado"
                );

            }
            catch (error) {

                console.error(error);

                mostrarMensaje(
                    "✕ " +
                    error.message,
                    true
                );

            }

        }
    );


    /* =================================================
       ELIMINAR
       ================================================= */

    const eliminar =
        sector.querySelector(
            ".sector-delete"
        );


    eliminar.addEventListener(
        "click",
        async e => {

            e.preventDefault();
            e.stopPropagation();


            const nombre =
                sector.querySelector(
                    "span"
                )
                .textContent
                .trim();


            if (
                !confirm(
                    "¿Eliminar " +
                    nombre +
                    "?"
                )
            ) {
                return;
            }


            try {

                const resultado =
                    await enviar({

                        accion:
                            "eliminar_sector",

                        id:
                            sector.dataset.id

                    });


                if (!resultado.ok) {

                    throw new Error(
                        resultado.mensaje ||
                        "No se pudo eliminar el sector"
                    );
                }


                sector.remove();


                mostrarMensaje(
                    "✓ Sector eliminado"
                );

            }
            catch (error) {

                console.error(error);

                mostrarMensaje(
                    "✕ " +
                    error.message,
                    true
                );

            }

        }
    );

}


/* =====================================================
   CONFIGURAR SECTORES EXISTENTES
   ===================================================== */

document
    .querySelectorAll(
        ".editor-sector"
    )
    .forEach(
        configurarSector
    );


/* =====================================================
   AÑADIR SECTOR
   ===================================================== */

document
    .getElementById(
        "addSectorButton"
    )
    .addEventListener(
        "click",
        async () => {

            const boton =
                document.getElementById(
                    "addSectorButton"
                );


            const nombre =
                prompt(
                    "Nombre del nuevo sector:",
                    "Sector A"
                );


            if (
                nombre === null ||
                nombre.trim() === ""
            ) {
                return;
            }


            boton.disabled = true;


            boton.textContent =
                "Guardando...";


            try {

                const resultado =
                    await enviar({

                        accion:
                            "crear_sector",

                        nombre:
                            nombre.trim()

                    });


                console.log(
                    "Resultado crear sector:",
                    resultado
                );


                if (
                    resultado.ok !== true
                ) {

                    throw new Error(
                        resultado.mensaje ||
                        "El servidor no pudo crear el sector"
                    );
                }


                const sector =
                    document.createElement(
                        "div"
                    );


                sector.className =
                    "editor-sector";


                sector.setAttribute(
                    "draggable",
                    "true"
                );


                sector.dataset.id =
                    resultado.id;


                sector.style.left =
                    resultado.x + "px";


                sector.style.top =
                    resultado.y + "px";


                sector.innerHTML = `

                    <strong>
                        #${String(
                            resultado.id
                        ).padStart(2, "0")}
                    </strong>

                    <span>
                        ${escapeHTML(
                            resultado.nombre
                        )}
                    </span>

                    <div class="sector-buttons">

                        <button
                            type="button"
                            class="sector-button sector-edit"
                            title="Cambiar nombre"
                        >
                            ✎
                        </button>

                        <button
                            type="button"
                            class="sector-button sector-delete"
                            title="Eliminar sector"
                        >
                            ×
                        </button>

                    </div>

                `;


                editorMap.appendChild(
                    sector
                );


                configurarSector(
                    sector
                );


                mostrarMensaje(
                    "✓ Sector añadido correctamente"
                );

            }
            catch (error) {

                console.error(
                    "Error al añadir sector:",
                    error
                );


                mostrarMensaje(
                    "✕ " +
                    error.message,
                    true
                );

            }
            finally {

                boton.disabled = false;


                boton.textContent =
                    "+ Añadir sector";

            }

        }
    );


/* =====================================================
   CAMINOS
   ===================================================== */

let caminoMoviendo = null;

let caminoOffsetX = 0;


document
    .querySelectorAll(".map-path")
    .forEach(
        camino => {

            camino.addEventListener(
                "mousedown",
                e => {

                    caminoMoviendo =
                        camino;


                    const rect =
                        camino.getBoundingClientRect();


                    caminoOffsetX =
                        e.clientX -
                        rect.left;


                    e.preventDefault();

                }
            );

        }
    );


document.addEventListener(
    "mousemove",
    e => {

        if (!caminoMoviendo) {
            return;
        }


        const rect =
            editorMap.getBoundingClientRect();


        let x =
            e.clientX -
            rect.left -
            caminoOffsetX;


        const ancho =
            caminoMoviendo.offsetWidth;


        x = Math.max(
            0,
            Math.min(
                x,
                editorMap.clientWidth -
                ancho
            )
        );


        caminoMoviendo.style.left =
            Math.round(x) + "px";

    }
);


document.addEventListener(
    "mouseup",
    async () => {

        if (!caminoMoviendo) {
            return;
        }


        const camino =
            caminoMoviendo;


        caminoMoviendo = null;


        const x =
            parseInt(
                camino.style.left
            ) || 0;


        try {

            const resultado =
                await enviar({

                    accion:
                        "guardar_camino",

                    id:
                        camino.dataset.id,

                    x:
                        x

                });


            if (!resultado.ok) {

                throw new Error(
                    resultado.mensaje ||
                    "No se pudo guardar el camino"
                );
            }


            mostrarMensaje(
                "✓ Camino guardado"
            );

        }
        catch (error) {

            console.error(error);

            mostrarMensaje(
                "✕ " +
                error.message,
                true
            );
        }

    }
);


/* =====================================================
   SECCIONES
   ===================================================== */

function configurarSeccion(
    section
)
{

    section.addEventListener(
        "mousedown",
        e => {

            if (
                e.target.closest(
                    ".section-buttons"
                )
            ) {
                return;
            }


            if (
                e.target.closest(
                    ".section-resize"
                )
            ) {
                return;
            }


            iniciarMovimiento(
                section,
                e
            );

        }
    );


    const editar =
        section.querySelector(
            ".section-edit"
        );


    editar.addEventListener(
        "click",
        async e => {

            e.stopPropagation();


            const label =
                section.querySelector(
                    ".section-label"
                );


            const nombre =
                prompt(
                    "Nombre de la sección:",
                    label.textContent.trim()
                );


            if (
                nombre === null ||
                nombre.trim() === ""
            ) {
                return;
            }


            try {

                const resultado =
                    await enviar({

                        accion:
                            "renombrar_seccion",

                        id:
                            section.dataset.id,

                        nombre:
                            nombre.trim()

                    });


                if (!resultado.ok) {

                    throw new Error(
                        resultado.mensaje ||
                        "No se pudo cambiar el nombre"
                    );
                }


                label.textContent =
                    resultado.nombre;


                mostrarMensaje(
                    "✓ Nombre cambiado"
                );

            }
            catch (error) {

                console.error(error);

                mostrarMensaje(
                    "✕ " +
                    error.message,
                    true
                );
            }

        }
    );


    const eliminar =
        section.querySelector(
            ".section-delete"
        );


    eliminar.addEventListener(
        "click",
        async e => {

            e.stopPropagation();


            const nombre =
                section.querySelector(
                    ".section-label"
                )
                .textContent
                .trim();


            if (
                !confirm(
                    "¿Eliminar " +
                    nombre +
                    "?"
                )
            ) {
                return;
            }


            try {

                const resultado =
                    await enviar({

                        accion:
                            "eliminar_seccion",

                        id:
                            section.dataset.id

                    });


                if (!resultado.ok) {

                    throw new Error(
                        resultado.mensaje ||
                        "No se pudo eliminar"
                    );
                }


                section.remove();


                mostrarMensaje(
                    "✓ Sección eliminada"
                );

            }
            catch (error) {

                console.error(error);

                mostrarMensaje(
                    "✕ " +
                    error.message,
                    true
                );
            }

        }
    );


    const resize =
        section.querySelector(
            ".section-resize"
        );


    resize.addEventListener(
        "mousedown",
        e => {

            e.preventDefault();
            e.stopPropagation();


            const inicioX =
                e.clientX;


            const inicioY =
                e.clientY;


            const inicioAncho =
                section.offsetWidth;


            const inicioAlto =
                section.offsetHeight;


            function moverResize(ev)
            {

                let ancho =
                    inicioAncho +
                    (
                        ev.clientX -
                        inicioX
                    );


                let alto =
                    inicioAlto +
                    (
                        ev.clientY -
                        inicioY
                    );


                ancho =
                    Math.max(
                        150,
                        ancho
                    );


                alto =
                    Math.max(
                        100,
                        alto
                    );


                const x =
                    parseInt(
                        section.style.left
                    ) || 0;


                const y =
                    parseInt(
                        section.style.top
                    ) || 90;


                ancho =
                    Math.min(
                        ancho,
                        editorMap.clientWidth -
                        x
                    );


                alto =
                    Math.min(
                        alto,
                        editorMap.clientHeight -
                        y
                    );


                section.style.width =
                    ancho + "px";


                section.style.height =
                    alto + "px";

            }


            async function terminarResize()
            {

                document.removeEventListener(
                    "mousemove",
                    moverResize
                );


                document.removeEventListener(
                    "mouseup",
                    terminarResize
                );


                await guardarSeccion(
                    section
                );

            }


            document.addEventListener(
                "mousemove",
                moverResize
            );


            document.addEventListener(
                "mouseup",
                terminarResize
            );

        }
    );

}


/* =====================================================
   MOVIMIENTO DE SECCIÓN
   ===================================================== */

let seccionMoviendo = null;

let offsetSeccionX = 0;

let offsetSeccionY = 0;


function iniciarMovimiento(
    section,
    e
)
{

    seccionMoviendo =
        section;


    const rect =
        section.getBoundingClientRect();


    offsetSeccionX =
        e.clientX -
        rect.left;


    offsetSeccionY =
        e.clientY -
        rect.top;


    section.classList.add(
        "moving"
    );


    e.preventDefault();

}


/* =====================================================
   MOVIMIENTO
   ===================================================== */

document.addEventListener(
    "mousemove",
    e => {

        if (!seccionMoviendo) {
            return;
        }


        const rect =
            editorMap.getBoundingClientRect();


        const ancho =
            seccionMoviendo.offsetWidth;


        const alto =
            seccionMoviendo.offsetHeight;


        let x =
            e.clientX -
            rect.left -
            offsetSeccionX;


        let y =
            e.clientY -
            rect.top -
            offsetSeccionY;


        x = Math.max(
            0,
            Math.min(
                x,
                editorMap.clientWidth -
                ancho
            )
        );


        y = Math.max(
            90,
            Math.min(
                y,
                editorMap.clientHeight -
                alto
            )
        );


        seccionMoviendo.style.left =
            Math.round(x) + "px";


        seccionMoviendo.style.top =
            Math.round(y) + "px";

    }
);


/* =====================================================
   TERMINAR MOVIMIENTO SECCIÓN
   ===================================================== */

document.addEventListener(
    "mouseup",
    async () => {

        if (!seccionMoviendo) {
            return;
        }


        const section =
            seccionMoviendo;


        seccionMoviendo = null;


        section.classList.remove(
            "moving"
        );


        await guardarSeccion(
            section
        );

    }
);


/* =====================================================
   GUARDAR SECCIÓN
   ===================================================== */

async function guardarSeccion(
    section
)
{

    const x =
        parseInt(
            section.style.left
        ) || 0;


    const y =
        parseInt(
            section.style.top
        ) || 90;


    const ancho =
        section.offsetWidth;


    const alto =
        section.offsetHeight;


    try {

        const resultado =
            await enviar({

                accion:
                    "guardar_seccion",

                id:
                    section.dataset.id,

                x:
                    x,

                y:
                    y,

                ancho:
                    ancho,

                alto:
                    alto

            });


        if (!resultado.ok) {

            throw new Error(
                resultado.mensaje ||
                "No se pudo guardar la sección"
            );
        }


        mostrarMensaje(
            "✓ Sección guardada"
        );

    }
    catch (error) {

        console.error(error);

        mostrarMensaje(
            "✕ " +
            error.message,
            true
        );

    }

}


/* =====================================================
   CONFIGURAR SECCIONES EXISTENTES
   ===================================================== */

document
    .querySelectorAll(
        ".map-section"
    )
    .forEach(
        configurarSeccion
    );


/* =====================================================
   AÑADIR SECCIÓN
   ===================================================== */

document
    .getElementById(
        "addSectionButton"
    )
    .addEventListener(
        "click",
        async () => {

            const boton =
                document.getElementById(
                    "addSectionButton"
                );


            const nombre =
                prompt(
                    "Nombre de la nueva sección:",
                    "NUEVA SECCIÓN"
                );


            if (
                nombre === null ||
                nombre.trim() === ""
            ) {
                return;
            }


            boton.disabled = true;


            boton.textContent =
                "Guardando...";


            try {

                const resultado =
                    await enviar({

                        accion:
                            "crear_seccion",

                        nombre:
                            nombre.trim()

                    });


                console.log(
                    "Resultado crear sección:",
                    resultado
                );


                if (
                    resultado.ok !== true
                ) {

                    throw new Error(
                        resultado.mensaje ||
                        "El servidor no pudo crear la sección"
                    );
                }


                const section =
                    document.createElement(
                        "div"
                    );


                section.className =
                    "map-section";


                section.dataset.id =
                    resultado.id;


                section.style.left =
                    resultado.x + "px";


                section.style.top =
                    resultado.y + "px";


                section.style.width =
                    resultado.ancho + "px";


                section.style.height =
                    resultado.alto + "px";


                section.innerHTML = `

                    <div class="section-label">
                        ${escapeHTML(
                            resultado.nombre
                        )}
                    </div>

                    <div class="section-buttons">

                        <button
                            type="button"
                            class="section-button section-edit"
                            title="Cambiar nombre"
                        >
                            ✎
                        </button>

                        <button
                            type="button"
                            class="section-button section-delete"
                            title="Eliminar sección"
                        >
                            ×
                        </button>

                    </div>

                    <div class="section-resize"></div>

                `;


                editorMap.appendChild(
                    section
                );


                configurarSeccion(
                    section
                );


                mostrarMensaje(
                    "✓ Sección añadida correctamente"
                );

            }
            catch (error) {

                console.error(
                    "Error al añadir sección:",
                    error
                );


                mostrarMensaje(
                    "✕ " +
                    error.message,
                    true
                );

            }
            finally {

                boton.disabled = false;

                boton.textContent =
                    "+ Añadir sección";

            }

        }
    );

</script>


<?php

require_once "footer.php";

?>
```

Listo: **los sectores antiguos ya no tienen su sistema de rectángulo azul ni el movimiento/redimensionado antiguo**. Ahora son objetos independientes con el mismo aspecto y comportamiento de arrastre que las carpas.

Una cosa importante: **no hace falta modificar `conexion.php`, `header.php`, `footer.php` ni la tabla `carpas` para este cambio**.
