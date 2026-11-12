<?php
// Controlador del asistente: muestra el chat y atiende las consultas a la IA
class AsistenteController
{
    private const PROMPT_V1 = __DIR__ . '/../prompts/asistente_v1.txt';
    private const DIAS = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];

    public function chat(): void
    {
        $titulo = 'Asistente';
        require __DIR__ . '/../views/layouts/header.php';
        require __DIR__ . '/../views/asistente/chat.php';
        require __DIR__ . '/../views/layouts/footer.php';
    }

    // Recibe la pregunta por fetch (JSON) y devuelve la respuesta en JSON
    public function consultar(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responder(405, ['error' => 'Método no permitido.']);
        }

        // Validación del lado del servidor
        $entrada = json_decode(file_get_contents('php://input'), true);
        $pregunta = trim((string) ($entrada['pregunta'] ?? ''));
        $largo = mb_strlen($pregunta);
        if ($largo < 1 || $largo > 1000) {
            $this->responder(422, ['error' => 'La pregunta tiene que tener entre 1 y 1000 caracteres.']);
        }
        $version = ($entrada['version'] ?? 'filtrado') === 'tools' ? 'tools' : 'filtrado';

        // Se sacan los < > para que nadie pueda cerrar las etiquetas <datos> o <pregunta>
        $pregunta = str_replace(['<', '>'], '', $pregunta);

        try {
            $salida = $version === 'tools'
                ? $this->conFunciones($pregunta)
                : $this->conFiltrado($pregunta);
            $this->responder(200, $salida);
        } catch (RuntimeException $e) {
            // El detalle va al log de PHP; al usuario, un mensaje genérico
            error_log($e->getMessage());
            $this->responder(500, ['error' => 'No se pudo consultar a la IA. Probá de nuevo en un momento.']);
        }
    }

    // Versión 1: el código elige los datos y se los manda en el prompt
    private function conFiltrado(string $pregunta): array
    {
        $contexto = (new ContextoAsistente())->construir($pregunta);
        $fecha = self::DIAS[(int) date('w')] . ' ' . date('d/m/Y');
        $sistema = str_replace(['{{fecha}}', '{{datos}}'], [$fecha, $contexto['texto']], file_get_contents(self::PROMPT_V1));

        $resultado = (new IAService())->chat([
            ['role' => 'system', 'content' => $sistema],
            ['role' => 'user', 'content' => "<pregunta>\n$pregunta\n</pregunta>"],
        ], 'filtrado');

        return [
            'respuesta' => trim($resultado['mensaje']['content'] ?? ''),
            'tokens' => $resultado['uso']['total_tokens'] ?? 0,
            'detalle' => 'v1 filtrado · 1 llamada · datos: ' . implode(', ', $contexto['secciones']),
        ];
    }

    // Versión 2: la IA pide las funciones que necesita
    private function conFunciones(string $pregunta): array
    {
        $r = (new AgenteAsistente())->responder($pregunta);
        $funciones = $r['llamadas'] ? implode(', ', $r['llamadas']) : 'ninguna';

        return [
            'respuesta' => $r['respuesta'],
            'tokens' => $r['tokens'],
            'detalle' => "v2 funciones · {$r['pasos']} llamada(s) · funciones: $funciones",
        ];
    }

    private function responder(int $codigo, array $datos): never
    {
        http_response_code($codigo);
        echo json_encode($datos, JSON_UNESCAPED_UNICODE);
        exit;
    }
}