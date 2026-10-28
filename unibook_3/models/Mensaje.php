<?php

class Mensaje
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    public function enviar($remitente_id, $destinatario_id, $libro_id, $mensaje)
    {
        $sql = "INSERT INTO mensajes
                (remitente_id, destinatario_id, libro_id, mensaje)
                VALUES (?, ?, ?, ?)";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param(
            "iiis",
            $remitente_id,
            $destinatario_id,
            $libro_id,
            $mensaje
        );

        $resultado = $stmt->execute();

        $stmt->close();

        return $resultado;
    }

    public function obtenerConversacion(
        $usuario_id,
        $otro_usuario_id,
        $libro_id
    ) {
        $sql = "SELECT
                    m.id,
                    m.remitente_id,
                    m.destinatario_id,
                    m.libro_id,
                    m.mensaje,
                    m.leido,
                    m.fecha_creacion,
                    remitente.nombre AS nombre_remitente,
                    destinatario.nombre AS nombre_destinatario
                FROM mensajes m
                INNER JOIN usuarios remitente
                    ON remitente.id = m.remitente_id
                INNER JOIN usuarios destinatario
                    ON destinatario.id = m.destinatario_id
                WHERE m.libro_id = ?
                AND (
                    (m.remitente_id = ? AND m.destinatario_id = ?)
                    OR
                    (m.remitente_id = ? AND m.destinatario_id = ?)
                )
                ORDER BY m.fecha_creacion ASC";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            return [];
        }

        $stmt->bind_param(
            "iiiii",
            $libro_id,
            $usuario_id,
            $otro_usuario_id,
            $otro_usuario_id,
            $usuario_id
        );

        $stmt->execute();

        $resultado = $stmt->get_result();

        $mensajes = [];

        while ($fila = $resultado->fetch_assoc()) {
            $mensajes[] = $fila;
        }

        $stmt->close();

        return $mensajes;
    }

    /*
     * Busca con quién tiene conversación el usuario
     * respecto a un libro.
     *
     * Sirve tanto para el comprador como para el vendedor.
     */
    public function obtenerOtroUsuario($usuario_id, $libro_id)
    {
        $sql = "SELECT
                    CASE
                        WHEN remitente_id = ?
                        THEN destinatario_id
                        ELSE remitente_id
                    END AS otro_usuario_id
                FROM mensajes
                WHERE libro_id = ?
                AND (
                    remitente_id = ?
                    OR destinatario_id = ?
                )
                ORDER BY fecha_creacion DESC
                LIMIT 1";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            return 0;
        }

        $stmt->bind_param(
            "iiii",
            $usuario_id,
            $libro_id,
            $usuario_id,
            $usuario_id
        );

        $stmt->execute();

        $resultado = $stmt->get_result();

        $fila = $resultado->fetch_assoc();

        $stmt->close();

        if (!$fila) {
            return 0;
        }

        return (int)$fila['otro_usuario_id'];
    }

    public function obtenerConversaciones($usuario_id)
    {
        $sql = "SELECT
                    m.libro_id,
                    l.titulo,
                    l.imagen,

                    CASE
                        WHEN m.remitente_id = ?
                        THEN m.destinatario_id
                        ELSE m.remitente_id
                    END AS otro_usuario_id,

                    CASE
                        WHEN m.remitente_id = ?
                        THEN destinatario.nombre
                        ELSE remitente.nombre
                    END AS otro_usuario,

                    m.mensaje AS ultimo_mensaje,
                    m.fecha_creacion,

                    (
                        SELECT COUNT(*)
                        FROM mensajes m2
                        WHERE m2.libro_id = m.libro_id
                        AND (
                            (
                                m2.remitente_id = m.remitente_id
                                AND
                                m2.destinatario_id = m.destinatario_id
                            )
                            OR
                            (
                                m2.remitente_id = m.destinatario_id
                                AND
                                m2.destinatario_id = m.remitente_id
                            )
                        )
                        AND m2.destinatario_id = ?
                        AND m2.leido = 0
                    ) AS no_leidos

                FROM mensajes m

                INNER JOIN libros l
                    ON l.id = m.libro_id

                INNER JOIN usuarios remitente
                    ON remitente.id = m.remitente_id

                INNER JOIN usuarios destinatario
                    ON destinatario.id = m.destinatario_id

                WHERE m.id = (
                    SELECT MAX(m3.id)
                    FROM mensajes m3
                    WHERE m3.libro_id = m.libro_id
                    AND (
                        (
                            m3.remitente_id = m.remitente_id
                            AND
                            m3.destinatario_id = m.destinatario_id
                        )
                        OR
                        (
                            m3.remitente_id = m.destinatario_id
                            AND
                            m3.destinatario_id = m.remitente_id
                        )
                    )
                )

                AND (
                    m.remitente_id = ?
                    OR
                    m.destinatario_id = ?
                )

                ORDER BY m.fecha_creacion DESC";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            return [];
        }

        $stmt->bind_param(
            "iiiii",
            $usuario_id,
            $usuario_id,
            $usuario_id,
            $usuario_id,
            $usuario_id
        );

        $stmt->execute();

        $resultado = $stmt->get_result();

        $conversaciones = [];

        while ($fila = $resultado->fetch_assoc()) {
            $conversaciones[] = $fila;
        }

        $stmt->close();

        return $conversaciones;
    }

    public function marcarLeidos(
        $usuario_id,
        $otro_usuario_id,
        $libro_id
    ) {
        $sql = "UPDATE mensajes
                SET leido = 1
                WHERE destinatario_id = ?
                AND remitente_id = ?
                AND libro_id = ?
                AND leido = 0";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param(
            "iii",
            $usuario_id,
            $otro_usuario_id,
            $libro_id
        );

        $resultado = $stmt->execute();

        $stmt->close();

        return $resultado;
    }

    public function contarNoLeidos($usuario_id)
    {
        $sql = "SELECT COUNT(*) AS total
                FROM mensajes
                WHERE destinatario_id = ?
                AND leido = 0";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            return 0;
        }

        $stmt->bind_param("i", $usuario_id);

        $stmt->execute();

        $resultado = $stmt->get_result();

        $fila = $resultado->fetch_assoc();

        $stmt->close();

        return (int)$fila['total'];
    }
}