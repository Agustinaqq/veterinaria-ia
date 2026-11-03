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

    // Una mascota con los datos de su dueño. Devuelve null si no existe.
    public function buscarPorId(int $id): ?array
    {
        $sql = "SELECT m.*, d.nombre AS dueno, d.telefono, d.email
                FROM mascotas m
                JOIN duenos d ON d.id = m.dueno_id
                WHERE m.id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    // Historial clínico: turnos realizados con su consulta, del más reciente al más viejo
    public function historial(int $id): array
    {
        $sql = "SELECT t.fecha_hora, t.motivo, v.nombre AS veterinario,
                       c.diagnostico, c.tratamiento, c.observaciones
                FROM turnos t
                JOIN veterinarios v ON v.id = t.veterinario_id
                LEFT JOIN consultas c ON c.turno_id = t.id
                WHERE t.mascota_id = :id AND t.estado = 'realizado'
                ORDER BY t.fecha_hora DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetchAll();
    }
}