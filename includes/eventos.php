<?php
declare(strict_types=1);

const UMBRAL_INTENTOS = 5;
const VENTANA_MINUTOS = 5;
const UMBRAL_INACTIVIDAD = 15;

function bloquear_fuente(PDO $bd, string $codigo): array
{
    $consulta = $bd->prepare('SELECT * FROM fuentes WHERE codigo = ? FOR UPDATE');
    $consulta->execute([$codigo]);
    $fuente = $consulta->fetch();
    if (!$fuente) {
        throw new InvalidArgumentException('Fuente inexistente.');
    }
    return $fuente;
}

function insertar_evento(PDO $bd, int $fuente, string $codigo, ?string $usuario, string $descripcion, string $severidad, string $uuid): array
{
    $consulta = $bd->prepare('SELECT id, fuente_id, codigo, usuario, descripcion, severidad FROM eventos WHERE uuid = ?');
    $consulta->execute([$uuid]);
    $anterior = $consulta->fetch();
    if ($anterior) {
        if ((int) $anterior['fuente_id'] !== $fuente || $anterior['codigo'] !== $codigo || $anterior['usuario'] !== $usuario || $anterior['descripcion'] !== $descripcion || $anterior['severidad'] !== $severidad) {
            throw new DomainException('El identificador ya pertenece a otro evento.');
        }
        return ['id' => (int) $anterior['id'], 'duplicado' => true];
    }
    $consulta = $bd->prepare('INSERT INTO eventos (uuid, fuente_id, codigo, usuario, descripcion, severidad) VALUES (?, ?, ?, ?, ?, ?)');
    $consulta->execute([$uuid, $fuente, $codigo, $usuario, $descripcion, $severidad]);
    return ['id' => (int) $bd->lastInsertId(), 'duplicado' => false];
}

function crear_alerta(PDO $bd, int $fuente, string $tipo, string $nombre, string $descripcion, ?string $usuario, string $severidad, string $clave, int $primero, int $ultimo, int $cantidad = 1): void
{
    $consulta = $bd->prepare('INSERT INTO alertas (fuente_id, tipo, nombre, descripcion, usuario, severidad, correlacion, primer_evento_id, ultimo_evento_id, cantidad_eventos) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $consulta->execute([$fuente, $tipo, $nombre, $descripcion, $usuario, $severidad, hash('sha256', $clave), $primero, $ultimo, $cantidad]);
}

function evaluar_intentos(PDO $bd, int $fuente, string $usuario): void
{
    // Excluye los fallos que ya generaron una alerta.
    $consulta = $bd->prepare("SELECT COALESCE(MAX(ultimo_evento_id), 0) FROM alertas WHERE fuente_id = ? AND tipo = 'BRUTE_FORCE' AND usuario = ?");
    $consulta->execute([$fuente, $usuario]);
    $ultimo = (int) $consulta->fetchColumn();
    $consulta = $bd->prepare("SELECT id FROM eventos WHERE fuente_id = ? AND codigo = 'LOGIN_FAILED' AND usuario = ? AND fecha >= DATE_SUB(UTC_TIMESTAMP(6), INTERVAL " . VENTANA_MINUTOS . ' MINUTE) AND id > ? ORDER BY id LIMIT ' . UMBRAL_INTENTOS);
    $consulta->execute([$fuente, $usuario, $ultimo]);
    $ids = $consulta->fetchAll(PDO::FETCH_COLUMN);
    if (count($ids) === UMBRAL_INTENTOS) {
        crear_alerta($bd, $fuente, 'BRUTE_FORCE', 'Posible ataque de fuerza bruta', 'Cinco intentos fallidos del mismo usuario y fuente en cinco minutos.', $usuario, 'Alta', 'intentos:' . $fuente . ':' . end($ids), (int) $ids[0], (int) end($ids), count($ids));
    }
}

function comprobar_permiso(PDO $bd, string $uuid): array
{
    $bd->beginTransaction();
    try {
        $fuente = bloquear_fuente($bd, 'ADMIN-CBB');
        // La cuenta se elige en el servidor, no en la petición.
        $consulta = $bd->prepare('SELECT nombre, rol, activo FROM usuarios WHERE nombre = ?');
        $consulta->execute(['empleado']);
        $cuenta = $consulta->fetch();
        if ($cuenta && $cuenta['activo'] && $cuenta['rol'] === 'administrador') {
            $cantidad = (int) $bd->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
            $bd->commit();
            return ['permitido' => true, 'mensaje' => 'Operación autorizada.', 'datos_restringidos' => ['cuentas_registradas' => $cantidad]];
        }
        $evento = insertar_evento($bd, (int) $fuente['id'], 'ACCESS_DENIED', 'empleado', 'Operación administrativa restringida rechazada por permisos insuficientes.', 'Alta', $uuid);
        if (!$evento['duplicado']) {
            // Agrupa los intentos de la misma cuenta durante cinco minutos.
            $consulta = $bd->prepare("SELECT id FROM alertas WHERE fuente_id = ? AND tipo = 'ACCESS_DENIED' AND usuario = ? AND fecha >= DATE_SUB(UTC_TIMESTAMP(6), INTERVAL 5 MINUTE) LIMIT 1");
            $consulta->execute([$fuente['id'], 'empleado']);
            if (!$consulta->fetch()) {
                crear_alerta($bd, (int) $fuente['id'], 'ACCESS_DENIED', 'Intento de acceso no autorizado', 'El usuario no tiene permisos para consultar información administrativa restringida.', 'empleado', 'Alta', 'acceso:' . $evento['id'], $evento['id'], $evento['id']);
            }
        }
        $bd->commit();
        return ['permitido' => false, 'evento' => $evento, 'mensaje' => 'Acceso denegado. Se requiere el rol de administrador.'];
    } catch (Throwable $error) {
        if ($bd->inTransaction()) {
            $bd->rollBack();
        }
        throw $error;
    }
}

function recibir_heartbeat(PDO $bd, string $uuid): array
{
    $bd->beginTransaction();
    try {
        $fuente = bloquear_fuente($bd, 'INV-SCZ');
        $evento = insertar_evento($bd, (int) $fuente['id'], 'HEARTBEAT', null, 'Señal de actividad recibida desde inventarios.', 'Informativa', $uuid);
        // Un heartbeat repetido no actualiza la hora.
        if (!$evento['duplicado']) {
            if ($fuente['estado'] === 'inactiva') {
                insertar_evento($bd, (int) $fuente['id'], 'SERVICE_RECOVERED', null, 'El sistema de inventarios volvió a enviar señales de actividad.', 'Informativa', uuid());
                $consulta = $bd->prepare("UPDATE alertas SET estado = 'Resuelta' WHERE fuente_id = ? AND tipo = 'SERVICE_DOWN' AND correlacion = ? AND estado = 'Abierta'");
                $consulta->execute([$fuente['id'], hash('sha256', 'caida:' . $fuente['id'] . ':' . $fuente['incidente_actual'])]);
            }
            $consulta = $bd->prepare("UPDATE fuentes SET ultima_senal = UTC_TIMESTAMP(6), estado = 'activa', incidente_actual = NULL WHERE id = ?");
            $consulta->execute([$fuente['id']]);
        }
        $bd->commit();
        return ['evento' => $evento, 'mensaje' => $evento['duplicado'] ? 'Señal repetida; no se actualizó la hora.' : 'Señal recibida.'];
    } catch (Throwable $error) {
        if ($bd->inTransaction()) {
            $bd->rollBack();
        }
        throw $error;
    }
}

function verificar_inactividad(PDO $bd): array
{
    $bd->beginTransaction();
    try {
        $fuente = bloquear_fuente($bd, 'INV-SCZ');
        $nuevas = 0;
        // La fuente debe haber recibido al menos una señal.
        $consulta = $bd->prepare('SELECT ultima_senal IS NOT NULL AND ultima_senal < DATE_SUB(UTC_TIMESTAMP(6), INTERVAL ' . UMBRAL_INACTIVIDAD . ' SECOND) FROM fuentes WHERE id = ?');
        $consulta->execute([$fuente['id']]);
        if ($fuente['estado'] === 'activa' && $consulta->fetchColumn()) {
            $incidente = bin2hex(random_bytes(16));
            $evento = insertar_evento($bd, (int) $fuente['id'], 'SERVICE_DOWN', null, 'Inventarios lleva más de 15 segundos sin enviar señales de actividad.', 'Critica', uuid());
            crear_alerta($bd, (int) $fuente['id'], 'SERVICE_DOWN', 'Sistema de inventarios sin respuesta', 'No se han recibido heartbeats durante más de 15 segundos.', null, 'Critica', 'caida:' . $fuente['id'] . ':' . $incidente, $evento['id'], $evento['id']);
            $consulta = $bd->prepare("UPDATE fuentes SET estado = 'inactiva', incidente_actual = ? WHERE id = ?");
            $consulta->execute([$incidente, $fuente['id']]);
            $nuevas = 1;
        }
        $bd->commit();
        return ['alertas_nuevas' => $nuevas];
    } catch (Throwable $error) {
        if ($bd->inTransaction()) {
            $bd->rollBack();
        }
        throw $error;
    }
}

function registrar_evento(PDO $bd, string $fuente, string $descripcion, string $uuid): array
{
    $bd->beginTransaction();
    try {
        $origen = bloquear_fuente($bd, $fuente);
        $evento = insertar_evento($bd, (int) $origen['id'], 'LAB_EVENT', null, $descripcion, 'Informativa', $uuid);
        $bd->commit();
        return ['evento' => $evento];
    } catch (Throwable $error) {
        if ($bd->inTransaction()) {
            $bd->rollBack();
        }
        throw $error;
    }
}

function autenticar(PDO $bd, string $usuario, string $contrasena, string $uuid): array
{
    $bd->beginTransaction();
    try {
        $fuente = bloquear_fuente($bd, 'PORTAL-LPZ');
        $consulta = $bd->prepare('SELECT contrasena_hash, activo FROM usuarios WHERE nombre = ?');
        $consulta->execute([$usuario]);
        $cuenta = $consulta->fetch();
        $valido = $cuenta && (bool) $cuenta['activo'] && password_verify($contrasena, $cuenta['contrasena_hash']);
        $evento = insertar_evento($bd, (int) $fuente['id'], $valido ? 'LOGIN_SUCCESS' : 'LOGIN_FAILED', $usuario, $valido ? 'Inicio de sesión válido en el portal de empleados.' : 'Intento de autenticación fallido en el portal de empleados.', $valido ? 'Informativa' : 'Media', $uuid);
        if (!$valido && !$evento['duplicado']) {
            evaluar_intentos($bd, (int) $fuente['id'], $usuario);
        }
        $bd->commit();
        return ['autenticado' => (bool) $valido, 'evento' => $evento, 'mensaje' => $valido ? 'Inicio de sesión correcto.' : 'Credenciales incorrectas. Intento registrado.'];
    } catch (Throwable $error) {
        if ($bd->inTransaction()) {
            $bd->rollBack();
        }
        throw $error;
    }
}
