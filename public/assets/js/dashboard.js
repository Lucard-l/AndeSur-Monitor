'use strict';

const grafico = new Chart(document.getElementById('grafico-eventos'), {
    type: 'bar',
    data: { labels: [], datasets: [{ label: 'Eventos', data: [], backgroundColor: '#315f82', borderRadius: 2 }] },
    options: {
        responsive: true, maintainAspectRatio: false, animation: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { grid: { display: false }, ticks: { maxTicksLimit: 8 } } },
    },
});

function llenarTabla(id, registros, columnas, vacio) {
    const cuerpo = document.getElementById(id);
    cuerpo.replaceChildren();
    if (registros.length === 0) {
        const celda = document.createElement('td');
        celda.colSpan = columnas.length;
        celda.className = 'text-secondary p-3';
        celda.textContent = vacio;
        const fila = document.createElement('tr');
        fila.append(celda);
        cuerpo.append(fila);
    }
    registros.forEach((registro) => {
        const fila = document.createElement('tr');
        columnas.forEach((columna) => {
            const celda = document.createElement('td');
            const valor = columna(registro);
            if (valor instanceof Node) celda.append(valor);
            else celda.textContent = valor;
            fila.append(celda);
        });
        cuerpo.append(fila);
    });
}

function actualizarGrafico(horas, ahora) {
    const cantidades = new Map(horas.map((fila) => [fila.hora, Number(fila.cantidad)]));
    const fin = new Date(ahora);
    fin.setUTCMinutes(0, 0, 0);
    const etiquetas = [];
    const datos = [];
    for (let i = 23; i >= 0; i--) {
        const hora = new Date(fin.getTime() - i * 3600000);
        const clave = hora.toISOString().slice(0, 19).replace('T', ' ');
        etiquetas.push(fechaBolivia(hora.toISOString(), true));
        datos.push(cantidades.get(clave) || 0);
    }
    grafico.data.labels = etiquetas;
    grafico.data.datasets[0].data = datos;
    grafico.update();
    document.getElementById('grafico-resumen').textContent = `${datos.reduce((a, b) => a + b, 0)} eventos en las horas mostradas.`;
}

function mostrarFuentes(fuentes) {
    const lista = document.getElementById('lista-fuentes');
    lista.replaceChildren();
    fuentes.forEach((fuente) => {
        const bloque = document.createElement('div');
        bloque.className = 'fuente';
        const nombre = document.createElement('div');
        nombre.className = 'd-flex justify-content-between gap-2';
        const titulo = document.createElement('strong');
        titulo.textContent = fuente.nombre;
        nombre.append(titulo, insignia(fuente.estado));
        const detalle = document.createElement('small');
        detalle.textContent = `${fuente.ubicacion} · ${fuente.codigo}`;
        bloque.append(nombre, detalle);
        if (fuente.codigo === 'INV-SCZ') {
            const senal = document.createElement('small');
            senal.textContent = `Última señal: ${fechaBolivia(fuente.ultima_senal)}`;
            bloque.append(senal);
        }
        lista.append(bloque);
    });
}

async function actualizarDashboard() {
    const errorElemento = document.getElementById('error-dashboard');
    try {
        await api('verificar', {});
        const [resumen, eventos, alertas, fuentes] = await Promise.all([
            api('resumen'), api('eventos'), api('alertas'), api('fuentes'),
        ]);
        Object.entries(resumen.indicadores).forEach(([id, valor]) => { document.getElementById(id).textContent = valor; });
        actualizarGrafico(resumen.horas, resumen.hora_servidor);
        llenarTabla('tabla-eventos', eventos.eventos, [
            (e) => fechaBolivia(e.fecha), (e) => e.fuente, (e) => e.ubicacion, (e) => e.codigo,
            (e) => `${e.descripcion}${e.usuario ? ` Usuario: ${e.usuario}.` : ''}`, (e) => insignia(e.severidad),
        ], 'Todavía no hay eventos. Genere actividad desde AndeSur Lab.');
        llenarTabla('tabla-alertas', alertas.alertas, [
            (a) => fechaBolivia(a.fecha), (a) => `${a.nombre}${a.usuario ? ` · ${a.usuario}` : ''}`, (a) => `${a.fuente} · ${a.ubicacion}`,
            (a) => insignia(a.severidad), (a) => insignia(a.estado),
        ], 'No se han detectado alertas.');
        mostrarFuentes(fuentes.fuentes);
        document.getElementById('actualizacion').textContent = `Actualizado: ${fechaBolivia(resumen.hora_servidor)} · cada 5 s`;
        errorElemento.hidden = true;
    } catch (error) {
        errorElemento.textContent = error.message;
        errorElemento.hidden = false;
        document.getElementById('actualizacion').textContent = 'No se pudo actualizar. Reintentando en 5 s.';
    } finally {
        setTimeout(actualizarDashboard, 5000);
    }
}

actualizarDashboard();
