# API del servicio de IA: recibe una consulta y devuelve los fragmentos de documentos
# más parecidos, combinando búsqueda semántica (embeddings) y por palabras clave (BM25).
# Se levanta con:
#   .\venv\Scripts\python.exe -m uvicorn api:app --port 8001
import os
import re
import unicodedata
from pathlib import Path
import chromadb
import numpy as np
import re
from dotenv import load_dotenv
from fastapi import FastAPI, Header, HTTPException
from pydantic import BaseModel, Field
from rank_bm25 import BM25Okapi
from sentence_transformers import SentenceTransformer

CARPETA = Path(__file__).resolve().parent
load_dotenv(CARPETA / ".env")

TOKEN = os.getenv("SERVICIO_IA_TOKEN", "")
MODELO = "paraphrase-multilingual-MiniLM-L12-v2"   # multilingüe; 384 dimensiones
COLECCION = "guias"

# Umbral de relevancia semántica: por debajo, el fragmento no se devuelve (práctica 7, caso E)
SIMILITUD_MINIMA = 0.25
# Cuántos candidatos trae la búsqueda vectorial antes de reordenar con palabras clave
CANDIDATOS = 20
# Peso de la parte semántica en el puntaje final (el resto es palabras clave)
PESO_SEMANTICO = 0.5

app = FastAPI(title="Servicio de IA · Veterinaria")

# El modelo y la base se cargan una sola vez, al arrancar
modelo = SentenceTransformer(MODELO)
coleccion = chromadb.PersistentClient(path=str(CARPETA / "base_vectorial")).get_collection(COLECCION)


# Palabras que no aportan a la búsqueda por palabras clave
PALABRAS_VACIAS = {
    "a", "al", "ante", "con", "cual", "cuando", "de", "del", "el", "en", "es", "la", "las", "le", "les",
    "lo", "los", "me", "mi", "o", "para", "por", "que", "se", "si", "sin", "su", "sus", "te", "toca",
    "tu", "un", "una", "uno", "unos", "unas", "y", "ya",
}


def tokenizar(texto: str) -> list[str]:
    """Minúsculas, sin tildes, sin palabras vacías y sin plural: 'Cachorros' y 'cachorro' son lo mismo."""
    texto = unicodedata.normalize("NFKD", texto.lower())
    texto = "".join(c for c in texto if not unicodedata.combining(c))
    palabras = []
    for palabra in re.findall(r"[a-z0-9]+", texto):
        if palabra in PALABRAS_VACIAS:
            continue
        if len(palabra) > 4 and palabra.endswith("es") and not palabra.endswith("ses"):
            palabra = palabra[:-2]     # "refuerzos" no, pero "animales" → "animal"
        elif len(palabra) > 3 and palabra.endswith("s"):
            palabra = palabra[:-1]     # "cachorros" → "cachorro", "semanas" → "semana"
        palabras.append(palabra)
    return palabras

# Índice de palabras clave (BM25) sobre los mismos fragmentos, para la búsqueda híbrida
todos = coleccion.get(include=["documents"])
posicion_por_id = {id_: i for i, id_ in enumerate(todos["ids"])}
bm25 = BM25Okapi([tokenizar(t) for t in todos["documents"]])


class Consulta(BaseModel):
    consulta: str = Field(min_length=1, max_length=500)
    k: int = Field(default=5, ge=1, le=10)  # cuántos fragmentos devolver (top-k)


def verificar_token(x_token: str | None) -> None:
    if not TOKEN or x_token != TOKEN:
        raise HTTPException(status_code=401, detail="Token inválido")


@app.get("/salud")
def salud():
    return {"estado": "ok", "modelo": MODELO, "fragmentos": coleccion.count()}


@app.post("/buscar")
def buscar(datos: Consulta, x_token: str | None = Header(default=None)):
    verificar_token(x_token)

    # 1. Búsqueda semántica: los CANDIDATOS fragmentos más cercanos a la pregunta
    vector = modelo.encode(datos.consulta)
    semantico = coleccion.query(query_embeddings=[vector.tolist()], n_results=CANDIDATOS, include=[])
    ids_semantico = semantico["ids"][0]

    # 2. Búsqueda por palabras clave: los CANDIDATOS fragmentos con mejor BM25
    puntajes = bm25.get_scores(tokenizar(datos.consulta))
    maximo = max(float(puntajes.max()), 1e-9)
    mejores = sorted(range(len(puntajes)), key=lambda i: puntajes[i], reverse=True)[:CANDIDATOS]
    ids_palabras = [todos["ids"][i] for i in mejores]

    # 3. Unión de los dos conjuntos: un fragmento entra si lo encontró cualquiera de las dos
    ids = list(dict.fromkeys(ids_semantico + ids_palabras))
    detalle = coleccion.get(ids=ids, include=["documents", "metadatas", "embeddings"])

    # 4. A cada candidato se le calculan los dos puntajes y se combinan
    candidatos = []
    for id_, texto, meta, emb in zip(detalle["ids"], detalle["documents"], detalle["metadatas"], detalle["embeddings"]):
        emb = np.asarray(emb)
        semantica = float(vector @ emb / (np.linalg.norm(vector) * np.linalg.norm(emb) + 1e-9))
        palabras = float(puntajes[posicion_por_id[id_]]) / maximo
        candidatos.append({
            "documento": meta["documento"],
            "pagina": meta["pagina"],
            "similitud": round(semantica, 3),
            "palabras_clave": round(palabras, 3),
            "puntaje": round(PESO_SEMANTICO * semantica + (1 - PESO_SEMANTICO) * palabras, 3),
            "texto": texto,
        })
    candidatos.sort(key=lambda c: c["puntaje"], reverse=True)

    # 5. Se devuelven los k mejores que superan el umbral semántico
    fragmentos = [c for c in candidatos if c["similitud"] >= SIMILITUD_MINIMA][: datos.k]
    return {"consulta": datos.consulta, "fragmentos": fragmentos}