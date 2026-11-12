<?php
// Modelo: consultas SQL sobre turnos
class Turno
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = obtenerConexion();
    }

    // Turnos pendientes de hoy y mañana (lo usa el Dashboard)
    public function proximos(): array
    {
        return $this->pendientesHasta(1);
    }

    // Turnos pendientes desde hoy hasta dentro de N días
    public function pendientesHasta(int $dias): array
    {
        $sql = "SELECT t.fecha_hora, t.motivo, m.nombre AS mascota, m.especie,
                       v.nombre AS veterinario
                FROM turnos t
                JOIN mascotas m ON m.id = t.mascota_id
                JOIN veterinarios v ON v.id = t.veterinario_id
                WHERE t.estado = 'pendiente'
                  AND date(t.fecha_hora) BETWEEN date('now', 'localtime')
                                             AND date('now', 'localtime', '+' || :dias || ' days')
                ORDER BY t.fecha_hora";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':dias' => $dias]);
        return $stmt->fetchAll();
    }
}