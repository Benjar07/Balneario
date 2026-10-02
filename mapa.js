/**
 * Mapa interactivo:
 * - Consulta map_data.php
 * - Dibuja las carpas con posición X/Y
 * - Cambia el color según estado
 * - Abre un modal con la información de la reserva
 */
const tentsLayer = document.getElementById("tentsLayer");
const fechaMapa = document.getElementById("fechaMapa");
let modalMapa;

document.addEventListener("DOMContentLoaded", () => {
    modalMapa = new bootstrap.Modal(document.getElementById("carpaModal"));
    cargarMapa();
});

fechaMapa?.addEventListener("change", cargarMapa);

async function cargarMapa() {
    try {
        const response = await fetch("map_data.php?fecha=" + encodeURIComponent(fechaMapa.value));
        if (!response.ok) throw new Error("No se pudo consultar el servidor.");
        const carpas = await response.json();

        tentsLayer.innerHTML = "";

        carpas.forEach(carpa => {
            const el = document.createElement("button");
            el.type = "button";
            el.className = `tent tent-${carpa.estado}`;
            el.style.left = carpa.x + "px";
            el.style.top = carpa.y + "px";
            el.innerHTML = `<strong>#${escapeHtml(carpa.numero)}</strong><span>${escapeHtml(carpa.sector)}</span>`;
            el.addEventListener("click", () => abrirDetalle(carpa));
            tentsLayer.appendChild(el);
        });
    } catch (error) {
        console.error(error);
        tentsLayer.innerHTML = `<div class="alert alert-danger position-absolute top-0 start-0 m-3">No se pudo cargar el mapa.</div>`;
    }
}

function abrirDetalle(c) {
    document.getElementById("modalNumero").textContent = "#" + c.numero;
    document.getElementById("modalSector").textContent = c.sector || "-";
    document.getElementById("modalCliente").textContent = c.cliente || "Sin reserva";
    document.getElementById("modalDni").textContent = c.dni || "-";
    document.getElementById("modalTelefono").textContent = c.telefono || "-";
    document.getElementById("modalInicio").textContent = formatearFecha(c.fecha_inicio);
    document.getElementById("modalFin").textContent = formatearFecha(c.fecha_fin);
    document.getElementById("modalTipo").textContent = c.tipo_reserva ? capitalizar(c.tipo_reserva) : "-";
    document.getElementById("modalCosto").textContent = c.costo_total
        ? "$" + Number(c.costo_total).toLocaleString("es-AR", {minimumFractionDigits: 2})
        : "-";

    const estado = document.getElementById("modalEstado");
    const etiquetas = {
        disponible: "🟢 Disponible",
        pendiente: "🟡 Reservado / pendiente de pago",
        ocupado: "🔴 Ocupado"
    };
    estado.className = `status status-${c.estado}`;
    estado.textContent = etiquetas[c.estado] || c.estado;

    modalMapa.show();
}

function formatearFecha(fecha) {
    if (!fecha) return "-";
    const [y,m,d] = fecha.split("-");
    return `${d}/${m}/${y}`;
}

function capitalizar(valor) {
    return valor.charAt(0).toUpperCase() + valor.slice(1);
}

function escapeHtml(texto) {
    return String(texto ?? "").replace(/[&<>"']/g, m => ({
        "&":"&amp;", "<":"&lt;", ">":"&gt;", '"':"&quot;", "'":"&#039;"
    }[m]));
}
