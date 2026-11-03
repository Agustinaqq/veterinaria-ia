<?php
// Versión 1: elige qué datos del sistema mandarle a la IA según las palabras de la pregunta
class ContextoAsistente
{
    private const MAX_MASCOTAS = 3;

    // Devuelve el texto con los datos y la lista de secciones incluidas (para mostrar y medir)
    public function construir(string $pregunta): array
    {
        $texto = self::normalizar($pregunta);
        $lineas = [];
        $secciones = [];

        $mascotaModelo = new Mascota();
        $vacunaModelo = new Vacuna();
        $vencidas = $vacunaModelo->vencidas();
        $porVencer = $vacunaModelo->porVencer();
        $turnos = (new Turno())->proximos();

        // Siempre: un resumen corto con los totales
        $lineas[] = 'RESUMEN';
        $lineas[] = 'mascotas: ' . $mascotaModelo->contar()
            . ' | turnos hoy y mañana: ' . count($turnos)
            . ' | vacunas vencidas: ' . count($vencidas)
            . ' | vacunas por vencer (30 días): ' . count($porVencer);
        $secciones[] = 'resumen';

        // Si habla de vacunas: el detalle de vencidas y por vencer
        if ($this->menciona($texto, ['vacun', 'vencid', 'vence', 'dosis', 'antirrab', 'sextuple', 'triple'])) {
            $campos = ['mascota', 'nombre_vacuna', 'proxima_dosis', 'dueno', 'telefono'];
            $this->agregarSeccion($lineas, 'VACUNAS VENCIDAS (mascota | vacuna | próxima dosis | dueño | teléfono)', $vencidas, $campos);
            $this->agregarSeccion($lineas, 'VACUNAS POR VENCER (mascota | vacuna | próxima dosis | dueño | teléfono)', $porVencer, $campos);
            $secciones[] = 'vacunas';
        }

        // Si habla de turnos o de la agenda: los turnos de hoy y mañana
        if ($this->menciona($texto, ['turno', 'agenda', 'hoy', 'manana', 'atiend'])) {
            $this->agregarSeccion(
                $lineas,
                'TURNOS DE HOY Y MAÑANA (fecha y hora | mascota | especie | motivo | veterinario)',
                $turnos,
                ['fecha_hora', 'mascota', 'especie', 'motivo', 'veterinario']
            );
            $secciones[] = 'turnos';
        }

        // Si nombra una mascota: su ficha completa (datos, vacunas e historial)
        $encontradas = 0;
        foreach ($mascotaModelo->listar() as $m) {
            if ($encontradas >= self::MAX_MASCOTAS) {
                break;
            }
            $nombre = preg_quote(self::normalizar($m['nombre']), '/');
            if (!preg_match('/\b' . $nombre . '\b/u', $texto)) {
                continue;
            }
            $encontradas++;

            $f = $mascotaModelo->buscarPorId($m['id']);
            $lineas[] = '';
            $lineas[] = 'MASCOTA (nombre | especie | raza | edad | peso | dueño | teléfono)';
            $lineas[] = implode(' | ', [
                $f['nombre'], $f['especie'], $f['raza'], edad($f['fecha_nacimiento']),
                $f['peso'] . ' kg', $f['dueno'], $f['telefono'],
            ]);
            $this->agregarSeccion(
                $lineas,
                "VACUNAS DE {$f['nombre']} (vacuna | aplicada | próxima dosis | estado)",
                $vacunaModelo->deMascota($m['id']),
                ['nombre_vacuna', 'fecha_aplicada', 'proxima_dosis', 'estado']
            );
            $this->agregarSeccion(
                $lineas,
                "HISTORIAL DE {$f['nombre']} (fecha | motivo | veterinario | diagnóstico | tratamiento | observaciones)",
                $mascotaModelo->historial($m['id']),
                ['fecha_hora', 'motivo', 'veterinario', 'diagnostico', 'tratamiento', 'observaciones']
            );
            $secciones[] = 'ficha de ' . $f['nombre'];
        }

        return ['texto' => implode("\n", $lineas), 'secciones' => $secciones];
    }

    // Agrega un título y una línea por fila, con los campos separados por " | "
    // (más corto que JSON, que repite el nombre de cada campo en cada fila)
    private function agregarSeccion(array &$lineas, string $titulo, array $filas, array $campos): void
    {
        $lineas[] = '';
        $lineas[] = $titulo;
        if (empty($filas)) {
            $lineas[] = '(ninguno)';
            return;
        }
        foreach ($filas as $fila) {
            $lineas[] = implode(' | ', array_map(fn($c) => $fila[$c] ?? '-', $campos));
        }
    }

    // ¿La pregunta contiene alguna de estas palabras (o comienzos de palabra)?
    private function menciona(string $texto, array $palabras): bool
    {
        foreach ($palabras as $palabra) {
            if (str_contains($texto, $palabra)) {
                return true;
            }
        }
        return false;
    }

    // Minúsculas y sin tildes, para que "Mañana", "mañana" y "manana" coincidan
    private static function normalizar(string $texto): string
    {
        $texto = mb_strtolower($texto, 'UTF-8');
        return strtr($texto, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
    }
}