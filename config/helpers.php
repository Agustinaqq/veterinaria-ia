<?php
// Escapa el texto antes de mostrarlo en HTML (evita que se inyecte código en la vista)
function e(mixed $texto): string
{
    return htmlspecialchars((string) ($texto ?? ''), ENT_QUOTES, 'UTF-8');
}

// Calcula la edad a partir de la fecha de nacimiento ("3 años", "5 meses")
function edad(?string $fechaNacimiento): string
{
    if (!$fechaNacimiento) {
        return 'Sin dato';
    }

    $diferencia = (new DateTime($fechaNacimiento))->diff(new DateTime());

    if ($diferencia->y > 0) {
        return $diferencia->y . ($diferencia->y === 1 ? ' año' : ' años');
    }
    return $diferencia->m . ($diferencia->m === 1 ? ' mes' : ' meses');
}