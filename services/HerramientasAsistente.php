<?php
// Versión 2: las funciones (tools) que la IA puede pedir. La IA elige cuál; este código la ejecuta.
class HerramientasAsistente
{
    // Descripción de cada tool en el formato de la API. El modelo SOLO ve esto,
    // por eso cada descripción tiene que decir claramente cuándo usarla.
    public function definiciones(): array
    {
        return [
            $this->tool(
                'buscar_mascotas',
                'Busca mascotas por nombre o por especie. Devuelve id, nombre, especie, raza y dueño. Usala para encontrar el id de una mascota o para listar o contar por especie. Sin parámetros devuelve todas.',
                [
                    'nombre' => ['type' => 'string', 'description' => 'Nombre o parte del nombre de la mascota'],
                    'especie' => ['type' => 'string', 'description' => 'Especie: perro, gato, conejo, etc.'],
                ]
            ),
            $this->tool(
                'ficha_mascota',
                'Devuelve la ficha completa de una mascota: datos, dueño, estado de sus vacunas e historial clínico. Requiere el id; si no lo tenés, primero usá buscar_mascotas.',
                ['id' => ['type' => 'integer', 'description' => 'Id de la mascota']],
                ['id']
            ),
            $this->tool(
                'turnos_proximos',
                'Turnos pendientes desde hoy hasta dentro de N días. Con dias=0 solo hoy, con dias=1 hoy y mañana.',
                ['dias' => ['type' => 'integer', 'description' => 'Días hacia adelante, entre 0 y 30. Por defecto 1.']]
            ),
            $this->tool(
                'vacunas_pendientes',
                'Lista las vacunas vencidas, o las que vencen en los próximos 30 días, con mascota, dueño y teléfono.',
                ['tipo' => ['type' => 'string', 'enum' => ['vencidas', 'por_vencer'], 'description' => 'Qué listar']],
                ['tipo']
            ),
        ];
    }

    // Ejecuta la tool pedida. Los errores se devuelven como TEXTO, para que el modelo
    // pueda leerlos y corregir el pedido, en vez de cortar el loop con una excepción.
    public function ejecutar(string $nombre, array $args): string
    {
        try {
            return match ($nombre) {
                'buscar_mascotas' => $this->buscarMascotas($args),
                'ficha_mascota' => $this->fichaMascota($args),
                'turnos_proximos' => $this->turnosProximos($args),
                'vacunas_pendientes' => $this->vacunasPendientes($args),
                default => "Error: la función '$nombre' no existe.",
            };
        } catch (Throwable $e) {
            error_log($e->getMessage());
            return 'Error interno al ejecutar la función.';
        }
    }

    private function buscarMascotas(array $args): string
    {
        $nombre = trim((string) ($args['nombre'] ?? '')) ?: null;
        $especie = trim((string) ($args['especie'] ?? '')) ?: null;
        $filas = (new Mascota())->buscar($nombre, $especie);
        return $this->tabla('MASCOTAS (id | nombre | especie | raza | dueño)', $filas, ['id', 'nombre', 'especie', 'raza', 'dueno']);
    }

    private function fichaMascota(array $args): string
    {
        $id = (int) ($args['id'] ?? 0);
        if ($id < 1) {
            return 'Error: falta el id de la mascota. Usá buscar_mascotas para obtenerlo.';
        }
        $modelo = new Mascota();
        $f = $modelo->buscarPorId($id);
        if (!$f) {
            return "Error: no existe una mascota con id $id.";
        }

        $texto = "MASCOTA (nombre | especie | raza | edad | peso | dueño | teléfono)\n"
            . implode(' | ', [$f['nombre'], $f['especie'], $f['raza'], edad($f['fecha_nacimiento']), $f['peso'] . ' kg', $f['dueno'], $f['telefono']]);
        $texto .= "\n" . $this->tabla('VACUNAS (vacuna | aplicada | próxima dosis | estado)', (new Vacuna())->deMascota($id), ['nombre_vacuna', 'fecha_aplicada', 'proxima_dosis', 'estado']);
        $texto .= "\n" . $this->tabla('HISTORIAL (fecha | motivo | veterinario | diagnóstico | tratamiento | observaciones)', $modelo->historial($id), ['fecha_hora', 'motivo', 'veterinario', 'diagnostico', 'tratamiento', 'observaciones']);
        return $texto;
    }

    private function turnosProximos(array $args): string
    {
        // Se valida en el servidor aunque la descripción diga "entre 0 y 30"
        $dias = max(0, min(30, (int) ($args['dias'] ?? 1)));
        $filas = (new Turno())->pendientesHasta($dias);
        return $this->tabla("TURNOS PENDIENTES HASTA DENTRO DE $dias DÍAS (fecha y hora | mascota | especie | motivo | veterinario)", $filas, ['fecha_hora', 'mascota', 'especie', 'motivo', 'veterinario']);
    }

    private function vacunasPendientes(array $args): string
    {
        $tipo = $args['tipo'] ?? '';
        if (!in_array($tipo, ['vencidas', 'por_vencer'], true)) {
            return "Error: tipo debe ser 'vencidas' o 'por_vencer'.";
        }
        $modelo = new Vacuna();
        $filas = $tipo === 'vencidas' ? $modelo->vencidas() : $modelo->porVencer();
        $titulo = $tipo === 'vencidas' ? 'VACUNAS VENCIDAS' : 'VACUNAS POR VENCER (30 días)';
        return $this->tabla("$titulo (mascota | vacuna | próxima dosis | dueño | teléfono)", $filas, ['mascota', 'nombre_vacuna', 'proxima_dosis', 'dueno', 'telefono']);
    }

    // Una línea por fila, con los campos separados por " | "
    private function tabla(string $cabecera, array $filas, array $campos): string
    {
        if (empty($filas)) {
            return "$cabecera\n(ninguno)";
        }
        $lineas = [$cabecera];
        foreach ($filas as $fila) {
            $lineas[] = implode(' | ', array_map(fn($c) => $fila[$c] ?? '-', $campos));
        }
        return implode("\n", $lineas);
    }

    // Arma la definición de una tool en el formato que espera la API
    private function tool(string $nombre, string $descripcion, array $propiedades, array $requeridos = []): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $nombre,
                'description' => $descripcion,
                'parameters' => [
                    'type' => 'object',
                    'properties' => $propiedades,
                    'required' => $requeridos,
                ],
            ],
        ];
    }
}