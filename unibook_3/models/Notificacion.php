<?php

class Notificacion
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    // ============================================================
    // CREAR NOTIFICACIÓN
    // ============================================================

    public function crear(
        $usuario_id,
        $tipo,
        $titulo,
        $mensaje,
        $enlace = null
    ) {
        $sql = "INSERT INTO notificaciones
                (
                    usuario_id,
                    tipo,
                    titulo,
                    mensaje,
                    enlace
                )
                VALUES (?, ?, ?, ?, ?)";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param(
            "issss",
            $usuario_id,
            $tipo,
            $titulo,
            $mensaje,
            $enlace
        );

        $ok = $stmt->execute();

        $id = $this->conexion->insert_id;

        $stmt->close();

        return $ok ? $id : false;
    }


    // ============================================================
    // OBTENER NOTIFICACIONES DE UN USUARIO
    // ============================================================

    public function obtenerPorUsuario($usuario_id)
    {
        $sql = "SELECT
                    id,
                    tipo,
                    titulo,
                    mensaje,
                    enlace,
                    leida,
                    fecha_creacion
                FROM notificaciones
                WHERE usuario_id = ?
                ORDER BY fecha_creacion DESC";

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

        $notificaciones = [];

        while ($fila = $resultado->fetch_assoc()) {
            $notificaciones[] = $fila;
        }

        $stmt->close();

        return $notificaciones;
    }


    // ============================================================
    // CONTAR NOTIFICACIONES NO LEÍDAS
    // ============================================================

    public function contarNoLeidas($usuario_id)
    {
        $sql = "SELECT COUNT(*) AS total
                FROM notificaciones
                WHERE usuario_id = ?
                AND leida = 0";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            return 0;
        }

        $stmt->bind_param(
            "i",
            $usuario_id
        );

        $stmt->execute();

        $resultado = $stmt->get_result();

        $fila = $resultado->fetch_assoc();

        $stmt->close();

        return (int)$fila['total'];
    }


    // ============================================================
    // MARCAR UNA NOTIFICACIÓN COMO LEÍDA
    // ============================================================

    public function marcarLeida(
        $id,
        $usuario_id
    ) {
        $sql = "UPDATE notificaciones
                SET leida = 1
                WHERE id = ?
                AND usuario_id = ?";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param(
            "ii",
            $id,
            $usuario_id
        );

        $ok = $stmt->execute();

        $stmt->close();

        return $ok;
    }


    // ============================================================
    // MARCAR TODAS COMO LEÍDAS
    // ============================================================

    public function marcarTodasLeidas($usuario_id)
    {
        $sql = "UPDATE notificaciones
                SET leida = 1
                WHERE usuario_id = ?
                AND leida = 0";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param(
            "i",
            $usuario_id
        );

        $ok = $stmt->execute();

        $stmt->close();

        return $ok;
    }
}