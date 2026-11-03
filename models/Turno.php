<?php
// Modelo: consultas SQL sobre turnos
class Turno
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = obtenerConexion();
    }

    // Turnos pendientes de hoy y mañana
    public function proximos(): array
    {
        $sql = "SELECT t.fecha_hora, t.motivo, m.nombre AS mascota, m.especie,
                       v.nombre AS veterinario
                FROM turnos t
                JOIN mascotas m ON m.id = t.mascota_id
                JOIN veterinarios v ON v.id = t.veterinario_id
                WHERE t.estado = 'pendiente'
                  AND date(t.fecha_hora) BETWEEN date('now', 'localtime')
                                             AND date('now', 'localtime', '+1 day')
                ORDER BY t.fecha_hora";
        return $this->pdo->query($sql)->fetchAll();
    }
}