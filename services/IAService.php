<?php
// Servicio que habla con la API de Groq y registra los tokens de cada llamada
class IAService
{
    private const URL = 'https://api.groq.com/openai/v1/chat/completions';
    private const MAX_INTENTOS = 3;
    private const LOG = __DIR__ . '/../logs/tokens.csv';

    private string $clave;
    private string $modelo;

    public function __construct()
    {
        $clave = env('GROQ_API_KEY');
        if (!$clave || $clave === 'pega_tu_clave_aca') {
            throw new RuntimeException('Falta GROQ_API_KEY en el archivo .env');
        }
        $this->clave = $clave;
        $this->modelo = env('GROQ_MODELO') ?? 'openai/gpt-oss-120b';
    }

    /**
     * Manda los mensajes al modelo y devuelve su respuesta.
     * $version identifica la estrategia (filtrado, tools...) para comparar en el log.
     * $tools queda vacío por ahora; lo usamos en la versión con function calling.
     */
    public function chat(array $mensajes, string $version, array $tools = []): array
    {
        $cuerpo = [
            'model' => $this->modelo,
            'messages' => $mensajes,
            'temperature' => 0.2, // baja: respuestas más estables y menos "creativas"
        ];
        if ($tools) {
            $cuerpo['tools'] = $tools;
            $cuerpo['tool_choice'] = 'auto';
        }

        $respuesta = $this->enviar($cuerpo);
        $uso = $respuesta['usage'] ?? [];
        $this->registrarTokens($version, $uso);

        return [
            'mensaje' => $respuesta['choices'][0]['message'],
            'uso' => $uso,
        ];
    }

    // Hace la petición HTTP con cURL. Si Groq está saturado (429) o falla (5xx),
    // espera y reintenta con espera creciente: 1s, 2s...
    private function enviar(array $cuerpo): array
    {
        for ($intento = 1; $intento <= self::MAX_INTENTOS; $intento++) {
            $ch = curl_init(self::URL);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->clave,
                ],
                CURLOPT_POSTFIELDS => json_encode($cuerpo, JSON_UNESCAPED_UNICODE),
            ]);

            $texto = curl_exec($ch);
            $codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $errorCurl = curl_error($ch);
            curl_close($ch);

            if ($texto === false) {
                throw new RuntimeException('No se pudo conectar con Groq: ' . $errorCurl);
            }
            if ($codigo === 200) {
                return json_decode($texto, true);
            }
            if (($codigo === 429 || $codigo >= 500) && $intento < self::MAX_INTENTOS) {
                sleep(2 ** ($intento - 1));
                continue;
            }
            throw new RuntimeException("Groq respondió con error $codigo: $texto");
        }

        throw new RuntimeException('Groq no respondió después de varios intentos.');
    }

    // Guarda una línea por llamada: fecha, versión y tokens usados
    private function registrarTokens(string $version, array $uso): void
    {
        $carpeta = dirname(self::LOG);
        if (!is_dir($carpeta)) {
            mkdir($carpeta, 0777, true);
        }
        $linea = sprintf(
            "%s,%s,%d,%d,%d\n",
            date('Y-m-d H:i:s'),
            $version,
            $uso['prompt_tokens'] ?? 0,
            $uso['completion_tokens'] ?? 0,
            $uso['total_tokens'] ?? 0
        );
        file_put_contents(self::LOG, $linea, FILE_APPEND);
    }
}