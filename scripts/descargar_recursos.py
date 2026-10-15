#!/usr/bin/env python3
"""Descarga versiones fijas y verifica la integridad publicada por npm."""
import base64
import hashlib
import io
import json
from pathlib import Path
import tarfile
from urllib.request import urlopen

DESTINO = Path(__file__).resolve().parents[1] / 'public/assets/vendor'
PAQUETES = {
    'bootstrap': ('5.3.3', {'dist/css/bootstrap.min.css': 'bootstrap.min.css', 'LICENSE': 'LICENSE.bootstrap'}),
    'chart.js': ('4.4.9', {'dist/chart.umd.js': 'chart.umd.js', 'LICENSE.md': 'LICENSE.chartjs'}),
}

DESTINO.mkdir(parents=True, exist_ok=True)
registro = {}
for paquete, (version, archivos) in PAQUETES.items():
    with urlopen(f'https://registry.npmjs.org/{paquete}/{version}', timeout=30) as respuesta:
        datos = json.load(respuesta)
    distribucion = datos['dist']
    with urlopen(distribucion['tarball'], timeout=30) as respuesta:
        contenido = respuesta.read()
    algoritmo, esperado = distribucion['integrity'].split('-', 1)
    if algoritmo != 'sha512':
        raise ValueError('Algoritmo de integridad inesperado')
    obtenido = base64.b64encode(hashlib.sha512(contenido).digest()).decode()
    if obtenido != esperado:
        raise ValueError(f'Integridad incorrecta para {paquete}')
    hashes = {}
    with tarfile.open(fileobj=io.BytesIO(contenido), mode='r:gz') as paquete_tar:
        for origen, nombre in archivos.items():
            archivo = paquete_tar.extractfile('package/' + origen)
            if archivo is None:
                raise ValueError('Falta un recurso requerido')
            recurso = archivo.read()
            (DESTINO / nombre).write_bytes(recurso)
            hashes[nombre] = hashlib.sha256(recurso).hexdigest()
    registro[paquete] = {'version': version, 'integrity': distribucion['integrity'], 'archivos_sha256': hashes}
    print(f'{paquete} {version}: integridad SHA-512 verificada')
(DESTINO / 'versiones.json').write_text(json.dumps(registro, indent=2) + '\n')
