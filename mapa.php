<?php
$titulo = "Mapa interactivo de carpas";
require_once "header.php";
?>
<div class="card-soft mb-4">
    <div class="row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label">Fecha a consultar</label>
            <input type="date" id="fechaMapa" class="form-control" value="<?= date("Y-m-d") ?>">
        </div>
        <div class="col-md-8">
            <div class="map-legend">
                <span><i class="dot available"></i> Disponible</span>
                <span><i class="dot pending"></i> Reservado / Pendiente</span>
                <span><i class="dot occupied"></i> Ocupado</span>
            </div>
        </div>
    </div>
</div>

<div class="card-soft">
    <div class="beach-map" id="beachMap">
        <div class="map-water"></div>
        <div class="map-sand"></div>
        <div class="map-label map-title-label">PLANO DEL BALNEARIO</div>
        <div class="map-label sea-label">MAR</div>
        <div id="tentsLayer"></div>
    </div>
</div>

<div class="modal fade" id="carpaModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header">
        <h5 class="modal-title">Detalle de carpa <span id="modalNumero"></span></h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="modalEstado" class="mb-3"></div>
        <div class="info-grid">
            <div><small class="text-muted">Sector</small><div id="modalSector">-</div></div>
            <div><small class="text-muted">Cliente</small><div id="modalCliente">-</div></div>
            <div><small class="text-muted">DNI</small><div id="modalDni">-</div></div>
            <div><small class="text-muted">Teléfono</small><div id="modalTelefono">-</div></div>
            <div><small class="text-muted">Inicio</small><div id="modalInicio">-</div></div>
            <div><small class="text-muted">Fin</small><div id="modalFin">-</div></div>
            <div><small class="text-muted">Tipo</small><div id="modalTipo">-</div></div>
            <div><small class="text-muted">Costo total</small><div id="modalCosto">-</div></div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="mapa.js"></script>
<?php require_once "footer.php"; ?>
