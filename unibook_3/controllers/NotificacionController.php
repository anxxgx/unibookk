<?php

require_once "models/Notificacion.php";

class NotificacionController
{
    private $modelo;

    public function __construct($conexion)
    {
        $this->modelo = new Notificacion($conexion);
    }

    // ============================================================
    // MOSTRAR NOTIFICACIONES
    // ============================================================

    public function listar()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['id_usuario'])) {
            return [];
        }

        return $this->modelo->obtenerPorUsuario(
            (int)$_SESSION['id_usuario']
        );
    }

    // ============================================================
    // CONTAR NOTIFICACIONES NO LEÍDAS
    // ============================================================

    public function contarNoLeidas()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['id_usuario'])) {
            return 0;
        }

        return $this->modelo->contarNoLeidas(
            (int)$_SESSION['id_usuario']
        );
    }

    // ============================================================
    // MARCAR UNA NOTIFICACIÓN COMO LEÍDA
    // ============================================================

    public function marcarLeida($id = 0)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['id_usuario'])) {
            return false;
        }

        if ($id <= 0) {
            $id = (int)($_POST['notificacion_id'] ?? 0);
        }

        if ($id <= 0) {
            return false;
        }

        return $this->modelo->marcarLeida(
            $id,
            (int)$_SESSION['id_usuario']
        );
    }

    // ============================================================
    // MARCAR TODAS COMO LEÍDAS
    // ============================================================

    public function marcarTodasLeidas()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['id_usuario'])) {
            return false;
        }

        return $this->modelo->marcarTodasLeidas(
            (int)$_SESSION['id_usuario']
        );
    }
}