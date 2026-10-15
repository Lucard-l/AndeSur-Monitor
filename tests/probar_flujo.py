#!/usr/bin/env python3
"""Prueba HTTP real. Agrega registros sintéticos; no borra datos existentes."""
import argparse
from http.cookiejar import CookieJar
import json
import re
import time
from urllib.error import HTTPError
from urllib.request import build_opener, HTTPCookieProcessor, Request
from uuid import uuid4

argumentos = argparse.ArgumentParser()
argumentos.add_argument('url', help='URL base que contiene lab.php y api.php')
argumentos.add_argument('--solo-login', action='store_true')
opciones = argumentos.parse_args()
base = opciones.url.rstrip('/') + '/'
cliente = build_opener(HTTPCookieProcessor(CookieJar()))
with cliente.open(base + 'lab.php', timeout=10) as respuesta:
    pagina = respuesta.read().decode()
token = re.search(r'name="csrf-token" content="([a-f0-9]+)"', pagina).group(1)


def llamar(accion, datos=None, csrf=token):
    cabeceras = {'Content-Type': 'application/json', 'X-CSRF-Token': csrf}
    solicitud = Request(base + 'api.php?accion=' + accion,
                        data=None if datos is None else json.dumps(datos).encode(),
                        headers=cabeceras)
    try:
        respuesta = cliente.open(solicitud, timeout=10)
    except HTTPError as error:
        respuesta = error
    return respuesta.code, json.loads(respuesta.read())


def comprobar(condicion, mensaje):
    if not condicion:
        raise AssertionError(mensaje)
    print('APROBADO:', mensaje)


usuario = 'prueba_' + uuid4().hex[:12]
def fallar(id=None, nombre=usuario):
    return llamar('login', {'usuario': nombre, 'contrasena': 'incorrecta', 'uuid': id or str(uuid4())})


for numero in range(4):
    estado, resultado = fallar()
    comprobar(estado == 401 and not resultado['autenticado'], f'Intento {numero + 1}: PHP rechaza y registra')
alertas = llamar('alertas')[1]['alertas']
comprobar(not any(a['usuario'] == usuario for a in alertas), 'Cuatro intentos no generan alerta')
identificador = str(uuid4())
estado, resultado = fallar(identificador)
id_evento = resultado['evento']['id']
alertas = llamar('alertas')[1]['alertas']
propias = [a for a in alertas if a['usuario'] == usuario]
comprobar(len(propias) == 1 and propias[0]['severidad'] == 'Alta' and propias[0]['cantidad_eventos'] == 5,
          'Quinto intento: una alerta alta vinculada a cinco eventos')
estado, repetido = fallar(identificador)
comprobar(repetido['evento']['duplicado'] and repetido['evento']['id'] == id_evento,
          'Reenvío del mismo UUID no duplica evento')
fallar()
alertas = llamar('alertas')[1]['alertas']
comprobar(len([a for a in alertas if a['usuario'] == usuario]) == 1,
          'Reenvío y sexto intento no repiten los mismos cinco fallos')
estado, resultado = llamar('login', {'usuario': 'empleado', 'contrasena': 'AndeSur2026!', 'uuid': str(uuid4())})
comprobar(estado == 200 and resultado['autenticado'], 'Contraseña válida verificada con el hash de MySQL')
estado, _ = llamar('login', {'usuario': 'empleado', 'contrasena': 'incorrecta', 'uuid': str(uuid4())}, csrf='incorrecto')
comprobar(estado == 403, 'Solicitud sin CSRF válido rechazada')
estado, _ = llamar('login', {'usuario': 'empleado', 'contrasena': 'incorrecta', 'uuid': 'invalido'})
comprobar(estado == 400, 'UUID inválido rechazado')
eventos = llamar('eventos')[1]['eventos']
comprobar(len([e for e in eventos if e['usuario'] == usuario]) == 6, 'Se conservan exactamente seis fallos individuales')
comprobar(all('incorrecta' not in e['descripcion'] and 'contrasena' not in e for e in eventos),
          'Los registros de la API no contienen las contraseñas introducidas')
with cliente.open(base + 'dashboard.php', timeout=10) as respuesta:
    comprobar(respuesta.code == 200, 'Dashboard accesible después de recargar')
comprobar(any(a['usuario'] == usuario for a in llamar('alertas')[1]['alertas']), 'La alerta persiste tras recargar')
comprobar(llamar('resumen')[1]['indicadores']['fuentes'] == 3, 'Tres fuentes iniciales consultadas desde MySQL')

if opciones.solo_login:
    print('Flujo de autenticación completo verificado.')
    raise SystemExit(0)

estado, resultado = llamar('evento', {'uuid': str(uuid4()), 'fuente': 'PORTAL-LPZ', 'descripcion': 'Registro sintético de la prueba de ingesta.'})
comprobar(estado == 201 and not resultado['evento']['duplicado'], 'API de ingesta guarda un evento de laboratorio en MySQL')
id_ingesta = resultado['evento']['id']
comprobar(any(e['id'] == id_ingesta for e in llamar('eventos')[1]['eventos']), 'Evento ingresado visible en consulta del dashboard')
estado, resultado = llamar('permiso', {'uuid': str(uuid4()), 'rol': 'administrador', 'usuario': 'administrador'})
comprobar(estado == 403 and not resultado['permitido'] and 'datos_restringidos' not in resultado,
          'PHP rechaza la operación aunque el cliente intente atribuirse un rol')
alertas_antes = [a for a in llamar('alertas')[1]['alertas'] if a['tipo'] == 'ACCESS_DENIED']
comprobar(bool(alertas_antes) and alertas_antes[0]['severidad'] == 'Alta', 'Acceso denegado genera alerta alta')
llamar('permiso', {'uuid': str(uuid4())})
alertas_despues = [a for a in llamar('alertas')[1]['alertas'] if a['tipo'] == 'ACCESS_DENIED']
comprobar(len(alertas_antes) == len(alertas_despues), 'Repetir el acceso no duplica la alerta durante cinco minutos')
identificador = str(uuid4())
comprobar(llamar('heartbeat', {'uuid': identificador})[0] == 200, 'Heartbeat recibido')
fuente = lambda: next(f for f in llamar('fuentes')[1]['fuentes'] if f['codigo'] == 'INV-SCZ')
senal = fuente()['ultima_senal']
repetido = llamar('heartbeat', {'uuid': identificador})[1]
comprobar(repetido['evento']['duplicado'] and fuente()['ultima_senal'] == senal, 'Reenvío de heartbeat no renueva la hora')
antes = llamar('verificar', {})[1]
comprobar(antes['alertas_nuevas'] == 0 and fuente()['estado'] == 'activa', 'No hay caída inmediata al detener las señales')
print('Esperando 16 segundos sin heartbeats para comprobar el reloj real del servidor…', flush=True)
time.sleep(16)
despues = llamar('verificar', {})[1]
comprobar(despues['alertas_nuevas'] == 1 and fuente()['estado'] == 'inactiva', 'PHP detecta la inactividad después de más de 15 segundos')
caidas = [a for a in llamar('alertas')[1]['alertas'] if a['tipo'] == 'SERVICE_DOWN']
comprobar(caidas and caidas[0]['severidad'] == 'Critica', 'Alerta crítica de inventarios guardada')
comprobar(llamar('verificar', {})[1]['alertas_nuevas'] == 0, 'Verificar una caída persistente no duplica alertas')
llamar('heartbeat', {'uuid': identificador})
comprobar(fuente()['estado'] == 'inactiva', 'Reenviar una señal antigua no simula una recuperación')
llamar('heartbeat', {'uuid': str(uuid4())})
comprobar(fuente()['estado'] == 'activa', 'Una señal nueva recupera la fuente')
comprobar(any(e['codigo'] == 'SERVICE_RECOVERED' for e in llamar('eventos')[1]['eventos']), 'Evento de recuperación persistente')
ultima_caida = next(a for a in llamar('alertas')[1]['alertas'] if a['id'] == caidas[0]['id'])
comprobar(ultima_caida['estado'] == 'Resuelta', 'La recuperación resuelve automáticamente la alerta de esa caída')
estado, _ = llamar('heartbeat', {'uuid': str(uuid4())}, csrf='')
comprobar(estado == 403, 'Heartbeat sin protección de sesión rechazado')
comprobar(llamar('verificar')[0] == 405, 'Verificar inactividad requiere POST protegido')
comprobar(llamar('evento', {'uuid': str(uuid4()), 'fuente': 'DESCONOCIDA', 'descripcion': 'Evento inválido'})[0] == 400,
          'Fuente inexistente rechazada')
print('Sprint 1: pruebas HTTP completas aprobadas.')
