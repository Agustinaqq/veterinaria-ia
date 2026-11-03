<?php
// Carga datos de prueba. Se corre una sola vez desde la terminal.
require __DIR__ . '/database.php';

$pdo = obtenerConexion();

// Si ya hay datos, no los vuelve a cargar
if ($pdo->query('SELECT COUNT(*) FROM duenos')->fetchColumn() > 0) {
    echo "La base ya tiene datos. No se cargó nada.\n";
    exit;
}

// Fechas relativas a hoy, así los datos de prueba nunca quedan viejos
function dia(int $dias, ?string $hora = null): string
{
    $fecha = date('Y-m-d', strtotime("$dias days"));
    return $hora ? "$fecha $hora" : $fecha;
}

// Inserta varias filas con una consulta preparada
function insertar(PDO $pdo, string $sql, array $filas): void
{
    $stmt = $pdo->prepare($sql);
    foreach ($filas as $fila) {
        $stmt->execute($fila);
    }
}

$pdo->beginTransaction();

insertar($pdo, 'INSERT INTO veterinarios (nombre, especialidad) VALUES (?, ?)', [
    ['Dra. Laura Méndez', 'Clínica general'],   // id 1
    ['Dr. Pablo Ríos', 'Cirugía'],              // id 2
    ['Dra. Sofía Acosta', 'Dermatología'],      // id 3
]);

insertar($pdo, 'INSERT INTO duenos (nombre, telefono, email) VALUES (?, ?, ?)', [
    ['Marta Gómez', '2494 123456', 'marta@mail.com'],       // id 1
    ['Jorge Paz', '2494 234567', 'jorge@mail.com'],         // id 2
    ['Carla Ruiz', '2494 345678', 'carla@mail.com'],        // id 3
    ['Diego Fernández', '2494 456789', 'diego@mail.com'],   // id 4
    ['Ana Torres', '2494 567890', 'ana@mail.com'],          // id 5
]);

insertar($pdo, 'INSERT INTO mascotas (dueno_id, nombre, especie, raza, fecha_nacimiento, peso) VALUES (?, ?, ?, ?, ?, ?)', [
    [1, 'Toby', 'perro', 'Caniche', dia(-1200), 6.5],            // id 1
    [1, 'Luna', 'gato', 'Siamés', dia(-800), 4.2],               // id 2
    [2, 'Rocco', 'perro', 'Labrador', dia(-2500), 32],           // id 3
    [3, 'Mishi', 'gato', 'Común europeo', dia(-150), 2.1],       // id 4
    [4, 'Bruno', 'perro', 'Ovejero alemán', dia(-90), 12],       // id 5
    [5, 'Nala', 'perro', 'Mestizo', dia(-3000), 18],             // id 6
    [5, 'Pelusa', 'conejo', 'Mini lop', dia(-600), 1.8],         // id 7
]);

insertar($pdo, 'INSERT INTO turnos (mascota_id, veterinario_id, fecha_hora, motivo, estado) VALUES (?, ?, ?, ?, ?)', [
    [1, 1, dia(-60, '10:00'), 'Control anual', 'realizado'],                // id 1
    [3, 2, dia(-30, '11:30'), 'Cojera pata trasera', 'realizado'],          // id 2
    [6, 3, dia(-15, '09:00'), 'Picazón y caída de pelo', 'realizado'],      // id 3
    [4, 1, dia(-20, '16:00'), 'Primera consulta y vacuna', 'realizado'],    // id 4
    [7, 1, dia(-5, '17:00'), 'Control de dientes', 'cancelado'],            // id 5
    [1, 1, dia(1, '10:00'), 'Control de oídos', 'pendiente'],               // id 6
    [5, 1, dia(1, '10:30'), 'Vacunación', 'pendiente'],                     // id 7
    [3, 2, dia(3, '12:00'), 'Control post tratamiento', 'pendiente'],       // id 8
]);

// Solo los turnos realizados tienen consulta
insertar($pdo, 'INSERT INTO consultas (turno_id, diagnostico, tratamiento, observaciones) VALUES (?, ?, ?, ?)', [
    [1, 'Sano. Leve sarro dental.', 'Limpieza dental recomendada en 6 meses.', 'La dueña consultó por alimento balanceado.'],
    [2, 'Inflamación leve en articulación de cadera.', 'Antiinflamatorio por 7 días y reposo.', 'Controlar en un mes. Evitar escaleras.'],
    [3, 'Dermatitis alérgica, probable origen alimentario.', 'Alimento hipoalergénico y baños medicados.', 'Revisar evolución en 3 semanas.'],
    [4, 'Gatito sano, buen peso.', 'Primera dosis de triple felina y desparasitación interna.', 'Completar esquema de vacunación.'],
]);

insertar($pdo, 'INSERT INTO vacunas (mascota_id, nombre_vacuna, fecha_aplicada, proxima_dosis) VALUES (?, ?, ?, ?)', [
    [1, 'Antirrábica', dia(-380), dia(-15)],     // vencida
    [1, 'Séxtuple', dia(-60), dia(305)],         // al día
    [2, 'Triple felina', dia(-400), dia(-35)],   // vencida
    [3, 'Antirrábica', dia(-200), dia(165)],     // al día
    [4, 'Triple felina', dia(-20), dia(8)],      // por vencer
    [5, 'Séxtuple', dia(-45), dia(-24)],         // vencida
    [6, 'Antirrábica', dia(-350), dia(15)],      // por vencer
]);

$pdo->commit();

echo "Datos de prueba cargados.\n";