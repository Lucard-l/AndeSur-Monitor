<?php
declare(strict_types=1);

date_default_timezone_set('UTC');
ini_set('display_errors', '0');

// El prototipo se ejecuta en una sola computadora, sin acceso público.
if (PHP_SAPI !== 'cli' && !in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    exit('El laboratorio solo admite conexiones desde esta computadora.');
}

if (PHP_SAPI !== 'cli') {
    $servidor = parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST);
    if (!in_array($servidor, ['localhost', '127.0.0.1', '[::1]'], true)) {
        http_response_code(403);
        exit('Utilice localhost para abrir el laboratorio.');
    }
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: same-origin');
    header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; font-src 'self'; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
    header('Cache-Control: no-store');
    session_name('ANDESUR_MONITOR');
    session_start([
        'use_strict_mode' => true,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
        'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function escapar(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function conexion(): PDO
{
    $ruta = __DIR__ . '/../config/config.local.php';
    if (!is_file($ruta)) {
        throw new RuntimeException('Falta la configuración local de MySQL.');
    }
    $config = require $ruta;
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $config['host'], $config['puerto'], $config['base']);
    $bd = new PDO($dsn, $config['usuario'], $config['contrasena'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $bd->exec("SET time_zone = '+00:00'");
    return $bd;
}

function uuid(): string
{
    $bytes = random_bytes(16);
    $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
    $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
    $hex = bin2hex($bytes);
    return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4) . '-' . substr($hex, 16, 4) . '-' . substr($hex, 20);
}
