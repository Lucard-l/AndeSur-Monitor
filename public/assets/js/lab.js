'use strict';

document.getElementById('form-login').addEventListener('submit', async (evento) => {
    evento.preventDefault();
    const boton = evento.target.querySelector('button');
    boton.disabled = true;
    try {
        const resultado = await api('login', {
            usuario: document.getElementById('usuario').value,
            contrasena: document.getElementById('contrasena').value,
            uuid: crypto.randomUUID(),
        });
        mostrarResultado('resultado-login', resultado.mensaje, resultado.autenticado ? 'success' : 'warning');
        document.getElementById('contrasena').value = '';
    } catch (error) {
        mostrarResultado('resultado-login', error.message, 'danger');
    } finally {
        boton.disabled = false;
    }
});

document.getElementById('intentar-acceso').addEventListener('click', async (evento) => {
    evento.target.disabled = true;
    try {
        const resultado = await api('permiso', { uuid: crypto.randomUUID() });
        mostrarResultado('resultado-permiso', resultado.mensaje, resultado.permitido ? 'success' : 'warning');
    } catch (error) {
        mostrarResultado('resultado-permiso', error.message, 'danger');
    } finally {
        evento.target.disabled = false;
    }
});

// El botón solo controla el emisor; la ausencia de señales la detecta PHP.
const claveServicio = 'andesur-inventarios-activo';
let emitiendo = localStorage.getItem(claveServicio) !== 'no';
let temporizador;
let enVuelo = false;
let uuidPendiente = null;

function actualizarEmision() {
    document.getElementById('estado-emision').textContent = emitiendo ? 'Activa · cada 4 segundos' : 'Detenida · no se envían señales';
    document.getElementById('detener-servicio').disabled = !emitiendo;
    document.getElementById('reanudar-servicio').disabled = emitiendo;
}

async function consultarInventarios() {
    const respuesta = await api('fuentes');
    const fuente = respuesta.fuentes.find((f) => f.codigo === 'INV-SCZ');
    document.getElementById('estado-inventarios').replaceChildren(insignia(fuente.estado));
    document.getElementById('ultima-senal').textContent = fechaBolivia(fuente.ultima_senal);
}

async function enviarSenal() {
    if (!emitiendo || enVuelo) return;
    enVuelo = true;
    try {
        uuidPendiente ??= crypto.randomUUID();
        await api('heartbeat', { uuid: uuidPendiente });
        uuidPendiente = null;
        await consultarInventarios();
        document.getElementById('resultado-servicio').hidden = true;
    } catch (error) {
        mostrarResultado('resultado-servicio', error.message, 'danger');
    } finally {
        enVuelo = false;
        if (emitiendo) temporizador = setTimeout(enviarSenal, 4000);
    }
}

function cambiarEmision(activa) {
    clearTimeout(temporizador);
    emitiendo = activa;
    localStorage.setItem(claveServicio, activa ? 'si' : 'no');
    actualizarEmision();
    if (activa) enviarSenal();
}

document.getElementById('detener-servicio').addEventListener('click', () => cambiarEmision(false));
document.getElementById('reanudar-servicio').addEventListener('click', () => cambiarEmision(true));
window.addEventListener('storage', (evento) => {
    if (evento.key === claveServicio) cambiarEmision(evento.newValue !== 'no');
});

async function actualizarEstado() {
    try {
        await consultarInventarios();
    } catch (error) {
        mostrarResultado('resultado-servicio', error.message, 'danger');
    } finally {
        setTimeout(actualizarEstado, 5000);
    }
}

actualizarEmision();
actualizarEstado();
if (emitiendo) enviarSenal();
