<?php

class Intercambio
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }


    // ============================================================
    // CREAR SOLICITUD DE INTERCAMBIO
    // ============================================================

    public function crear(
        $libro_solicitado_id,
        $libro_ofrecido_id,
        $solicitante_id,
        $propietario_id
    ) {
        $sql = "INSERT INTO intercambios
                (
                    libro_solicitado_id,
                    libro_ofrecido_id,
                    solicitante_id,
                    propietario_id
                )
                VALUES (?, ?, ?, ?)";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param(
            "iiii",
            $libro_solicitado_id,
            $libro_ofrecido_id,
            $solicitante_id,
            $propietario_id
        );

        $ok = $stmt->execute();

        $id = $this->conexion->insert_id;

        $stmt->close();

        return $ok ? $id : false;
    }


    // ============================================================
    // OBTENER UN INTERCAMBIO POR ID
    // ============================================================

    public function obtenerPorId($intercambio_id)
    {
        $sql = "SELECT
                    i.*,
                    ls.titulo AS libro_solicitado,
                    lo.titulo AS libro_ofrecido
                FROM intercambios i
                INNER JOIN libros ls
                    ON ls.id = i.libro_solicitado_id
                INNER JOIN libros lo
                    ON lo.id = i.libro_ofrecido_id
                WHERE i.id = ?";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            return null;
        }

        $stmt->bind_param(
            "i",
            $intercambio_id
        );

        $stmt->execute();

        $resultado = $stmt->get_result();

        $datos = $resultado->fetch_assoc();

        $stmt->close();

        return $datos;
    }


    // ============================================================
    // INTERCAMBIOS RECIBIDOS
    // ============================================================

    public function obtenerRecibidos($usuario_id)
    {
        $sql = "SELECT
                    i.*,
                    ls.titulo AS libro_solicitado,
                    lo.titulo AS libro_ofrecido,
                    u.nombre,
                    u.apellido
                FROM intercambios i
                INNER JOIN libros ls
                    ON ls.id = i.libro_solicitado_id
                INNER JOIN libros lo
                    ON lo.id = i.libro_ofrecido_id
                INNER JOIN usuarios u
                    ON u.id = i.solicitante_id
                WHERE i.propietario_id = ?
                ORDER BY i.fecha_creacion DESC";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            return [];
        }

        $stmt->bind_param(
            "i",
            $usuario_id
        );

        $stmt->execute();

        $resultado = $stmt->get_result();

        $datos = [];

        while ($fila = $resultado->fetch_assoc()) {
            $datos[] = $fila;
        }

        $stmt->close();

        return $datos;
    }


    // ============================================================
    // INTERCAMBIOS ENVIADOS
    // ============================================================

    public function obtenerEnviados($usuario_id)
    {
        $sql = "SELECT
                    i.*,
                    ls.titulo AS libro_solicitado,
                    lo.titulo AS libro_ofrecido
                FROM intercambios i
                INNER JOIN libros ls
                    ON ls.id = i.libro_solicitado_id
                INNER JOIN libros lo
                    ON lo.id = i.libro_ofrecido_id
                WHERE i.solicitante_id = ?
                ORDER BY i.fecha_creacion DESC";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            return [];
        }

        $stmt->bind_param(
            "i",
            $usuario_id
        );

        $stmt->execute();

        $resultado = $stmt->get_result();

        $datos = [];

        while ($fila = $resultado->fetch_assoc()) {
            $datos[] = $fila;
        }

        $stmt->close();

        return $datos;
    }


    // ============================================================
    // ACEPTAR INTERCAMBIO
    // ============================================================

    public function aceptar(
        $intercambio_id,
        $propietario_id
    ) {
        $sql = "UPDATE intercambios
                SET estado = 'aceptado'
                WHERE id = ?
                AND propietario_id = ?
                AND estado = 'pendiente'";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param(
            "ii",
            $intercambio_id,
            $propietario_id
        );

        $ok = $stmt->execute();

        $filasAfectadas = $stmt->affected_rows;

        $stmt->close();

        return $ok && $filasAfectadas > 0;
    }


    // ============================================================
    // RECHAZAR INTERCAMBIO
    // ============================================================

    public function rechazar(
        $intercambio_id,
        $propietario_id
    ) {
        $sql = "UPDATE intercambios
                SET estado = 'rechazado'
                WHERE id = ?
                AND propietario_id = ?
                AND estado = 'pendiente'";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param(
            "ii",
            $intercambio_id,
            $propietario_id
        );

        $ok = $stmt->execute();

        $filasAfectadas = $stmt->affected_rows;

        $stmt->close();

        return $ok && $filasAfectadas > 0;
    }
}