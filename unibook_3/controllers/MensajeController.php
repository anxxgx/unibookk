<?php

require_once "models/Mensaje.php";
require_once "models/Libro.php";

class MensajeController
{
    private $modelo;
    private $libroModelo;

    public function __construct($conexion)
    {
        $this->modelo = new Mensaje($conexion);
        $this->libroModelo = new Libro($conexion);
    }

    public function abrirChat($libro_id, $otro_usuario_id = 0)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['id_usuario'])) {
            return null;
        }

        $usuario_id = (int)$_SESSION['id_usuario'];
        $libro_id = (int)$libro_id;
        $otro_usuario_id = (int)$otro_usuario_id;

        if ($libro_id <= 0) {
            return null;
        }

        $libro = $this->libroModelo->obtenerPorId($libro_id);

        if (!$libro) {
            return null;
        }

        $vendedor_id = (int)$libro['usuario_id'];

        /*
         * Si desde Mensajes ya viene el otro usuario,
         * usamos ese usuario.
         */
        if ($otro_usuario_id > 0) {

            /*
             * El otro usuario no puede ser el mismo usuario.
             */
            if ($otro_usuario_id === $usuario_id) {
                return null;
            }

            /*
             * Verificamos que realmente exista una conversación
             * entre ambos para este libro.
             */
            $mensajes = $this->modelo->obtenerConversacion(
                $usuario_id,
                $otro_usuario_id,
                $libro_id
            );

            /*
             * Si hay mensajes, es una conversación válida.
             */
            if (!empty($mensajes)) {

                $this->modelo->marcarLeidos(
                    $usuario_id,
                    $otro_usuario_id,
                    $libro_id
                );

                return [
                    'libro' => $libro,
                    'vendedor_id' => $vendedor_id,
                    'otro_usuario_id' => $otro_usuario_id,
                    'mensajes' => $mensajes
                ];
            }
        }

        /*
         * Si no vino el otro usuario, buscamos la conversación.
         */
        if ($usuario_id === $vendedor_id) {

            $otro_usuario_id =
                $this->modelo->obtenerOtroUsuario(
                    $usuario_id,
                    $libro_id
                );

            if ($otro_usuario_id <= 0) {
                return null;
            }

        } else {

            $otro_usuario_id = $vendedor_id;
        }

        $mensajes = $this->modelo->obtenerConversacion(
            $usuario_id,
            $otro_usuario_id,
            $libro_id
        );

        /*
         * Si no hay conversación todavía pero el usuario
         * es el comprador que viene desde el detalle,
         * igualmente permitimos abrir el chat.
         */
        if (
            empty($mensajes) &&
            $usuario_id !== $vendedor_id
        ) {
            return [
                'libro' => $libro,
                'vendedor_id' => $vendedor_id,
                'otro_usuario_id' => $vendedor_id,
                'mensajes' => []
            ];
        }

        if (empty($mensajes)) {
            return null;
        }

        $this->modelo->marcarLeidos(
            $usuario_id,
            $otro_usuario_id,
            $libro_id
        );

        return [
            'libro' => $libro,
            'vendedor_id' => $vendedor_id,
            'otro_usuario_id' => $otro_usuario_id,
            'mensajes' => $mensajes
        ];
    }

    public function enviar()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['id_usuario'])) {
            return false;
        }

        $remitente_id = (int)$_SESSION['id_usuario'];

        $destinatario_id =
            (int)($_POST['destinatario_id'] ?? 0);

        $libro_id =
            (int)($_POST['libro_id'] ?? 0);

        $mensaje =
            trim($_POST['mensaje'] ?? '');

        if (
            $destinatario_id <= 0 ||
            $libro_id <= 0 ||
            $mensaje === ''
        ) {
            return false;
        }

        if ($remitente_id === $destinatario_id) {
            return false;
        }

        $libro =
            $this->libroModelo->obtenerPorId($libro_id);

        if (!$libro) {
            return false;
        }

        $vendedor_id = (int)$libro['usuario_id'];

        /*
         * Si escribe el vendedor, solo puede responder
         * a alguien con quien ya existe conversación.
         */
        if ($remitente_id === $vendedor_id) {

            $otroUsuario =
                $this->modelo->obtenerOtroUsuario(
                    $remitente_id,
                    $libro_id
                );

            if (
                $otroUsuario <= 0 ||
                $otroUsuario !== $destinatario_id
            ) {
                return false;
            }

        } else {

            /*
             * Si escribe un comprador/interesado,
             * solamente puede escribirle al vendedor.
             */
            if ($destinatario_id !== $vendedor_id) {
                return false;
            }
        }

        return $this->modelo->enviar(
            $remitente_id,
            $destinatario_id,
            $libro_id,
            $mensaje
        );
    }

    public function conversaciones()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['id_usuario'])) {
            return [];
        }

        return $this->modelo->obtenerConversaciones(
            (int)$_SESSION['id_usuario']
        );
    }

    public function contarNoLeidos()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['id_usuario'])) {
            return 0;
        }

        return $this->modelo->contarNoLeidos(
            (int)$_SESSION['id_usuario']
        );
    }
}