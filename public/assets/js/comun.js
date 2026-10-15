'use strict';

const api = async (accion, datos) => {
    const opciones = { credentials: 'same-origin', cache: 'no-store' };
    if (datos !== undefined) {
        opciones.method = 'POST';
        opciones.headers = {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content,
        };
        opciones.body = JSON.stringify(datos);
    }
    const respuesta = await fetch(`api.php?accion=${encodeURIComponent(accion)}`, opciones);
    const resultado = await respuesta.json();
    if (!respuesta.ok && !('autenticado' in resultado) && !('permitido' in resultado)) {
        throw new Error(resultado.error || 'No se pudo completar la solicitud.');
    }
    return resultado;
};

const fechaBolivia = (fecha, soloHora = false) => {
    if (!fecha) return 'Sin señales recibidas';
    const fechaUTC = fecha.includes('T') ? fecha : `${fecha.replace(' ', 'T')}Z`;
    return new Intl.DateTimeFormat('es-BO', {
        timeZone: 'America/La_Paz',
        ...(soloHora ? {} : { day: '2-digit', month: '2-digit', year: 'numeric' }),
        hour: '2-digit', minute: '2-digit', second: soloHora ? undefined : '2-digit', hour12: false,
    }).format(new Date(fechaUTC));
};

const insignia = (valor) => {
    const clases = { Alta: 'text-bg-warning', Critica: 'text-bg-danger', Media: 'text-bg-warning', Informativa: 'text-bg-light border', Abierta: 'text-bg-warning', Resuelta: 'text-bg-success', activa: 'text-bg-success', inactiva: 'text-bg-danger', sin_senal: 'text-bg-secondary' };
    const elemento = document.createElement('span');
    elemento.className = `badge ${clases[valor] || 'text-bg-secondary'}`;
    elemento.textContent = { Critica: 'Crítica', activa: 'Activa', inactiva: 'Inactiva', sin_senal: 'Sin señal inicial' }[valor] || valor;
    return elemento;
};

const mostrarResultado = (id, mensaje, clase) => {
    const elemento = document.getElementById(id);
    elemento.textContent = mensaje;
    elemento.className = `alert alert-${clase} mt-3 mb-0`;
    elemento.hidden = false;
};
