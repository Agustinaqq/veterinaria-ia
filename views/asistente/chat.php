<h1 class="h3 mb-3">Asistente</h1>
<p class="text-muted">Preguntá sobre mascotas, turnos, vacunas o historiales.</p>

<div id="conversacion" class="border rounded p-3 mb-3 bg-light" style="min-height: 300px;"></div>

<div class="input-group">
    <input id="pregunta" type="text" class="form-control" maxlength="1000"
           placeholder="Ej: ¿qué mascotas tienen vacunas vencidas?">
    <button id="enviar" class="btn btn-success">Enviar</button>
</div>

<script>
const conversacion = document.getElementById('conversacion');
const campo = document.getElementById('pregunta');
const boton = document.getElementById('enviar');

// Agrega un mensaje. Se usa textContent (no innerHTML) para que el texto
// de la IA nunca se interprete como HTML.
function agregarMensaje(texto, clase, detalle = '') {
    const burbuja = document.createElement('div');
    burbuja.className = 'p-2 rounded mb-2 ' + clase;
    burbuja.style.whiteSpace = 'pre-wrap';
    burbuja.textContent = texto;

    if (detalle) {
        const pie = document.createElement('div');
        pie.className = 'small text-muted mt-1';
        pie.textContent = detalle;
        burbuja.appendChild(pie);
    }
    conversacion.appendChild(burbuja);
    conversacion.scrollTop = conversacion.scrollHeight;
}

async function enviar() {
    const pregunta = campo.value.trim();
    if (!pregunta) return;

    agregarMensaje(pregunta, 'bg-success text-white ms-5');
    campo.value = '';
    boton.disabled = true;
    boton.textContent = 'Pensando...';

    try {
        const respuesta = await fetch('index.php?action=consultar_ia', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ pregunta }),
        });
        const datos = await respuesta.json();

        if (!respuesta.ok) {
            agregarMensaje(datos.error, 'bg-danger-subtle me-5');
        } else {
            const detalle = `Tokens: ${datos.tokens} · Datos enviados: ${datos.datos_enviados.join(', ')}`;
            agregarMensaje(datos.respuesta, 'bg-white border me-5', detalle);
        }
    } catch (error) {
        agregarMensaje('No se pudo conectar con el servidor.', 'bg-danger-subtle me-5');
    } finally {
        boton.disabled = false;
        boton.textContent = 'Enviar';
        campo.focus();
    }
}

boton.addEventListener('click', enviar);
campo.addEventListener('keydown', (e) => { if (e.key === 'Enter') enviar(); });
</script>