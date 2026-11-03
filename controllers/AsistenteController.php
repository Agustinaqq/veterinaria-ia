<?php
// Controlador del asistente: muestra el chat y atiende las consultas a la IA
class AsistenteController
{
    private const PROMPT = __DIR__ . '/../prompts/asistente_v1.txt';
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

        // Se sacan los < > para que nadie pueda cerrar las etiquetas <datos> o <pregunta>
        $pregunta = str_replace(['<', '>'], '', $pregunta);

        try {
            $contexto = (new ContextoAsistente())->construir($pregunta);

            $fecha = self::DIAS[(int) date('w')] . ' ' . date('d/m/Y');
            $sistema = str_replace(
                ['{{fecha}}', '{{datos}}'],
                [$fecha, $contexto['texto']],
                file_get_contents(self::PROMPT)
            );

            $resultado = (new IAService())->chat([
                ['role' => 'system', 'content' => $sistema],
                ['role' => 'user', 'content' => "<pregunta>\n$pregunta\n</pregunta>"],
            ], 'filtrado');

            $this->responder(200, [
                'respuesta' => trim($resultado['mensaje']['content'] ?? ''),
                'tokens' => $resultado['uso']['total_tokens'] ?? 0,
                'datos_enviados' => $contexto['secciones'],
            ]);
        } catch (RuntimeException $e) {
            // El detalle va al log de PHP; al usuario, un mensaje genérico
            error_log($e->getMessage());
            $this->responder(500, ['error' => 'No se pudo consultar a la IA. Probá de nuevo en un momento.']);
        }
    }

    private function responder(int $codigo, array $datos): never
    {
        http_response_code($codigo);
        echo json_encode($datos, JSON_UNESCAPED_UNICODE);
        exit;
    }
}