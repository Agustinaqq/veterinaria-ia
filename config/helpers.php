<?php
// Escapa el texto antes de mostrarlo en HTML (evita que se inyecte código en la vista)
function e(mixed $texto): string
{
    return htmlspecialchars((string) ($texto ?? ''), ENT_QUOTES, 'UTF-8');
}