<?php
// Modelo: consultas SQL sobre vacunas
class Vacuna
{
    private PDO $pdo;

    // Solo cuenta la última aplicación de cada vacuna por mascota.
    // Si a Toby le dieron la antirrábica de nuevo, la dosis vieja ya no está "vencida".
    private const SOLO_ULTIMA = "NOT EXISTS (
        SELECT 1 FROM vacunas v2
        WHERE v2.mascota_id = v.mascota_id
          AND v2.nombre_vacuna = v.nombre_vacuna
          AND v2.fecha_aplicada > v.fecha_aplicada
    )";

    // Columnas y joins que comparten las consultas del Dashboard
    private const BASE = "SELECT v.nombre_vacuna, v.proxima_dosis, m.nombre AS mascota,
                                 d.nombre AS dueno, d.telefono
                          FROM vacunas v
                          JOIN mascotas m ON m.id = v.mascota_id
                          JOIN duenos d ON d.id = m.dueno_id";

    public function __construct()
    {
        $this->pdo = obtenerConexion();
    }

    // Vacunas cuya próxima dosis ya pasó
    public function vencidas(): array
    {
        $sql = self::BASE . "
                WHERE v.proxima_dosis < date('now', 'localtime')
                  AND " . self::SOLO_ULTIMA . "
                ORDER BY v.proxima_dosis";
        return $this->pdo->query($sql)->fetchAll();
    }

    // Vacunas que vencen dentro de los próximos días (30 por defecto)
    public function porVencer(int $dias = 30): array
    {
        $sql = self::BASE . "
                WHERE v.proxima_dosis BETWEEN date('now', 'localtime')
                                          AND date('now', 'localtime', '+' || :dias || ' days')
                  AND " . self::SOLO_ULTIMA . "
                ORDER BY v.proxima_dosis";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':dias' => $dias]);
        return $stmt->fetchAll();
    }

    // Última dosis de cada vacuna de una mascota, con su estado calculado en SQL
    public function deMascota(int $mascotaId): array
    {
        $sql = "SELECT v.nombre_vacuna, v.fecha_aplicada, v.proxima_dosis,
                       CASE
                           WHEN v.proxima_dosis < date('now', 'localtime') THEN 'vencida'
                           WHEN v.proxima_dosis <= date('now', 'localtime', '+30 days') THEN 'por vencer'
                           ELSE 'al día'
                       END AS estado
                FROM vacunas v
                WHERE v.mascota_id = :id
                  AND " . self::SOLO_ULTIMA . "
                ORDER BY v.proxima_dosis";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $mascotaId]);
        return $stmt->fetchAll();
    }
}