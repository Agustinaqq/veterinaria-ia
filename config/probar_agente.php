<?php
// Prueba del agente con tools desde la terminal: php config/probar_agente.php "pregunta"
require __DIR__ . '/database.php';
require __DIR__ . '/helpers.php';
require __DIR__ . '/env.php';
require __DIR__ . '/../models/Mascota.php';
require __DIR__ . '/../models/Turno.php';
require __DIR__ . '/../models/Vacuna.php';
require __DIR__ . '/../services/IAService.php';
require __DIR__ . '/../services/HerramientasAsistente.php';
require __DIR__ . '/../services/AgenteAsistente.php';

$pregunta = $argv[1] ?? 'Cuantos gatos hay?';
$r = (new AgenteAsistente())->responder($pregunta);

echo "Respuesta: {$r['respuesta']}\n";
echo "Tokens: {$r['tokens']} | Pasos: {$r['pasos']} | Funciones: " . (implode(', ', $r['llamadas']) ?: 'ninguna') . "\n";