<a href="index.php?action=mascotas" class="btn btn-link px-0 mb-2">← Volver al listado</a>

<h1 class="h3 mb-3">
    <?= e($mascota['nombre']) ?>
    <small class="text-muted"><?= e($mascota['especie']) ?></small>
</h1>

<!-- Datos de la mascota y del dueño -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card h-100"><div class="card-body">
            <h2 class="h6 text-muted">Datos</h2>
            <p class="mb-1"><strong>Raza:</strong> <?= e($mascota['raza']) ?></p>
            <p class="mb-1"><strong>Edad:</strong> <?= e(edad($mascota['fecha_nacimiento'])) ?></p>
            <p class="mb-0"><strong>Peso:</strong> <?= e($mascota['peso']) ?> kg</p>
        </div></div>
    </div>
    <div class="col-md-6">
        <div class="card h-100"><div class="card-body">
            <h2 class="h6 text-muted">Dueño</h2>
            <p class="mb-1"><strong><?= e($mascota['dueno']) ?></strong></p>
            <p class="mb-1">📞 <?= e($mascota['telefono']) ?></p>
            <p class="mb-0">✉️ <?= e($mascota['email']) ?></p>
        </div></div>
    </div>
</div>

<!-- Vacunas -->
<h2 class="h5">Vacunas</h2>
<?php if (empty($vacunas)): ?>
    <p class="text-muted">Sin vacunas registradas.</p>
<?php else: ?>
    <?php $colores = ['vencida' => 'bg-danger', 'por vencer' => 'bg-warning text-dark', 'al día' => 'bg-success']; ?>
    <div class="table-responsive mb-4">
        <table class="table table-sm">
            <thead><tr><th>Vacuna</th><th>Aplicada</th><th>Próxima dosis</th><th>Estado</th></tr></thead>
            <tbody>
            <?php foreach ($vacunas as $v): ?>
                <tr>
                    <td><?= e($v['nombre_vacuna']) ?></td>
                    <td><?= date('d/m/Y', strtotime($v['fecha_aplicada'])) ?></td>
                    <td><?= $v['proxima_dosis'] ? date('d/m/Y', strtotime($v['proxima_dosis'])) : '—' ?></td>
                    <td><span class="badge <?= $colores[$v['estado']] ?>"><?= e($v['estado']) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<!-- Historial clínico -->
<h2 class="h5">Historial clínico</h2>
<?php if (empty($historial)): ?>
    <p class="text-muted">Sin consultas registradas.</p>
<?php else: ?>
    <?php foreach ($historial as $h): ?>
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between flex-wrap gap-2">
                <span><strong><?= date('d/m/Y', strtotime($h['fecha_hora'])) ?></strong> · <?= e($h['motivo']) ?></span>
                <span class="text-muted"><?= e($h['veterinario']) ?></span>
            </div>
            <div class="card-body">
                <p class="mb-1"><strong>Diagnóstico:</strong> <?= e($h['diagnostico'] ?? 'Sin cargar') ?></p>
                <p class="mb-1"><strong>Tratamiento:</strong> <?= e($h['tratamiento'] ?? 'Sin cargar') ?></p>
                <p class="mb-0"><strong>Observaciones:</strong> <?= e($h['observaciones'] ?? '—') ?></p>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>