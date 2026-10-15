# Validación del Sprint 1

**Fecha:** 15 de octubre de 2026, horario de Bolivia (UTC−4).

**Entorno ejecutado:** Debian 13 x86_64, PHP 8.4.24 con PDO MySQL, MySQL 8.0.46 y Chromium mediante Playwright. Se utilizaron datos sintéticos generados por las solicitudes reales a la aplicación. El SQL inicial contiene fuentes y un usuario; no contiene eventos ni alertas prefabricados.

## Resultado de aceptación

| ID | Comprobación ejecutada | Resultado |
|---|---|---|
| P1 | Envío HTTP a la ingesta, consulta del evento persistido y formulario de Lab | Aprobada |
| P2 | Cuatro fallos sin alerta; quinto fallo con una alerta alta ligada a cinco eventos | Aprobada |
| P3 | Operación administrativa rechazada con HTTP 403; alterar el rol en la petición no concede acceso | Aprobada |
| P4 | Heartbeat inicial, espera real de 16 segundos sin señales y detección PHP de la caída; botón probado en Chromium | Aprobada |
| P5 | Recarga de pantalla seguida de consulta de la misma alerta persistente | Aprobada |
| P6 | Lab y Monitor abiertos en dos páginas; aparición de alertas, caída y recuperación sin recarga manual | Aprobada |
| P7 | UUID repetido sin nueva fila; verificación repetida sin nueva alerta; heartbeat antiguo sin recuperación; señal nueva con recuperación | Aprobada |

La prueba HTTP completa terminó con **34 comprobaciones aprobadas**. Además comprueba credenciales válidas, ausencia de contraseñas en las respuestas de eventos, CSRF, UUID inválido, fuentes desconocidas, métodos HTTP y recuperación automática de la alerta de disponibilidad.

La prueba de Chromium comprueba los formularios y la actualización entre dos ventanas, el gráfico de 24 franjas horarias con datos de MySQL, la recarga y el diseño a 390 px. No se observaron errores JavaScript en la ejecución final. Las tablas se desplazan dentro de su contenedor en pantallas estrechas.

Comprobaciones adicionales ejecutadas: rechazo de un Host externo (403), rechazo de escritura mediante GET (405) y ausencia de código/configuración descargable desde el directorio público. El código PHP pasó la comprobación de sintaxis y los tres scripts JavaScript pasaron `node --check`; Node se utilizó como herramienta de validación, no como backend.

## Repetibilidad del entorno

Se ejecutó el instalador de la nube nuevamente: descargó/verificó paquetes mediante APT, reimportó el esquema sin borrar datos y conservó `config.local.php`. Las firmas APT, los checksums de paquetes y TLS permanecieron habilitados. Los recursos de Bootstrap y Chart.js se verificaron con integridad SHA-512 publicada por npm; sus hashes locales también se comprobaron.

Se apagaron los servicios iniciados durante la preparación y se volvió a ejecutar el arranque guardado. MySQL respondió a `SELECT 1` y la API devolvió un resumen JSON con las tres fuentes desde PDO. Una segunda ejecución del arranque fue correcta sin duplicar servicios. La suite HTTP completa volvió a pasar después del reinicio.

## Evidencias

Los archivos de `docs/evidencias/` proceden de la ejecución real del prototipo:

- `http.txt`: salida de las 34 comprobaciones de la suite HTTP final.
- `interfaz.txt`: salida de la prueba final de Chromium.
- `monitor.png` y `lab.png`: capturas de las pantallas, con eventos y alertas generados durante las pruebas.
- `version-validada.sha256`: hashes de los archivos de aplicación y pruebas correspondientes a esta validación.

Las capturas muestran datos de laboratorio y no incidentes reales. No representan aceptación de estudiantes, docente ni de la empresa. El equipo debe realizar y documentar su propia demostración en Windows, las votaciones reales de Planning Poker y la Sprint Review.

## No ejecutado / fuera del alcance

- Instalación en Windows, Apache de XAMPP y consultas mediante la interfaz de phpMyAdmin: **no ejecutadas en esta máquina Linux**; instrucciones en el README. La compatibilidad con MariaDB de XAMPP no se presenta como una prueba realizada.
- No se probó una espera real de cinco minutos para el vencimiento de la ventana de correlación; se comprobó el umbral de cinco eventos dentro de ella. La condición temporal está definida en el SQL del motor.
- No se ejecutaron pruebas de carga de 1.000 eventos, NTP, desfases de reloj, políticas de retención, redes entre sedes ni pruebas de producción.
- Los casos CP-01 a CP-12 del documento académico abarcan funcionalidades de otros sprints. No se declaran aprobados en conjunto ni se reemplazan por estas siete pruebas de aceptación del Sprint 1.
- Guardar la configuración del entorno no publica su snapshot ni prueba la restauración en una tarea nueva. La publicación de la nube corresponde al flujo del producto.

No quedaron fallos funcionales conocidos en las pruebas de aceptación ejecutadas para el Sprint 1. El alcance continúa siendo un laboratorio local con datos simulados y detección de inactividad activada por el dashboard.
