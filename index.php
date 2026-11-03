<?php
// Punto de entrada: decide qué controlador atiende cada pedido
require __DIR__ . '/config/database.php';
require __DIR__ . '/config/helpers.php';

require __DIR__ . '/models/Mascota.php';
require __DIR__ . '/models/Turno.php';
require __DIR__ . '/models/Vacuna.php';

require __DIR__ . '/controllers/DashboardController.php';
require __DIR__ . '/controllers/MascotaController.php';

$action = $_GET['action'] ?? 'dashboard';

switch ($action) {
    case 'dashboard':
        (new DashboardController())->index();
        break;

    case 'mascotas':
        (new MascotaController())->listar();
        break;

    default:
        http_response_code(404);
        echo 'Página no encontrada';
}