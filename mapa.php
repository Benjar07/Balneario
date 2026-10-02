```php
<?php

$titulo = "Mapa interactivo de carpas";

require_once "header.php";

/*
|--------------------------------------------------------------------------
| CARGAR CONFIGURACIÓN DEL MAPA
|--------------------------------------------------------------------------
*/

$archivoConfig = __DIR__ . "/mapa_config.json";

$config = [
    "secciones" => [],
    "caminos" => []
];

if (file_exists($archivoConfig)) {

    $contenido = file_get_contents($archivoConfig);

    if ($contenido !== false) {

        $datos = json_decode($contenido, true);

        if (is_array($datos)) {
            $config = array_merge($config, $datos);
        }
    }
}

$secciones = [];

if (isset($config["secciones"]) && is_array($config["secciones"])) {
    $secciones = $config["secciones"];
}

$caminos = [];

if (isset($config["caminos"]) && is_array($config["caminos"])) {
    $caminos = $config["caminos"];
}

?>

<style>

/* =========================================================
   CONTENEDOR DEL VISOR
   ========================================================= */

.mapa-visor {

    position: relative;

    width: 100%;

    height: 750px;

    overflow: hidden;

    border-radius: 18px;

    border: 1px solid #c9dcea;

    background: #d7edf9;

    box-shadow:
        0 8px 30px rgba(22,34,51,.08);

    cursor: grab;

    user-select: none;

    touch-action: none;
}

.mapa-visor.arrastrando {
    cursor: grabbing;
}


/* =========================================================
   BOTONES DE ZOOM
   ========================================================= */

.mapa-controles {

    position: absolute;

    top: 15px;
    left: 15px;

    z-index: 999;

    display: flex;

    align-items: center;

    gap: 5px;

    padding: 6px;

    background: rgba(255,255,255,.95);

    border: 1px solid rgba(0,0,0,.08);

    border-radius: 12px;

    box-shadow:
        0 5px 18px rgba(0,0,0,.15);
}

.mapa-controles button {

    width: 40px;
    height: 40px;

    border: 0;

    border-radius: 9px;

    background: #f1f4f8;

    color: #172033;

    font-size: 21px;

    font-weight: 800;

    cursor: pointer;

    display: grid;

    place-items: center;
}

.mapa-controles button:hover {
    background: #dfe7ef;
}

.mapa-zoom {

    min-width: 55px;

    text-align: center;

    font-size: 13px;

    font-weight: 800;

    color: #4b5563;
}


/* =========================================================
   LIENZO
   ========================================================= */

.mapa-canvas {

    position: absolute;

    left: 0;
    top: 0;

    width: 1000px;

    height: 850px;

    transform-origin: 0 0;

    will-change: transform;
}


/* =========================================================
   MAR
   ========================================================= */

.mapa-canvas .map-water {

    position: absolute;

    left: 0;
    top: 0;

    width: 100%;
    height: 28%;

    background:
        linear-gradient(
            180deg,
            #69c6ee 0%,
            #91d7f4 55%,
            #c9eaf7 100%
        );

    z-index: 0;
}


/* =========================================================
   ARENA
   ========================================================= */

.mapa-canvas .map-sand {

    position: absolute;

    left: 0;
    top: 28%;

    width: 100%;
    height: 72%;

    background:
        linear-gradient(
            135deg,
            #f7e3b7 0%,
            #f3d49b 45%,
            #edc784 100%
        );

    z-index: 0;
}


/* =========================================================
   BORDE MAR / ARENA
   ========================================================= */

.mapa-canvas .linea-mar {

    position: absolute;

    left: 0;
    top: 28%;

    width: 100%;

    height: 4px;

    background: rgba(255,255,255,.7);

    z-index: 2;
}


/* =========================================================
   TEXTOS
   ========================================================= */

.mapa-canvas .map-label {

    position: absolute;

    z-index: 50;

    color: rgba(0,0,0,.40);

    font-weight: 900;

    letter-spacing: .14em;

    pointer-events: none;
}

.mapa-canvas .map-title-label {

    left: 25px;
    top: 20px;
}

.mapa-canvas .sea-label {

    right: 25px;
    top: 20px;
}


/* =========================================================
   CAPAS
   ========================================================= */

#pathsLayer,
#sectionsLayer,
#tentsLayer {

    position: absolute;

    left: 0;
    top: 0;

    width: 100%;
    height: 100%;
}


/* =========================================================
   CAMINOS
   ========================================================= */

#pathsLayer {
    z-index: 5;

    pointer-events: none;
}

.map-path {

    position: absolute;

    background:
        linear-gradient(
            90deg,
            rgba(255,255,255,.60),
            rgba(255,255,255,.95),
            rgba(255,255,255,.60)
        );

    border-left:
        2px dashed rgba(120,95,55,.35);

    border-right:
        2px dashed rgba(120,95,55,.35);

    box-shadow:
        inset 0 0 10px rgba(0,0,0,.08);
}


/* =========================================================
   SECCIONES
   ========================================================= */

#sectionsLayer {
    z-index: 10;

    pointer-events: none;
}

.map-section {

    position: absolute;

    box-sizing: border-box;

    border:
        3px solid rgba(13,110,253,.55);

    border-radius: 15px;

    background:
        rgba(13,110,253,.09);

    box-shadow:
        inset 0 0 0 1px rgba(255,255,255,.5);

    pointer-events: none;
}


/* =========================================================
   NOMBRE DEL SECTOR
   ========================================================= */

.map-section-title {

    position: absolute;

    left: 10px;
    top: 9px;

    padding: 5px 10px;

    border-radius: 7px;

    background: rgba(255,255,255,.90);

    color: #174a8b;

    font-size: 13px;

    font-weight: 900;

    text-transform: uppercase;

    white-space: nowrap;

    box-shadow:
        0 2px 6px rgba(0,0,0,.08);
}


/* =========================================================
   CARPAS
   ========================================================= */

#tentsLayer {
    z-index: 100;
}

#tentsLayer .tent {

    position: absolute;

    width: 90px;
    height: 60px;

    border:
        2px solid rgba(0,0,0,.15);

    border-radius: 14px;

    display: flex;

    flex-direction: column;

    justify-content: center;

    align-items: center;

    color: #fff;

    cursor: pointer;

    user-select: none;

    transition:
        transform .15s ease,
        box-shadow .15s ease;
}

#tentsLayer .tent strong {
    font-size: .95rem;
}

#tentsLayer .tent span {
    font-size: .67rem;
}

#tentsLayer .tent:hover {

    transform: scale(1.06);

    box-shadow:
        0 8px 18px rgba(0,0,0,.18);
}

#tentsLayer .tent-disponible {
    background: #16a34a;
}

#tentsLayer .tent-pendiente {
    background: #eab308;

    color: #352b00;
}

#tentsLayer .tent-ocupado {
    background: #dc2626;
}


/* =========================================================
   INDICACIÓN
   ========================================================= */

.mapa-ayuda {

    position: absolute;

    right: 15px;
    bottom: 15px;

    z-index: 999;

    padding: 8px 12px;

    border-radius: 9px;

    background: rgba(255,255,255,.90);

    color: #596579;

    font-size: 12px;

    pointer-events: none;
}


/* =========================================================
   PANTALLAS CHICAS
   ========================================================= */

@media (max-width: 768px) {

    .mapa-visor {
        height: 600px;
    }

}

</style>


<!-- =========================================================
     FECHA
     ========================================================= -->

<div class="card-soft mb-4">

    <div class="row g-3 align-items-end">

        <div class="col-md-4">

            <label class="form-label">
                Fecha a consultar
            </label>

            <input
                type="date"
                id="fechaMapa"
                class="form-control"
                value="<?= date("Y-m-d") ?>"
            >

        </div>


        <div class="col-md-8">

            <div class="map-legend">

                <span>
                    <i class="dot available"></i>
                    Disponible
                </span>

                <span>
                    <i class="dot pending"></i>
                    Reservado / Pendiente
                </span>

                <span>
                    <i class="dot occupied"></i>
                    Ocupado
                </span>

            </div>

        </div>

    </div>

</div>


<!-- =========================================================
     MAPA
     ========================================================= -->

<div class="card-soft">

    <div
        class="mapa-visor"
        id="mapViewport"
    >


        <!-- CONTROLES -->

        <div class="mapa-controles">

            <button
                type="button"
                id="zoomOut"
                title="Alejar"
            >
                −
            </button>

            <div
                class="mapa-zoom"
                id="zoomLabel"
            >
                100%
            </div>

            <button
                type="button"
                id="zoomIn"
                title="Acercar"
            >
                +
            </button>

            <button
                type="button"
                id="resetMap"
                title="Centrar mapa"
            >
                ↺
            </button>

        </div>


        <!-- LIENZO -->

        <div
            class="mapa-canvas"
            id="mapCanvas"
        >


            <!-- MAR -->

            <div class="map-water"></div>


            <!-- ARENA -->

            <div class="map-sand"></div>


            <!-- LÍNEA -->

            <div class="linea-mar"></div>


            <!-- TEXTOS -->

            <div class="map-label map-title-label">
                PLANO DEL BALNEARIO
            </div>

            <div class="map-label sea-label">
                MAR
            </div>


            <!-- =================================================
                 CAMINOS
                 ================================================= -->

            <div id="pathsLayer">

                <?php foreach ($caminos as $camino): ?>

                    <?php

                    $x = (float)(
                        $camino["x"]
                        ?? $camino["pos_x"]
                        ?? 0
                    );

                    $y = $camino["y"]
                        ?? $camino["pos_y"]
                        ?? null;

                    $width = (float)(
                        $camino["width"]
                        ?? $camino["ancho"]
                        ?? 55
                    );

                    $height = $camino["height"]
                        ?? $camino["alto"]
                        ?? null;

                    ?>

                    <div
                        class="map-path"
                        style="
                            left: <?= $x ?>px;

                            <?php if ($y !== null): ?>
                                top: <?= (float)$y ?>px;
                            <?php else: ?>
                                top: 28%;
                            <?php endif; ?>

                            width: <?= $width ?>px;

                            <?php if ($height !== null): ?>
                                height: <?= (float)$height ?>px;
                            <?php else: ?>
                                height: 72%;
                            <?php endif; ?>
                        "
                    ></div>

                <?php endforeach; ?>

            </div>


            <!-- =================================================
                 SECCIONES
                 ================================================= -->

            <div id="sectionsLayer">

                <?php foreach ($secciones as $seccion): ?>

                    <?php

                    $x = (float)(
                        $seccion["x"]
                        ?? $seccion["pos_x"]
                        ?? 0
                    );

                    $y = (float)(
                        $seccion["y"]
                        ?? $seccion["pos_y"]
                        ?? 0
                    );

                    $width = (float)(
                        $seccion["ancho"]
                        ?? $seccion["width"]
                        ?? 250
                    );

                    $height = (float)(
                        $seccion["alto"]
                        ?? $seccion["height"]
                        ?? 180
                    );

                    $nombre =
                        $seccion["nombre"]
                        ?? $seccion["name"]
                        ?? $seccion["titulo"]
                        ?? "SECCIÓN";

                    ?>

                    <div
                        class="map-section"
                        style="
                            left: <?= $x ?>px;
                            top: <?= $y ?>px;
                            width: <?= $width ?>px;
                            height: <?= $height ?>px;
                        "
                    >

                        <div class="map-section-title">

                            <?= htmlspecialchars(
                                (string)$nombre,
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>


            <!-- =================================================
                 CARPAS
                 ================================================= -->

            <div id="tentsLayer"></div>


        </div>


        <div class="mapa-ayuda">
            🖱️ Arrastrá para mover · Ruedita para zoom
        </div>

    </div>

</div>


<!-- =========================================================
     MODAL
     ========================================================= -->

<div
    class="modal fade"
    id="carpaModal"
    tabindex="-1"
>

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content border-0 shadow">

            <div class="modal-header">

                <h5 class="modal-title">

                    Detalle de carpa

                    <span id="modalNumero"></span>

                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                ></button>

            </div>


            <div class="modal-body">

                <div
                    id="modalEstado"
                    class="mb-3"
                ></div>


                <div class="info-grid">

                    <div>
                        <small class="text-muted">Sector</small>
                        <div id="modalSector">-</div>
                    </div>

                    <div>
                        <small class="text-muted">Cliente</small>
                        <div id="modalCliente">-</div>
                    </div>

                    <div>
                        <small class="text-muted">DNI</small>
                        <div id="modalDni">-</div>
                    </div>

                    <div>
                        <small class="text-muted">Teléfono</small>
                        <div id="modalTelefono">-</div>
                    </div>

                    <div>
                        <small class="text-muted">Inicio</small>
                        <div id="modalInicio">-</div>
                    </div>

                    <div>
                        <small class="text-muted">Fin</small>
                        <div id="modalFin">-</div>
                    </div>

                    <div>
                        <small class="text-muted">Tipo</small>
                        <div id="modalTipo">-</div>
                    </div>

                    <div>
                        <small class="text-muted">Costo total</small>
                        <div id="modalCosto">-</div>
                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


<script>

/* =========================================================
   ZOOM + MOVIMIENTO
   ========================================================= */

const viewport = document.getElementById("mapViewport");
const canvas = document.getElementById("mapCanvas");

const zoomIn = document.getElementById("zoomIn");
const zoomOut = document.getElementById("zoomOut");
const resetMap = document.getElementById("resetMap");
const zoomLabel = document.getElementById("zoomLabel");

let escala = 1;

let posicionX = 0;
let posicionY = 0;

let arrastrando = false;

let inicioMouseX = 0;
let inicioMouseY = 0;

let posicionInicialX = 0;
let posicionInicialY = 0;


/* =========================================================
   APLICAR TRANSFORMACIÓN
   ========================================================= */

function actualizarMapa() {

    canvas.style.transform =
        `translate(${posicionX}px, ${posicionY}px) scale(${escala})`;

    zoomLabel.textContent =
        Math.round(escala * 100) + "%";
}


/* =========================================================
   ZOOM MANTENIENDO EL PUNTO DEL MOUSE
   ========================================================= */

function hacerZoom(nuevoZoom, mouseX = null, mouseY = null) {

    nuevoZoom = Math.max(
        0.35,
        Math.min(3, nuevoZoom)
    );

    if (
        mouseX !== null &&
        mouseY !== null
    ) {

        const puntoX =
            (mouseX - posicionX) / escala;

        const puntoY =
            (mouseY - posicionY) / escala;

        posicionX =
            mouseX - puntoX * nuevoZoom;

        posicionY =
            mouseY - puntoY * nuevoZoom;
    }

    escala = nuevoZoom;

    actualizarMapa();
}


/* =========================================================
   BOTÓN +
   ========================================================= */

zoomIn.addEventListener(
    "click",
    () => {

        hacerZoom(
            escala + .1
        );

    }
);


/* =========================================================
   BOTÓN -
   ========================================================= */

zoomOut.addEventListener(
    "click",
    () => {

        hacerZoom(
            escala - .1
        );

    }
);


/* =========================================================
   RESTABLECER
   ========================================================= */

resetMap.addEventListener(
    "click",
    () => {

        escala = 1;

        /*
         * Centrar automáticamente el lienzo.
         */

        posicionX =
            (viewport.clientWidth - canvas.offsetWidth) / 2;

        posicionY =
            (viewport.clientHeight - canvas.offsetHeight) / 2;

        actualizarMapa();

    }
);


/* =========================================================
   MOUSE DOWN
   ========================================================= */

viewport.addEventListener(
    "mousedown",
    (e) => {

        /*
         * No mover el mapa si se está haciendo click
         * en una carpa.
         */

        if (e.target.closest(".tent")) {
            return;
        }

        arrastrando = true;

        viewport.classList.add("arrastrando");

        inicioMouseX = e.clientX;
        inicioMouseY = e.clientY;

        posicionInicialX = posicionX;
        posicionInicialY = posicionY;

    }
);


/* =========================================================
   MOUSE MOVE
   ========================================================= */

window.addEventListener(
    "mousemove",
    (e) => {

        if (!arrastrando) return;

        posicionX =
            posicionInicialX +
            (e.clientX - inicioMouseX);

        posicionY =
            posicionInicialY +
            (e.clientY - inicioMouseY);

        actualizarMapa();

    }
);


/* =========================================================
   MOUSE UP
   ========================================================= */

window.addEventListener(
    "mouseup",
    () => {

        arrastrando = false;

        viewport.classList.remove("arrastrando");

    }
);


/* =========================================================
   RUEDA DEL MOUSE
   ========================================================= */

viewport.addEventListener(
    "wheel",
    (e) => {

        e.preventDefault();

        const rect =
            viewport.getBoundingClientRect();

        const mouseX =
            e.clientX - rect.left;

        const mouseY =
            e.clientY - rect.top;

        const cambio =
            e.deltaY < 0
                ? .1
                : -.1;

        hacerZoom(
            escala + cambio,
            mouseX,
            mouseY
        );

    },
    {
        passive: false
    }
);


/* =========================================================
   TOUCH
   ========================================================= */

let touchInicialX = 0;
let touchInicialY = 0;

let touchPosInicialX = 0;
let touchPosInicialY = 0;


viewport.addEventListener(
    "touchstart",
    (e) => {

        if (e.target.closest(".tent")) {
            return;
        }

        if (e.touches.length !== 1) {
            return;
        }

        const touch = e.touches[0];

        touchInicialX = touch.clientX;
        touchInicialY = touch.clientY;

        touchPosInicialX = posicionX;
        touchPosInicialY = posicionY;

    },
    {
        passive: true
    }
);


viewport.addEventListener(
    "touchmove",
    (e) => {

        if (e.touches.length !== 1) {
            return;
        }

        const touch = e.touches[0];

        posicionX =
            touchPosInicialX +
            (touch.clientX - touchInicialX);

        posicionY =
            touchPosInicialY +
            (touch.clientY - touchInicialY);

        actualizarMapa();

    },
    {
        passive: true
    }
);


/* =========================================================
   CENTRAR AL CARGAR
   ========================================================= */

function centrarMapa() {

    posicionX =
        (viewport.clientWidth - canvas.offsetWidth) / 2;

    posicionY =
        (viewport.clientHeight - canvas.offsetHeight) / 2;

    actualizarMapa();
}


window.addEventListener(
    "load",
    centrarMapa
);

window.addEventListener(
    "resize",
    () => {

        /*
         * Solo recalculamos si todavía está
         * en el zoom inicial.
         */

        if (escala === 1) {
            centrarMapa();
        }

    }
);

</script>


<script src="mapa.js"></script>

<?php require_once "footer.php"; ?>
```


