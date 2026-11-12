<?php
// Borra la base y la vuelve a crear con los datos de prueba, con fechas relativas a hoy.
// Correrlo antes de cada demo: php config/resetear.php
$ruta = __DIR__ . '/../veterinaria.db';

if (is_file($ruta)) {
    unlink($ruta);
    echo "Base anterior borrada.\n";
}

require __DIR__ . '/seed.php';