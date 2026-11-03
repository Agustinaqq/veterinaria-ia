<?php
// Zona horaria de Argentina, para que PHP y SQLite usen el mismo día
date_default_timezone_set('America/Argentina/Buenos_Aires');
// Conexión a SQLite. La base y las tablas se crean solas la primera vez.
function obtenerConexion(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $ruta = __DIR__ . '/../veterinaria.db';
        $pdo = new PDO('sqlite:' . $ruta);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        // SQLite no controla las claves foráneas si no se activa esto
        $pdo->exec('PRAGMA foreign_keys = ON');
        crearTablas($pdo);
    }

    return $pdo;
}

function crearTablas(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS duenos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre TEXT NOT NULL,
            telefono TEXT,
            email TEXT
        );

        CREATE TABLE IF NOT EXISTS mascotas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            dueno_id INTEGER NOT NULL REFERENCES duenos(id),
            nombre TEXT NOT NULL,
            especie TEXT NOT NULL,
            raza TEXT,
            fecha_nacimiento TEXT,
            peso REAL
        );

        CREATE TABLE IF NOT EXISTS veterinarios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre TEXT NOT NULL,
            especialidad TEXT
        );

        -- Un veterinario no puede tener dos turnos a la misma hora:
        -- lo garantiza la base, no la IA
        CREATE TABLE IF NOT EXISTS turnos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            mascota_id INTEGER NOT NULL REFERENCES mascotas(id),
            veterinario_id INTEGER NOT NULL REFERENCES veterinarios(id),
            fecha_hora TEXT NOT NULL,
            motivo TEXT,
            estado TEXT NOT NULL DEFAULT 'pendiente'
                CHECK (estado IN ('pendiente', 'realizado', 'cancelado')),
            UNIQUE (veterinario_id, fecha_hora)
        );

        CREATE TABLE IF NOT EXISTS consultas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            turno_id INTEGER NOT NULL UNIQUE REFERENCES turnos(id),
            diagnostico TEXT,
            tratamiento TEXT,
            observaciones TEXT
        );

        CREATE TABLE IF NOT EXISTS vacunas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            mascota_id INTEGER NOT NULL REFERENCES mascotas(id),
            nombre_vacuna TEXT NOT NULL,
            fecha_aplicada TEXT NOT NULL,
            proxima_dosis TEXT
        );
    ");
}