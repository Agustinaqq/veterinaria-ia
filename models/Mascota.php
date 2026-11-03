<?php
// Modelo: todas las consultas SQL sobre mascotas
class Mascota
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = obtenerConexion();
    }

    // Lista todas las mascotas con el nombre de su dueño
    public function listar(): array
    {
        $sql = "SELECT m.id, m.nombre, m.especie, m.raza, m.peso, d.nombre AS dueno
                FROM mascotas m
                JOIN duenos d ON d.id = m.dueno_id
                ORDER BY m.nombre";
        return $this->pdo->query($sql)->fetchAll();
    }

    // Cantidad total de mascotas
    public function contar(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM mascotas')->fetchColumn();
    }
}