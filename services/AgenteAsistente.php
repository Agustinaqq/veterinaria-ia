<?php
// Versión 2: loop del agente. El modelo decide qué tools llamar; este código las ejecuta
// y le devuelve el resultado, hasta que el modelo responde con texto.
class AgenteAsistente
{
    private const MAX_PASOS = 5;
    private const PROMPT = __DIR__ . '/../prompts/asistente_v2.txt';
    private const DIAS = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];

    public function responder(string $pregunta): array
    {
        $herramientas = new HerramientasAsistente();
        $ia = new IAService();

        $fecha = self::DIAS[(int) date('w')] . ' ' . date('d/m/Y');
        $mensajes = [
            ['role' => 'system', 'content' => str_replace('{{fecha}}', $fecha, file_get_contents(self::PROMPT))],
            ['role' => 'user', 'content' => "<pregunta>\n$pregunta\n</pregunta>"],
        ];

        $tokens = 0;
        $llamadas = [];

        // Un for con límite, no un while(true): si el modelo entra en un bucle, se corta
        for ($paso = 1; $paso <= self::MAX_PASOS; $paso++) {
            $resultado = $ia->chat($mensajes, 'tools', $herramientas->definiciones());
            $tokens += $resultado['uso']['total_tokens'] ?? 0;
            $mensaje = $resultado['mensaje'];
            $toolCalls = $mensaje['tool_calls'] ?? [];

            // Si no pide ninguna tool, el modelo terminó: esta es la respuesta final
            if (empty($toolCalls)) {
                return [
                    'respuesta' => trim($mensaje['content'] ?? ''),
                    'tokens' => $tokens,
                    'pasos' => $paso,
                    'llamadas' => $llamadas,
                ];
            }

            // Se agrega al historial el pedido del modelo, tal como lo mandó
            $mensajes[] = [
                'role' => 'assistant',
                'content' => $mensaje['content'] ?? '',
                'tool_calls' => $toolCalls,
            ];

            // Se ejecuta cada tool pedida y se devuelve el resultado con el id del pedido
            foreach ($toolCalls as $call) {
                $nombre = $call['function']['name'] ?? '';
                $args = json_decode($call['function']['arguments'] ?? '{}', true) ?: [];
                $salida = $herramientas->ejecutar($nombre, $args);
                $llamadas[] = $nombre;

                $mensajes[] = [
                    'role' => 'tool',
                    'tool_call_id' => $call['id'],
                    'name' => $nombre,
                    'content' => $salida,
                ];
            }
        }

        return [
            'respuesta' => 'No pude completar la consulta: se alcanzó el límite de pasos.',
            'tokens' => $tokens,
            'pasos' => self::MAX_PASOS,
            'llamadas' => $llamadas,
        ];
    }
}