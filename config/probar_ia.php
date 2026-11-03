<?php
// Prueba rápida de conexión con Groq. Se corre desde la terminal.
require __DIR__ . '/database.php';   // para la zona horaria
require __DIR__ . '/env.php';
require __DIR__ . '/../services/IAService.php';

$ia = new IAService();
$resultado = $ia->chat(
    [['role' => 'user', 'content' => 'Respondé únicamente con la palabra: funciona']],
    'prueba'
);

echo 'Respuesta: ' . $resultado['mensaje']['content'] . "\n";
echo 'Tokens usados: ' . $resultado['uso']['total_tokens'] . "\n";