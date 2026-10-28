-- ============================================================
-- UniBook — FASE 4: Rediseño de "Mi perfil"
-- ------------------------------------------------------------
-- Ejecutar UNA sola vez sobre la base de datos "unibook":
--   1. Entra a phpMyAdmin.
--   2. Selecciona la base de datos "unibook" en el panel izquierdo.
--   3. Ve a la pestaña "SQL".
--   4. Pega todo este archivo.
--   5. Presiona "Continuar" / "Ejecutar".
--
-- Qué hace:
--   Agrega a la tabla "usuarios" cinco columnas OPCIONALES (NULL)
--   para guardar la foto de perfil, la biografía y los datos
--   académicos que el usuario complete en "Mi perfil".
--
-- Es una migración NO destructiva:
--   * No borra ni modifica ninguna columna ni fila existente.
--   * Todos los usuarios actuales quedan con estos campos vacíos
--     (NULL) hasta que ellos mismos los completen.
--   * No toca las tablas "libros", "compras", "intercambios",
--     "mensajes" ni "notificaciones".
--
-- Si alguna columna ya existe, MySQL mostrará el error
-- "Duplicate column name" para esa línea: es inofensivo, solo
-- significa que ya estaba aplicada. Ejecuta las demás por separado.
-- ============================================================

ALTER TABLE usuarios
    ADD COLUMN foto_perfil VARCHAR(255)  NULL DEFAULT NULL,
    ADD COLUMN biografia   VARCHAR(500)  NULL DEFAULT NULL,
    ADD COLUMN universidad VARCHAR(150)  NULL DEFAULT NULL,
    ADD COLUMN carrera     VARCHAR(150)  NULL DEFAULT NULL,
    ADD COLUMN semestre    VARCHAR(20)   NULL DEFAULT NULL;
