<?php
require __DIR__ . '/../includes/vista.php';
cabecera('dashboard', 'Resumen de monitoreo');
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <p class="text-secondary mb-0">Eventos y alertas de las fuentes monitoreadas.</p>
    <span id="actualizacion" class="small text-secondary" role="status">Consultando datos…</span>
</div>
<div id="error-dashboard" class="alert alert-danger" role="alert" hidden></div>
<section class="row g-3 mb-4" aria-label="Indicadores de monitoreo">
    <?php foreach (['eventos' => 'Eventos registrados', 'alertas_abiertas' => 'Alertas abiertas', 'fuentes' => 'Fuentes monitoreadas', 'alertas_graves' => 'Abiertas altas o críticas'] as $id => $etiqueta): ?>
    <div class="col-6 col-xl-3"><div class="card indicador"><div class="card-body"><div class="text-secondary small"><?= $etiqueta ?></div><div class="numero" id="<?= $id ?>">—</div></div></div></div>
    <?php endforeach; ?>
</section>
<section class="row g-3 mb-4">
    <div class="col-lg-7"><div class="card h-100"><div class="card-header"><h2>Eventos por hora</h2><span class="small text-secondary">Hora actual y 23 anteriores · Bolivia</span></div><div class="card-body grafico"><canvas id="grafico-eventos" role="img" aria-label="Eventos registrados por hora"></canvas><p id="grafico-resumen" class="small text-secondary mt-2" aria-live="polite"></p></div></div></div>
    <div class="col-lg-5"><div class="card h-100"><div class="card-header"><h2>Estado de las fuentes</h2></div><div class="card-body" id="lista-fuentes">Consultando fuentes…</div></div></div>
</section>
<section class="card mb-4"><div class="card-header"><h2>Alertas recientes</h2><span class="small text-secondary">Últimas 30 alertas</span></div><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Detección</th><th>Alerta</th><th>Fuente</th><th>Severidad</th><th>Estado</th></tr></thead><tbody id="tabla-alertas"><tr><td colspan="5" class="text-secondary p-3">Consultando alertas…</td></tr></tbody></table></div></section>
<section class="card"><div class="card-header"><h2>Últimos eventos</h2><span class="small text-secondary">Hasta 50 registros · orden del más reciente al más antiguo</span></div><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Fecha y hora</th><th>Fuente</th><th>Ubicación</th><th>Tipo</th><th>Descripción</th><th>Severidad</th></tr></thead><tbody id="tabla-eventos"><tr><td colspan="6" class="text-secondary p-3">Consultando eventos…</td></tr></tbody></table></div></section>
<?php pie(); ?>
