<?php
// Controlador del inicio: junta los datos de varios modelos
class DashboardController
{
    public function index(): void
    {
        $totalMascotas = (new Mascota())->contar();
        $turnos = (new Turno())->proximos();
        $vacunas = new Vacuna();
        $vencidas = $vacunas->vencidas();
        $porVencer = $vacunas->porVencer();
        $titulo = 'Inicio';

        require __DIR__ . '/../views/layouts/header.php';
        require __DIR__ . '/../views/dashboard/index.php';
        require __DIR__ . '/../views/layouts/footer.php';
    }
}