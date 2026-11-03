<h1 class="h3 mb-3">Mascotas</h1>

<div class="table-responsive">
    <table class="table table-striped align-middle">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Especie</th>
                <th>Raza</th>
                <th>Peso (kg)</th>
                <th>Dueño</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($mascotas as $m): ?>
                <tr>
                    <td><?= e($m['nombre']) ?></td>
                    <td><?= e($m['especie']) ?></td>
                    <td><?= e($m['raza']) ?></td>
                    <td><?= e($m['peso']) ?></td>
                    <td><?= e($m['dueno']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>