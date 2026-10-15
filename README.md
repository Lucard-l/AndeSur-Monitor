# AndeSur Monitor — Sprint 1

Prototipo académico del **Caso 9: monitoreo centralizado y gestión de registros del Grupo Empresarial AndeSur S.A.** Centraliza eventos sintéticos, los conserva en MySQL y genera alertas desde PHP. AndeSur Lab representa tres sistemas; AndeSur Monitor consulta la misma base mediante HTTP/JSON cada cinco segundos.

Tecnologías: PHP 8.1 o superior sin frameworks, PDO MySQL, MySQL 8, HTML5, CSS3, Bootstrap 5.3.3, JavaScript vanilla y Chart.js 4.4.9. También se utiliza sintaxis SQL compatible con MariaDB de XAMPP. No requiere Node.js, Composer, Docker ni conexión a Internet para utilizar las pantallas.

## Instalación en XAMPP para Windows

1. Instalar XAMPP con PHP 8.1 o superior. Este mínimo permite ejecutar la validación JSON utilizada; no usar PHP 7.
2. Clonar este repositorio en `C:\xampp\htdocs\andesur-monitor`. Conservar la carpeta `public` y las demás carpetas en su posición.
3. Iniciar **Apache** y **MySQL** desde el panel de XAMPP. Su componente MySQL suele ser MariaDB; la validación en la nube se realizó con MySQL 8 real.
4. Abrir phpMyAdmin localmente. En **Importar**, elegir `database.sql`. Se crea `andesur_monitor`, cuatro tablas, tres fuentes y una cuenta de demostración; no se insertan eventos ni alertas prefabricados. El archivo puede reimportarse sin borrar datos ni reemplazar contraseñas existentes.
5. Copiar `config/config.example.php` como `config/config.local.php`. Revisar host, puerto, base, usuario y contraseña. Los valores del ejemplo corresponden al XAMPP local habitual; ajustar si se configuró una contraseña o un puerto diferente. El archivo local está ignorado por Git.
6. Abrir en el navegador de esa misma computadora:
   - AndeSur Lab: ruta local `/andesur-monitor/public/lab.php`.
   - AndeSur Monitor: ruta local `/andesur-monitor/public/dashboard.php`.
7. Mantener abiertas ambas ventanas para la demostración. No abrir los archivos como `file://` ni mediante Live Server: PHP debe ejecutarse en Apache.

Las direcciones anteriores son rutas para introducir en el navegador local; este proyecto no publica un servicio web. Apache debe permitir los archivos `.htaccess` (`AllowOverride All`, valor habitual de XAMPP). El directorio raíz queda protegido y solo `public` admite conexiones locales. Si aparece un 403 en `public`, revisar esta configuración de Apache y usar `localhost` o `127.0.0.1` desde la misma PC.

Para otra instalación que ya tenga PHP/PDO MySQL y MySQL, importar el SQL, copiar la configuración y ejecutar desde la raíz:

```bash
php -S 127.0.0.1:8080 -t public
```

Se debe servir **solo `public`** con el servidor de desarrollo. El backend rechaza conexiones externas y nombres de servidor diferentes de localhost. No se habilitan CORS ni acceso desde la red.

## Cuenta y demostración

La cuenta sintética es `empleado`, contraseña de demostración `AndeSur2026!`, rol `lector`. Se guarda únicamente su hash bcrypt en MySQL. No utilizar esta cuenta fuera del laboratorio ni reutilizar esa contraseña para cuentas reales. La autenticación representada pertenece al portal de empleados; no es un módulo de administración de usuarios del monitor.

1. En Lab, introducir `empleado` y una contraseña incorrecta **cinco veces**. PHP registra cada `LOGIN_FAILED`; el quinto genera **Posible ataque de fuerza bruta**, severidad alta, origen Portal de empleados / La Paz. Verificar el aviso y el dashboard. Una contraseña correcta genera `LOGIN_SUCCESS` y no dispara esa regla.
2. Pulsar **Consultar información administrativa restringida**. PHP consulta el rol de la cuenta fija de prueba en MySQL y devuelve HTTP 403 sin ejecutar la consulta restringida. Guarda `ACCESS_DENIED` y una alerta alta desde el Sistema administrativo / Cochabamba. Enviar un supuesto rol de administrador desde JavaScript no concede permiso.
3. Inventarios envía un heartbeat cada cuatro segundos. Comprobar el estado activo y la última señal. Pulsar **Detener servicio**, conservar abierto el monitor y esperar aproximadamente **15–20 segundos**. El botón no envía una caída: el monitor solicita la verificación cada cinco segundos y PHP detecta más de 15 segundos sin actividad. Se guarda `SERVICE_DOWN`, una alerta crítica y el estado inactivo.
4. Pulsar **Reanudar servicio**. Una señal nueva registra `SERVICE_RECOVERED`, devuelve la fuente a activa y resuelve automáticamente la alerta de esa caída. Los eventos y la alerta se conservan. Volver a detener y reanudar permite demostrar un incidente nuevo.
5. Recargar ambas páginas: los datos persisten en MySQL. Lab recuerda la emisión detenida en `localStorage`; reanudarla expresamente cuando sea necesario.

Usar una sola ventana de Lab para una demostración clara. Varias ventanas del mismo navegador comparten la orden de detener/reanudar; una ventana de otro navegador puede seguir enviando señales. Cerrar Lab también detiene sus heartbeats. Si el navegador suspende la pestaña o la computadora, puede producirse una alerta de disponibilidad; el prototipo observa el emisor del navegador, no un servicio corporativo real.

## Motor y duplicados

Las funciones de `includes/eventos.php` se ejecutan dentro de transacciones. Se bloquea la fila de la fuente para ordenar sus operaciones concurrentes.

- **Fuerza bruta:** cinco fallos del mismo usuario y fuente en cinco minutos, con la hora UTC del servidor. Cada alerta consume cinco fallos nuevos; un sexto fallo no repite los cinco anteriores, y un siguiente grupo de cinco puede originar otra alerta. Los límites están definidos en constantes. La alerta conserva usuario, cantidad y primer/último ID de los eventos relacionados.
- **Acceso denegado:** se evalúa el rol en PHP. Los intentos de la misma cuenta/fuente durante cinco minutos se registran individualmente y comparten una alerta.
- **Servicio inactivo:** solo se vigila inventarios tras su primer heartbeat. Una transición activa → inactiva genera un incidente. La fuente permanece inactiva hasta una señal nueva; consultar repetidamente no crea más alertas. La recuperación resuelve únicamente ese incidente.
- Cada operación recibe un UUID v4. Su índice único evita insertar nuevamente el mismo evento. Un reenvío de heartbeat antiguo no actualiza su hora ni recupera la fuente. El servidor rechaza reutilizar un UUID con otro contenido.

Las fechas de almacenamiento y la sesión SQL usan UTC; JavaScript presenta hora de `America/La_Paz` (UTC−4). No se implementa sincronización NTP ni detección de desfase en este sprint.

## Archivos y módulos

```text
config/config.example.php   Ejemplo de conexión; copiar como config.local.php
database.sql                Tablas, índices, relaciones y datos mínimos
includes/inicio.php         Conexión PDO, sesión y controles de acceso local
includes/eventos.php        Persistencia, permisos y las tres reglas
includes/vista.php          Encabezado y navegación compartidos
public/api.php              API JSON y validación de solicitudes
public/lab.php              Tres escenarios de simulación
public/dashboard.php        Indicadores, fuentes, tablas y gráfico
public/assets/css/app.css   Estilo administrativo sencillo
public/assets/js/           Fetch, actualizaciones y emisor de heartbeats
public/assets/vendor/       Bootstrap y Chart.js locales, versiones y licencias
scripts/descargar_recursos.py  Descarga opcional verificada de recursos
tests/                      Pruebas HTTP y opcionalmente de navegador
docs/                       Backlog, criterios y resultados reales
```

Las cuatro tablas son `fuentes`, `usuarios`, `eventos`, `alertas`. No se añaden tablas de reportes, auditoría ni gestión avanzada. Los eventos tienen índices por fecha, fuente, código y usuario; las alertas tienen una clave de correlación única y referencias a los eventos desencadenantes.

## API y seguridad básica

La ruta es `public/api.php?accion=…`. Las lecturas son `GET`; las escrituras requieren `POST`, `Content-Type: application/json`, cookie de sesión y encabezado `X-CSRF-Token` obtenido al abrir Lab o Monitor. Los cuerpos admiten hasta 8 KiB. No se confía en roles enviados por el navegador.

| Acción | Método | Datos JSON / respuesta |
|---|---|---|
| `login` | POST | `usuario`, `contrasena`, `uuid`; valida el hash y registra el resultado. HTTP 401 para fallo |
| `permiso` | POST | `uuid`; consulta el rol de `empleado`. HTTP 403 para acceso denegado |
| `heartbeat` | POST | `uuid`; actualiza inventarios, registra señal y eventual recuperación |
| `verificar` | POST | `{}`; PHP evalúa el tiempo transcurrido y devuelve `alertas_nuevas` |
| `evento` | POST | `fuente`, `descripcion`, `uuid`; ingesta sintética de código `LAB_EVENT`, severidad informativa |
| `resumen` | GET | Cuatro indicadores, agrupación horaria y hora UTC del servidor |
| `eventos` | GET | Últimos 50 registros; no se carga todo el historial |
| `alertas` | GET | Últimas 30 alertas |
| `fuentes` | GET | Estado y última señal de las tres fuentes |

La ingesta genérica no permite elegir severidades ni insertar alertas: los tres escenarios específicos se comprueban desde PHP. Los códigos de fuente iniciales son `PORTAL-LPZ`, `ADMIN-CBB` e `INV-SCZ`.

Se emplean consultas preparadas, validación, sesión HttpOnly/SameSite Strict, CSRF, política de contenido, escape PHP y `textContent` al mostrar datos. Los fallos internos devuelven mensajes seguros. La configuración está fuera del directorio público y excluida de Git. En la nube la cuenta de aplicación tiene únicamente SELECT, INSERT y UPDATE sobre su base.

## Comprobar datos con phpMyAdmin

Elegir `andesur_monitor` y abrir las tablas **eventos**, **alertas**, **fuentes** y **usuarios**. No debe aparecer ninguna contraseña introducida en los eventos. Consultas útiles en la pestaña SQL:

```sql
SET time_zone = '+00:00';
SELECT id, uuid, fuente_id, codigo, usuario, fecha, es_simulado
FROM eventos ORDER BY id DESC LIMIT 50;

SELECT id, tipo, usuario, severidad, estado, primer_evento_id,
       ultimo_evento_id, cantidad_eventos
FROM alertas ORDER BY id DESC;

SELECT codigo, estado, ultima_senal FROM fuentes;

-- Reemplazar 1 por el ID real de la alerta de fuerza bruta.
SELECT e.id, e.uuid, e.codigo, e.usuario, e.fecha
FROM eventos e JOIN alertas a ON a.fuente_id = e.fuente_id
WHERE a.id = 1 AND a.tipo = 'BRUTE_FORCE'
  AND e.codigo = 'LOGIN_FAILED' AND e.usuario = a.usuario
  AND e.id BETWEEN a.primer_evento_id AND a.ultimo_evento_id
ORDER BY e.id;
```

## Pruebas

Con la aplicación y MySQL iniciados, desde la raíz del repositorio (sustituir `URL_BASE_LOCAL` por la dirección local que contiene `lab.php`):

```bash
python tests/probar_flujo.py URL_BASE_LOCAL
```

Es una prueba HTTP con Python 3 y su biblioteca estándar; requiere unos 16 segundos sin heartbeats. **Cerrar las ventanas de Lab antes de ejecutarla**, para que no sigan enviando señales. Agrega datos sintéticos y no borra registros. Cada ejecución usa un usuario de prueba diferente. Para comprobar solo el primer flujo, agregar `--solo-login`.

La prueba opcional de interfaz utiliza Playwright y Chromium, que no son dependencias de ejecución del proyecto:

```bash
python tests/probar_interfaz.py URL_BASE_LOCAL --chromium RUTA_CHROMIUM
```

Comprueba los formularios, dos ventanas, actualización automática, caída y recuperación, recarga, gráfico y adaptación a móvil; guarda capturas reales en `tests/resultados/`, excluidas de Git. [Resultados de la validación](docs/pruebas.md) y [Sprint Backlog propuesto](docs/sprint-1.md).

Los recursos frontend ya están versionados. Para descargarlos nuevamente se puede ejecutar `python scripts/descargar_recursos.py`; requiere acceso a `registry.npmjs.org`, verifica la integridad SHA-512 del paquete y registra hashes de sus archivos. No es necesario ejecutarlo al instalar en XAMPP.

## Alcance y limitaciones

Se implementa exclusivamente el Sprint 1 del texto de requisitos suministrado. El PDF oficial fundamenta la centralización (A.8.15), el monitoreo (A.8.16), la consistencia temporal (A.8.17) y la necesidad futura de dimensionamiento (A.8.6). Almacenar en UTC no acredita sincronización de relojes ni conformidad ISO.

Quedan para sprints posteriores: gestión de fuentes/alertas, filtros, roles de operadores del monitor, reportes, auditoría administrativa, verificación de desfases, capacidad, retención y endurecimiento empresarial. El timeout de 15 segundos y las tres fuentes corresponden al alcance solicitado; el documento académico describe además umbrales y escenarios para el piloto completo.

El detector de inactividad se ejecuta al consultar el monitor; sin este abierto se necesita invocar `verificar` manualmente con una sesión autorizada. No hay worker ni servicio de monitoreo independiente. Los umbrales son de demostración y no valores establecidos por ISO. XAMPP en Windows y phpMyAdmin requieren comprobación local por el equipo; la validación automatizada se realizó en Linux con PHP y MySQL reales.
