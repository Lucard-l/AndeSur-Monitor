<?php
declare(strict_types=1);
require __DIR__ . '/../includes/inicio.php';
require __DIR__ . '/../includes/eventos.php';
header('Content-Type: application/json; charset=utf-8');

function responder(array $datos, int $estado = 200): never
{
    http_response_code($estado);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit;
}

function texto(array $datos, string $campo, int $maximo): string
{
    $valor = $datos[$campo] ?? null;
    if (!is_string($valor) || trim($valor) === '' || strlen($valor) > $maximo || preg_match('/[\x00-\x1f\x7f]/', $valor)) {
        throw new InvalidArgumentException('Campo inválido: ' . $campo . '.');
    }
    return $campo === 'contrasena' ? $valor : trim($valor);
}

try {
    $accion = $_GET['accion'] ?? '';
    $metodos = ['login' => 'POST', 'permiso' => 'POST', 'heartbeat' => 'POST', 'verificar' => 'POST', 'evento' => 'POST', 'resumen' => 'GET', 'eventos' => 'GET', 'alertas' => 'GET', 'fuentes' => 'GET'];
    if (!is_string($accion) || !isset($metodos[$accion])) {
        responder(['error' => 'Operación inexistente.'], 404);
    }
    if ($_SERVER['REQUEST_METHOD'] !== $metodos[$accion]) {
        header('Allow: ' . $metodos[$accion]);
        responder(['error' => 'Método HTTP no permitido.'], 405);
    }
    $datos = [];
    if ($metodos[$accion] === 'POST') {
        if (!hash_equals($_SESSION['csrf'], $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
            responder(['error' => 'Solicitud no autorizada. Abra AndeSur Lab en esta computadora.'], 403);
        }
        if (strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0])) !== 'application/json') {
            responder(['error' => 'Se requiere JSON.'], 415);
        }
        $cuerpo = file_get_contents('php://input', false, null, 0, 8193);
        if (strlen($cuerpo) > 8192) {
            responder(['error' => 'Solicitud demasiado grande.'], 413);
        }
        $datos = json_decode($cuerpo, true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($datos) || !str_starts_with(ltrim($cuerpo), '{')) {
            throw new InvalidArgumentException('Se requiere un objeto JSON.');
        }
        if ($accion !== 'verificar') {
            if (!isset($datos['uuid']) || !is_string($datos['uuid']) || !preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/i', $datos['uuid'])) {
                throw new InvalidArgumentException('UUID inválido.');
            }
            $datos['uuid'] = strtolower($datos['uuid']);
        }
    }
    session_write_close();
    $bd = conexion();
    switch ($accion) {
        case 'permiso':
            $resultado = comprobar_permiso($bd, $datos['uuid']);
            responder($resultado, $resultado['permitido'] ? 200 : 403);
        case 'heartbeat':
            responder(recibir_heartbeat($bd, $datos['uuid']));
        case 'verificar':
            responder(verificar_inactividad($bd));
        case 'evento':
            $resultado = registrar_evento($bd, texto($datos, 'fuente', 30), texto($datos, 'descripcion', 255), $datos['uuid']);
            responder($resultado, $resultado['evento']['duplicado'] ? 200 : 201);
        case 'login':
            $usuario = texto($datos, 'usuario', 80);
            $contrasena = texto($datos, 'contrasena', 256);
            $id = strtolower(texto($datos, 'uuid', 36));
            $resultado = autenticar($bd, $usuario, $contrasena, $id);
            responder($resultado, $resultado['autenticado'] ? 200 : 401);
        case 'resumen':
            $indicadores = [
                'eventos' => (int) $bd->query('SELECT COUNT(*) FROM eventos')->fetchColumn(),
                'alertas_abiertas' => (int) $bd->query("SELECT COUNT(*) FROM alertas WHERE estado = 'Abierta'")->fetchColumn(),
                'fuentes' => (int) $bd->query('SELECT COUNT(*) FROM fuentes')->fetchColumn(),
                'alertas_graves' => (int) $bd->query("SELECT COUNT(*) FROM alertas WHERE estado = 'Abierta' AND severidad IN ('Alta', 'Critica')")->fetchColumn(),
            ];
            $horas = $bd->query("SELECT DATE_FORMAT(fecha, '%Y-%m-%d %H:00:00') AS hora, COUNT(*) AS cantidad FROM eventos WHERE fecha >= DATE_SUB(DATE_FORMAT(UTC_TIMESTAMP(), '%Y-%m-%d %H:00:00'), INTERVAL 23 HOUR) GROUP BY hora ORDER BY hora")->fetchAll();
            responder(['indicadores' => $indicadores, 'horas' => $horas, 'hora_servidor' => gmdate('Y-m-d\TH:i:s\Z')]);
        case 'eventos':
            responder(['eventos' => $bd->query('SELECT e.id, e.uuid, e.codigo, e.usuario, e.descripcion, e.severidad, e.fecha, e.es_simulado, f.nombre AS fuente, f.ubicacion FROM eventos e JOIN fuentes f ON f.id = e.fuente_id ORDER BY e.id DESC LIMIT 50')->fetchAll()]);
        case 'alertas':
            responder(['alertas' => $bd->query('SELECT a.id, a.tipo, a.nombre, a.descripcion, a.usuario, a.severidad, a.estado, a.fecha, a.primer_evento_id, a.ultimo_evento_id, a.cantidad_eventos, f.nombre AS fuente, f.ubicacion FROM alertas a JOIN fuentes f ON f.id = a.fuente_id ORDER BY a.id DESC LIMIT 30')->fetchAll()]);
        case 'fuentes':
            responder(['fuentes' => $bd->query('SELECT codigo, nombre, ubicacion, estado, ultima_senal FROM fuentes ORDER BY id')->fetchAll()]);
    }
} catch (JsonException | InvalidArgumentException $error) {
    responder(['error' => $error instanceof JsonException ? 'JSON inválido.' : $error->getMessage()], 400);
} catch (DomainException $error) {
    responder(['error' => $error->getMessage()], 409);
} catch (PDOException $error) {
    if (($error->errorInfo[1] ?? null) === 1062) {
        responder(['error' => 'El identificador ya está registrado.'], 409);
    }
    error_log('AndeSur Monitor: operación MySQL fallida.');
    responder(['error' => 'No se pudo completar la operación. Revise la configuración y disponibilidad de MySQL.'], 500);
} catch (Throwable $error) {
    error_log('AndeSur Monitor: fallo interno de tipo ' . get_class($error));
    responder(['error' => 'No se pudo completar la operación. Revise la configuración y disponibilidad de MySQL.'], 500);
}
