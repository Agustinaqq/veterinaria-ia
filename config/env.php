<?php
// Lee el archivo .env y devuelve el valor de una clave (o null si no existe)
function env(string $clave): ?string
{
    static $valores = null;

    // Se lee el archivo una sola vez y se guarda en memoria
    if ($valores === null) {
        $valores = [];
        $ruta = __DIR__ . '/../.env';

        if (is_file($ruta)) {
            foreach (file($ruta, FILE_IGNORE_NEW_LINES) as $linea) {
                $linea = trim($linea);
                // Se saltean líneas vacías, comentarios y líneas sin "="
                if ($linea === '' || str_starts_with($linea, '#') || !str_contains($linea, '=')) {
                    continue;
                }
                [$nombre, $valor] = explode('=', $linea, 2);
                $valores[trim($nombre)] = trim($valor);
            }
        }
    }

    return $valores[$clave] ?? null;
}