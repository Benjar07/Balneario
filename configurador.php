<?php
$titulo = "Configurador del mapa";
require_once "conexion.php";

$mensaje = "";
$carpas = $conexion->query("SELECT * FROM carpas WHERE activa=1 ORDER BY sector, numero");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = (int)($_POST["id"] ?? 0);
    $x = (int)($_POST["x"] ?? 0);
    $y = (int)($_POST["y"] ?? 0);

    $stmt = $conexion->prepare("UPDATE carpas SET pos_x=?, pos_y=? WHERE id=?");
    $stmt->bind_param("iii", $x, $y, $id);
    $stmt->execute();

    $mensaje = "Posición actualizada.";
}

$carpas = $conexion->query("SELECT * FROM carpas WHERE activa=1 ORDER BY sector, numero");
require_once "header.php";
?>

<?php if ($mensaje): ?><div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>

<div class="card-soft mb-4">
    <h2 class="section-title">Editor visual de posiciones</h2>
    <p class="text-muted mb-0">
        Arrastrá las carpas dentro del plano para adaptarlas a la distribución real del balneario.
        Al mover una carpa se guardan sus coordenadas X/Y en MySQL.
    </p>
</div>

<div class="row g-4">
    <div class="col-lg-9">
        <div class="card-soft">
            <div class="editor-map" id="editorMap">
                <div class="map-water"></div>
                <div class="map-sand"></div>
                <?php while ($c = $carpas->fetch_assoc()): ?>
                    <div class="editor-tent" draggable="true"
                         data-id="<?= $c["id"] ?>"
                         data-x="<?= $c["pos_x"] ?>"
                         data-y="<?= $c["pos_y"] ?>"
                         style="left:<?= $c["pos_x"] ?>px;top:<?= $c["pos_y"] ?>px;">
                        <strong>#<?= htmlspecialchars($c["numero"]) ?></strong>
                        <span><?= htmlspecialchars($c["sector"]) ?></span>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-3">
        <div class="card-soft">
            <h3 class="h5">Ayuda</h3>
            <p class="small text-muted">Las coordenadas se miden desde la esquina superior izquierda del plano.</p>
            <ul class="small text-muted ps-3">
                <li>Arrastrá una carpa.</li>
                <li>Soltala en la nueva ubicación.</li>
                <li>El sistema actualiza la posición automáticamente.</li>
            </ul>
            <div id="saveMessage" class="small text-success"></div>
        </div>
    </div>
</div>

<script>
const editorMap = document.getElementById("editorMap");
let arrastrada = null;

document.querySelectorAll(".editor-tent").forEach(el => {
    el.addEventListener("dragstart", () => {
        arrastrada = el;
        el.classList.add("dragging");
    });

    el.addEventListener("dragend", () => {
        el.classList.remove("dragging");
    });
});

editorMap.addEventListener("dragover", e => e.preventDefault());

editorMap.addEventListener("drop", async e => {
    e.preventDefault();
    if (!arrastrada) return;

    const rect = editorMap.getBoundingClientRect();
    const x = Math.max(0, Math.round(e.clientX - rect.left - 45));
    const y = Math.max(0, Math.round(e.clientY - rect.top - 30));

    arrastrada.style.left = x + "px";
    arrastrada.style.top = y + "px";

    const body = new URLSearchParams({
        id: arrastrada.dataset.id,
        x: x,
        y: y
    });

    try {
        const response = await fetch("configurador.php", {
            method: "POST",
            headers: {"Content-Type":"application/x-www-form-urlencoded;charset=UTF-8"},
            body
        });

        if (!response.ok) throw new Error("Error al guardar");
        document.getElementById("saveMessage").textContent = "✓ Posición guardada";
        setTimeout(() => document.getElementById("saveMessage").textContent = "", 1500);
    } catch (err) {
        document.getElementById("saveMessage").textContent = "No se pudo guardar.";
    }
});
</script>

<?php require_once "footer.php"; ?>
