<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo) ?> · Veterinaria</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-dark bg-success mb-4">
    <div class="container">
        <a class="navbar-brand" href="index.php">🐾 Veterinaria</a>
        <div class="navbar-nav flex-row gap-3">
            <a class="nav-link" href="index.php?action=dashboard">Inicio</a>
            <a class="nav-link" href="index.php?action=mascotas">Mascotas</a>
            <a class="nav-link" href="index.php?action=asistente">Asistente</a>
        </div>
    </div>
</nav>
<main class="container">