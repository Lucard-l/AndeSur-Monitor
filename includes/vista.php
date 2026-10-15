<?php
declare(strict_types=1);
require_once __DIR__ . '/inicio.php';

function cabecera(string $pagina, string $titulo): void
{
    $token = escapar($_SESSION['csrf']);
    session_write_close();
    ?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= $token ?>">
    <title><?= escapar($titulo) ?> · AndeSur Monitor</title>
    <link rel="icon" href="data:,">
    <link rel="stylesheet" href="assets/vendor/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/app.css">
    <script src="assets/js/comun.js" defer></script>
    <?php if ($pagina === 'dashboard'): ?>
    <script src="assets/vendor/chart.umd.js" defer></script>
    <?php endif; ?>
    <script src="assets/js/<?= escapar($pagina) ?>.js" defer></script>
</head>
<body>
<div class="aplicacion">
    <aside class="sidebar" aria-label="Navegación principal">
        <a class="marca" href="dashboard.php">AndeSur <strong>Monitor</strong></a>
        <div class="marca-detalle">Registros y monitoreo</div>
        <nav class="nav flex-column mt-4">
            <a class="nav-link <?= $pagina === 'dashboard' ? 'activo' : '' ?>" href="dashboard.php" <?= $pagina === 'dashboard' ? 'aria-current="page"' : '' ?>>Resumen de monitoreo</a>
            <a class="nav-link <?= $pagina === 'lab' ? 'activo' : '' ?>" href="lab.php" <?= $pagina === 'lab' ? 'aria-current="page"' : '' ?>>AndeSur Lab</a>
        </nav>
        <p class="sidebar-nota">Sprint 1<br>Prototipo académico</p>
    </aside>
    <div class="contenido">
        <header class="encabezado">
            <div><span class="text-secondary small">Grupo Empresarial AndeSur S.A.</span><h1><?= escapar($titulo) ?></h1></div>
            <span class="badge text-bg-light border">Hora de Bolivia · UTC−4</span>
        </header>
        <main class="container-fluid p-3 p-lg-4">
            <div class="aviso-laboratorio">ENTORNO ACADÉMICO — DATOS SIMULADOS</div>
    <?php
}

function pie(): void
{
    ?>
        </main>
        <footer>AndeSur Monitor · Laboratorio local · Sprint 1</footer>
    </div>
</div>
</body>
</html>
    <?php
}
