<?php
require __DIR__ . '/../includes/vista.php';
cabecera('lab', 'AndeSur Lab');
?>
<p class="text-secondary">Abra el monitor en otra ventana para observar los eventos y las alertas generados por el servidor.</p>
<div class="row g-3">
    <section class="col-lg-6"><div class="card h-100"><div class="card-header"><h2>Portal de empleados</h2><span class="small text-secondary">La Paz · Autenticación</span></div><div class="card-body">
        <p>Cuenta de prueba: <strong>empleado</strong>. Contraseña de demostración: <code>AndeSur2026!</code>.</p>
        <form id="form-login">
            <div class="mb-3"><label class="form-label" for="usuario">Nombre de usuario</label><input class="form-control" id="usuario" name="usuario" value="empleado" maxlength="80" autocomplete="username" required></div>
            <div class="mb-3"><label class="form-label" for="contrasena">Contraseña</label><input class="form-control" id="contrasena" name="contrasena" type="password" maxlength="256" autocomplete="current-password" required></div>
            <button class="btn btn-primary" type="submit">Iniciar sesión</button>
        </form>
        <div id="resultado-login" class="alert mt-3 mb-0" role="status" hidden></div>
        <p class="small text-secondary mt-3 mb-0">Cinco contraseñas incorrectas del mismo usuario en cinco minutos generan una alerta alta. Las contraseñas introducidas no se guardan en los registros.</p>
    </div></div></section>
    <section class="col-lg-6"><div class="card h-100"><div class="card-header"><h2>Sistema administrativo</h2><span class="small text-secondary">Cochabamba · Autorización</span></div><div class="card-body">
        <p>La cuenta de prueba <strong>empleado</strong> tiene el rol <strong>lector</strong>. La consulta restringida requiere un rol de administrador.</p>
        <button id="intentar-acceso" class="btn btn-primary" type="button">Consultar información administrativa restringida</button>
        <div id="resultado-permiso" class="alert mt-3 mb-0" role="status" hidden></div>
        <p class="small text-secondary mt-3 mb-0">PHP consulta el rol en MySQL y rechaza la operación antes de acceder a la información. Los intentos repetidos en cinco minutos comparten una alerta.</p>
    </div></div></section>
    <section class="col-12"><div class="card"><div class="card-header"><h2>Sistema de inventarios</h2><span class="small text-secondary">Centro logístico · Santa Cruz · Disponibilidad</span></div><div class="card-body">
        <div class="row align-items-center g-3"><div class="col-md-6">
            <dl class="mb-0"><dt>Emisión de señales</dt><dd id="estado-emision">Preparando simulador…</dd><dt>Estado central de la fuente</dt><dd id="estado-inventarios">Consultando…</dd><dt>Última señal recibida (Bolivia)</dt><dd id="ultima-senal" class="mb-0">Consultando…</dd></dl>
        </div><div class="col-md-6">
            <div class="d-flex flex-wrap gap-2"><button id="detener-servicio" class="btn btn-outline-secondary" type="button">Detener servicio</button><button id="reanudar-servicio" class="btn btn-success" type="button">Reanudar servicio</button></div>
            <p class="small text-secondary mt-3 mb-0">Se envía una señal cada 4 segundos. Con el monitor abierto, PHP detecta la falta de señales después de 15 segundos y genera una única alerta crítica por caída. La recuperación también se registra.</p>
        </div></div>
        <div id="resultado-servicio" class="alert mt-3 mb-0" role="status" hidden></div>
    </div></div></section>
</div>
<?php pie(); ?>
