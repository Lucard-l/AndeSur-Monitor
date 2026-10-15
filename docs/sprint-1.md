# Sprint 1 — Propuesta de trabajo y aceptación

**Meta:** demostrar Formulario → PHP → MySQL → regla → alerta → dashboard, junto con acceso denegado y detección de inactividad. Equipo de tres estudiantes, una semana. Este documento propone la planificación; no acredita reuniones, votaciones ni aceptación del docente.

El Caso 9 del PDF identifica logs dispersos, ausencia de consolidación y alertas, y falta de documentación sobre conservación, hora y capacidad. El DOCX propone un piloto de cuatro sprints. Para esta entrega se sigue el alcance expreso del texto del Sprint 1: tres fuentes, cinco fallos en cinco minutos y caída por más de 15 segundos sin señales. Los seis escenarios y ocho fuentes del piloto completo no se implementan anticipadamente.

## Sprint Backlog

Los puntos Fibonacci siguientes son **estimaciones iniciales sugeridas**. Los votos individuales y el consenso quedan pendientes de una sesión real de Planning Poker. Los puntos no se convierten en horas.

| Historia / tarea | Responsable propuesto | Puntos sugeridos | Criterio de aceptación |
|---|---|---:|---|
| S1-01: preparar esquema, fuentes y cuenta | Integrante 1 | 3 | SQL importable, cuatro tablas relacionadas, hash de contraseña y tres fuentes sin eventos precargados |
| S1-02: conexión e ingesta JSON | Integrante 1 | 5 | Evento real en MySQL, validación, CSRF, mensajes seguros y UUID único |
| S1-03: formulario de autenticación y regla | Integrante 2, revisión de I1 | 5 | Cinco fallos relacionados en cinco minutos generan una alerta alta; cuatro no; sin contraseñas en logs |
| S1-04: consulta de monitoreo | Integrante 3 | 5 | Indicadores, fuentes, eventos, alertas y gráfico desde MySQL; actualización cada cinco segundos |
| S1-05: operación restringida | Integrante 2 | 3 | PHP consulta el rol, rechaza el acceso y registra evento y alerta alta |
| S1-06: heartbeats, caída y recuperación | Integrante 2, integración de I3 | 5 | Detener no alerta de inmediato; tras más de 15 segundos PHP detecta una caída; reanudar registra recuperación |
| S1-07: integración y evidencias | Los tres | 3 | Siete pruebas de aceptación ejecutadas, persistencia y duplicados verificados; resultados reales documentados |
| S1-08: instalación y guía de defensa | Integrante 3, revisión del equipo | 2 | README reproducible, funciones explicables y pruebas/limitaciones identificadas |

Distribución técnica solicitada: **I1:** MySQL, backend y API. **I2:** simulador y motor. **I3:** dashboard, Bootstrap, JavaScript y gráfico. Las revisiones y pruebas son compartidas; esta distribución no atribuye autoría ni reuniones ficticias.

## Plan inicial de cinco días

| Día | Trabajo propuesto | Evidencia esperada |
|---|---|---|
| 1 | Revisar requisitos, backlog, modelo de datos y carpetas | Esquema y decisiones de alcance |
| 2 | Conectar PHP/MySQL, ingesta y autenticación | Evento visible en la base y API |
| 3 | Regla de fallos y dashboard | Cinco eventos, una alerta alta y actualización automática |
| 4 | Permisos y heartbeats, integrar | Acceso denegado; caída y recuperación sin duplicados |
| 5 | Pruebas, correcciones y preparación de defensa | Resultados, capturas reales y README |

## Criterios de aceptación

| Prueba | Procedimiento | Resultado exigido |
|---|---|---|
| P1 Registro | Generar actividad desde Lab o ingresar un evento por API | Evento persistente en MySQL y consultable por el dashboard |
| P2 Fuerza bruta | Cinco contraseñas incorrectas del mismo usuario y fuente | Una alerta alta con referencias a los cinco eventos; cuatro no alertan |
| P3 Acceso denegado | Solicitar información administrativa como lector | HTTP 403; sin información restringida; evento y alerta alta |
| P4 Caída | Enviar heartbeat, detener emisor y esperar | Sin alerta inmediata; tras más de 15 segundos, alerta crítica y fuente inactiva |
| P5 Persistencia | Recargar ambas pantallas | Eventos y alertas se mantienen; los contadores provienen de MySQL |
| P6 Actualización | Mantener Monitor abierto mientras se opera Lab | Nuevos registros, alertas y estados sin recargar manualmente |
| P7 Duplicados | Reenviar UUID, repetir verificación y reanudar | Sin segunda inserción ni alerta de la misma caída; nueva señal registra recuperación |

**Definition of Done propuesta:** código integrado, MySQL real, controles PHP efectivos, pruebas de aceptación aprobadas y evidencia verificable. La implementación y sus resultados técnicos no reemplazan la Sprint Review del equipo ni la aprobación académica.

## Registro pendiente de Planning Poker

| Historia | Voto I1 | Voto I2 | Voto I3 | Consenso definitivo |
|---|---|---|---|---|
| S1-01 a S1-08 | Pendiente | Pendiente | Pendiente | Pendiente de votación real por historia |

Registrar fecha, participantes, diferencias de estimación y consenso cuando ocurra. No se generan actas de Daily, Review, Retrospective ni burndowns con avances inventados.
