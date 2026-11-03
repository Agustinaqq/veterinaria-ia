<?php
// Controlador de mascotas: pide los datos al modelo y elige la vista
class MascotaController
{
    public function listar(): void
    {
        $mascotas = (new Mascota())->listar();
        $titulo = 'Mascotas';

        require __DIR__ . '/../views/layouts/header.php';
        require __DIR__ . '/../views/mascotas/listado.php';
        require __DIR__ . '/../views/layouts/footer.php';
    }
}