# Indexador: lee los documentos de ../documentos, los parte en fragmentos,
# genera los embeddings y los guarda en la base vectorial (ChromaDB).
# Se corre una vez, y de nuevo cada vez que se agregan documentos:
#   .\venv\Scripts\python.exe indexar.py
from datetime import date
from pathlib import Path

import chromadb
from pypdf import PdfReader
from sentence_transformers import SentenceTransformer

CARPETA_DOCUMENTOS = Path(__file__).resolve().parent.parent / "documentos"
CARPETA_BASE = Path(__file__).resolve().parent / "base_vectorial"
MODELO = "all-MiniLM-L6-v2"   # el mismo de la práctica del curso; 384 dimensiones
COLECCION = "guias"

# El modelo lee como máximo 256 tokens por texto: un fragmento más largo se trunca
# sin aviso. 150 palabras entran cómodas. El solapamiento evita cortar un
# procedimiento justo en el medio (práctica 3, consigna 15).
PALABRAS_POR_FRAGMENTO = 150
SOLAPAMIENTO = 25


def extraer_paginas(ruta: Path) -> list[tuple[int, str]]:
    """Devuelve (número de página, texto) por cada página. Un .txt o .md cuenta como una sola página."""
    if ruta.suffix.lower() == ".pdf":
        lector = PdfReader(str(ruta))
        return [(numero + 1, pagina.extract_text() or "") for numero, pagina in enumerate(lector.pages)]
    return [(1, ruta.read_text(encoding="utf-8"))]


def limpiar(texto: str) -> str:
    """Saca saltos de línea y espacios repetidos que deja la extracción del PDF."""
    return " ".join(texto.split())


def fragmentar(texto: str) -> list[str]:
    """Parte el texto en fragmentos de N palabras con solapamiento."""
    palabras = texto.split()
    fragmentos = []
    inicio = 0
    while inicio < len(palabras):
        fin = inicio + PALABRAS_POR_FRAGMENTO
        fragmentos.append(" ".join(palabras[inicio:fin]))
        if fin >= len(palabras):
            break
        inicio = fin - SOLAPAMIENTO
    return fragmentos


def main() -> None:
    archivos = sorted(p for p in CARPETA_DOCUMENTOS.iterdir() if p.suffix.lower() in (".pdf", ".txt", ".md"))
    if not archivos:
        print(f"No hay documentos en {CARPETA_DOCUMENTOS}")
        return

    print(f"Cargando el modelo {MODELO} (la primera vez lo descarga)...")
    modelo = SentenceTransformer(MODELO)

    cliente = chromadb.PersistentClient(path=str(CARPETA_BASE))
    # Se borra y se vuelve a crear la colección para no duplicar fragmentos al reindexar
    try:
        cliente.delete_collection(COLECCION)
    except Exception:
        pass
    coleccion = cliente.create_collection(COLECCION, metadata={"hnsw:space": "cosine"})

    ids, textos, metadatos = [], [], []
    for archivo in archivos:
        cantidad = 0
        for pagina, texto in extraer_paginas(archivo):
            texto = limpiar(texto)
            if len(texto) < 50:  # páginas vacías o que son solo imágenes
                continue
            for numero, fragmento in enumerate(fragmentar(texto)):
                ids.append(f"{archivo.stem}-p{pagina}-f{numero}")
                textos.append(fragmento)
                # Los metadatos permiten citar documento y página (práctica 4)
                metadatos.append({
                    "documento": archivo.name,
                    "pagina": pagina,
                    "fragmento": numero,
                    "modelo": MODELO,
                    "indexado": date.today().isoformat(),
                })
                cantidad += 1
        print(f"  {archivo.name}: {cantidad} fragmentos")

    print(f"Generando {len(textos)} embeddings...")
    vectores = modelo.encode(textos, show_progress_bar=True).tolist()
    coleccion.add(ids=ids, documents=textos, embeddings=vectores, metadatas=metadatos)

    print(f"Listo: {len(textos)} fragmentos de {len(archivos)} documentos guardados en {CARPETA_BASE}")
    print(f"Dimensión del embedding: {len(vectores[0])}")


if __name__ == "__main__":
    main()