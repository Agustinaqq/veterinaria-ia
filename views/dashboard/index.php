<h1 class="h3 mb-4">Inicio</h1>

<!-- Totales -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card text-center"><div class="card-body">
            <div class="display-6"><?= $totalMascotas ?></div>
            <div class="text-muted">Mascotas</div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center"><div class="card-body">
            <div class="display-6"><?= count($turnos) ?></div>
            <div class="text-muted">Turnos hoy y mañana</div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center border-danger"><div class="card-body">
            <div class="display-6 text-danger"><?= count($vencidas) ?></div>
            <div class="text-muted">Vacunas vencidas</div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center border-warning"><div class="card-body">
            <div class="display-6 text-warning"><?= count($porVencer) ?></div>
            <div class="text-muted">Por vencer (30 días)</div>
        </div></div>
    </div>
</div>

<!-- Próximos turnos -->
<h2 class="h5">Próximos turnos</h2>
<?php if (empty($turnos)): ?>
    <p class="text-muted">No hay turnos para hoy ni mañana.</p>
<?php else: ?>
    <div class="table-responsive mb-4">
        <table class="table table-sm">
            <thead><tr><th>Fecha y hora</th><th>Mascota</th><th>Motivo</th><th>Veterinario</th></tr></thead>
            <tbody>
            <?php foreach ($turnos as $t): ?>
                <tr>
                    <td><?= date('d/m H:i', strtotime($t['fecha_hora'])) ?></td>
                    <td><?= e($t['mascota']) ?> (<?= e($t['especie']) ?>)</td>
                    <td><?= e($t['motivo']) ?></td>
                    <td><?= e($t['veterinario']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<!-- Vacunas: las dos tablas tienen las mismas columnas -->
<?php foreach (['Vacunas vencidas' => $vencidas, 'Vacunas por vencer' => $porVencer] as $subtitulo => $lista): ?>
    <h2 class="h5"><?= $subtitulo ?></h2>
    <?php if (empty($lista)): ?>
        <p class="text-muted">Ninguna.</p>
    <?php else: ?>
        <div class="table-responsive mb-4">
            <table class="table table-sm">
                <thead><tr><th>Mascota</th><th>Vacuna</th><th>Próxima dosis</th><th>Dueño</th><th>Teléfono</th></tr></thead>
                <tbody>
                <?php foreach ($lista as $v): ?>
                    <tr>
                        <td><?= e($v['mascota']) ?></td>
                        <td><?= e($v['nombre_vacuna']) ?></td>
                        <td><?= date('d/m/Y', strtotime($v['proxima_dosis'])) ?></td>
                        <td><?= e($v['dueno']) ?></td>
                        <td><?= e($v['telefono']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
<?php endforeach; ?>
