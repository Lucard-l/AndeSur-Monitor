#!/usr/bin/env python3
"""Prueba opcional en Chromium: dos ventanas, actualización y caída real."""
import argparse
from pathlib import Path
from uuid import uuid4
from playwright.sync_api import expect, sync_playwright

parser = argparse.ArgumentParser()
parser.add_argument('url')
parser.add_argument('--chromium', help='Ruta al Chromium instalado, si no se usa el de Playwright')
opciones = parser.parse_args()
base = opciones.url.rstrip('/') + '/'
destino = Path(__file__).parent / 'resultados'
destino.mkdir(exist_ok=True)

with sync_playwright() as p:
    configuracion = {'headless': True}
    if opciones.chromium:
        configuracion['executable_path'] = opciones.chromium
    navegador = p.chromium.launch(**configuracion)
    contexto = navegador.new_context(viewport={'width': 1440, 'height': 1100})
    lab, monitor = contexto.new_page(), contexto.new_page()
    errores = []
    for pagina in (lab, monitor):
        pagina.on('pageerror', lambda error: errores.append(str(error)))
    monitor.goto(base + 'dashboard.php')
    expect(monitor.locator('#eventos')).not_to_have_text('—')
    lab.goto(base + 'lab.php')
    expect(lab.locator('#ultima-senal')).not_to_have_text('Consultando…')
    usuario = 'ui_' + uuid4().hex[:12]
    lab.locator('#usuario').fill(usuario)
    for _ in range(5):
        lab.locator('#contrasena').fill('incorrecta')
        with lab.expect_response(lambda r: 'accion=login' in r.url) as respuesta:
            lab.get_by_role('button', name='Iniciar sesión').click()
        assert respuesta.value.status == 401
    monitor.get_by_text('Posible ataque de fuerza bruta · ' + usuario, exact=True).wait_for(timeout=12000)
    print('APROBADO: cinco formularios fallidos actualizan la alerta en otra ventana sin recargar.', flush=True)
    lab.get_by_role('button', name='Consultar información administrativa restringida').click()
    lab.locator('#resultado-permiso').filter(has_text='Acceso denegado').wait_for()
    monitor.get_by_text('Intento de acceso no autorizado · empleado', exact=True).first.wait_for(timeout=12000)
    print('APROBADO: operación restringida rechazada desde la interfaz.', flush=True)
    lab.get_by_role('button', name='Detener servicio').click()
    expect(monitor.locator('#lista-fuentes')).to_contain_text('Inactiva', timeout=26000)
    monitor.get_by_text('Sistema de inventarios sin respuesta', exact=True).first.wait_for()
    # Conservar el estado real de la caída como evidencia visual.
    monitor.screenshot(path=str(destino / 'monitor-caida.png'), full_page=True)
    lab.screenshot(path=str(destino / 'lab-caida.png'), full_page=True)
    print('APROBADO: detener señales produce una caída detectada por el monitor después del umbral.', flush=True)
    lab.get_by_role('button', name='Reanudar servicio').click()
    expect(monitor.locator('#lista-fuentes')).not_to_contain_text('Inactiva', timeout=12000)
    monitor.get_by_text('SERVICE_RECOVERED', exact=True).first.wait_for(timeout=12000)
    print('APROBADO: recuperación persistida y visible automáticamente.', flush=True)
    monitor.reload()
    monitor.get_by_text('Posible ataque de fuerza bruta · ' + usuario, exact=True).wait_for(timeout=12000)
    print('APROBADO: eventos y alerta persisten al recargar.', flush=True)
    assert monitor.locator('#grafico-eventos').evaluate('(e) => Chart.getChart(e).data.labels.length') == 24
    assert not errores, errores
    assert monitor.locator('#error-dashboard').is_hidden()
    monitor.screenshot(path=str(destino / 'monitor.png'), full_page=True)
    lab.screenshot(path=str(destino / 'lab.png'), full_page=True)
    monitor.set_viewport_size({'width': 390, 'height': 844})
    expect(monitor.locator('html')).to_have_js_property('scrollWidth', 390)
    lab.set_viewport_size({'width': 390, 'height': 844})
    expect(lab.locator('html')).to_have_js_property('scrollWidth', 390)
    print('APROBADO: gráfico real, sin errores JavaScript y vistas adaptadas a 390 px.', flush=True)
    # Dejar el emisor detenido al finalizar esta prueba; el navegador ya no emitirá.
    lab.get_by_role('button', name='Detener servicio').click()
    navegador.close()
print('Prueba de interfaz completa aprobada. Capturas en tests/resultados/.')
