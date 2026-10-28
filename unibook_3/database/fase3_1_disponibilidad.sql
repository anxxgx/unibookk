-- ============================================================
-- UniBook — FASE 3.1: Disponibilidad de libros
-- ------------------------------------------------------------
-- Ejecutar UNA sola vez sobre la base de datos "unibook"
-- (phpMyAdmin > pestaña "SQL", o consola de MySQL).
--
-- Qué hace:
--   Agrega la columna "estado_disponibilidad" a la tabla
--   "libros". Es el semáforo que en las próximas fases decidirá
--   si un libro puede comprarse / solicitarse en intercambio.
--
-- No borra ni modifica ningún dato existente: todos los libros
-- que ya existen en la tabla quedan automáticamente en
-- 'disponible' (el valor por defecto).
-- ============================================================

ALTER TABLE libros
    ADD COLUMN estado_disponibilidad
        ENUM('disponible', 'reservado', 'vendido', 'intercambiado')
        NOT NULL DEFAULT 'disponible'
    AFTER estado;
