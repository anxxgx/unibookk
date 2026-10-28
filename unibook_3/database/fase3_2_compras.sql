-- ============================================================
-- UniBook — FASE 3.2: Compra simulada de libros
-- ------------------------------------------------------------
-- Ejecutar UNA sola vez sobre la base de datos "unibook":
--   1. Entra a phpMyAdmin.
--   2. Selecciona la base de datos "unibook" en el panel izquierdo.
--   3. Ve a la pestaña "SQL".
--   4. Pega todo este archivo.
--   5. Presiona "Continuar" / "Ejecutar".
--
-- No borra ni modifica ninguna tabla existente. No requiere que
-- vuelvas a tocar "libros" ni "usuarios": la columna
-- "estado_disponibilidad" (Fase 3.1) ya tiene los valores
-- 'disponible'/'reservado'/'vendido' que este flujo necesita.
-- ============================================================

CREATE TABLE IF NOT EXISTS compras (

    id                  INT AUTO_INCREMENT PRIMARY KEY,

    -- Mismo estilo de nombres que ya usa el proyecto
    -- (libros.usuario_id, no libros.id_usuario).
    libro_id            INT NOT NULL,
    comprador_id        INT NOT NULL,
    vendedor_id         INT NOT NULL,

    -- Copia del precio del libro al momento de comprar, para que
    -- el historial no cambie si el vendedor edita el precio después.
    precio              DECIMAL(10,2) NOT NULL,

    -- Solo para mostrar cuál "medio" simulado eligió el usuario.
    -- No se conecta con ninguna pasarela real.
    metodo_pago         ENUM('pse', 'nequi', 'bancolombia', 'daviplata') NOT NULL,

    -- Estados simples, tal como se pidió: sin pasos intermedios
    -- innecesarios. 'cancelada' queda reservado para uso futuro
    -- (no tiene botón en esta fase).
    estado              ENUM('pendiente', 'confirmada', 'rechazada', 'cancelada')
                         NOT NULL DEFAULT 'pendiente',

    fecha_compra        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME NULL,

    INDEX idx_compras_libro (libro_id),
    INDEX idx_compras_comprador (comprador_id),
    INDEX idx_compras_vendedor (vendedor_id)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Si la tabla ya fue creada con una versión anterior, alinea el estado
-- con el que usan controladores y vistas.
UPDATE compras c
INNER JOIN libros l ON l.id = c.libro_id
SET c.estado = 'confirmada'
WHERE c.estado IN ('vendida', '')
  AND l.estado_disponibilidad = 'vendido';

ALTER TABLE compras
    MODIFY estado ENUM('pendiente', 'confirmada', 'rechazada', 'cancelada')
    NOT NULL DEFAULT 'pendiente';
