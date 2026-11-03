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

    public function ficha(): void
    {
        // Validación del lado del servidor: el id tiene que ser un entero positivo
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        $modelo = new Mascota();
        $mascota = $id ? $modelo->buscarPorId($id) : null;

        if (!$mascota) {
            http_response_code(404);
            $titulo = 'Mascota no encontrada';
            require __DIR__ . '/../views/layouts/header.php';
            echo '<div class="alert alert-warning">No existe esa mascota.</div>';
            require __DIR__ . '/../views/layouts/footer.php';
            return;
        }

        $vacunas = (new Vacuna())->deMascota($id);
        $historial = $modelo->historial($id);
        $titulo = $mascota['nombre'];

        require __DIR__ . '/../views/layouts/header.php';
        require __DIR__ . '/../views/mascotas/ficha.php';
        require __DIR__ . '/../views/layouts/footer.php';
    }
}