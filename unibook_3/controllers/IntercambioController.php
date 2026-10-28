<?php

require_once "models/Intercambio.php";
require_once "models/Libro.php";
require_once "models/Notificacion.php";

class IntercambioController
{
    private $modelo;
    private $libroModelo;
    private $notificacionModelo;
    private $conexion;


    // ============================================================
    // CONSTRUCTOR
    // ============================================================

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
        $this->modelo = new Intercambio($conexion);
        $this->libroModelo = new Libro($conexion);
        $this->notificacionModelo = new Notificacion($conexion);
    }


    // ============================================================
    // LIBROS DEL USUARIO PARA OFRECER
    // ============================================================

    public function obtenerMisLibros($usuario_id)
    {
        return $this->libroModelo->obtenerPorUsuario($usuario_id);
    }


    // ============================================================
    // CREAR SOLICITUD DE INTERCAMBIO
    // ============================================================

    public function crearSolicitud()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['id_usuario'])) {
            return false;
        }

        $solicitante_id = (int)$_SESSION['id_usuario'];

        $libro_solicitado_id =
            (int)($_POST['libro_solicitado_id'] ?? 0);

        $libro_ofrecido_id =
            (int)($_POST['libro_ofrecido_id'] ?? 0);


        if (
            $libro_solicitado_id <= 0 ||
            $libro_ofrecido_id <= 0 ||
            $libro_solicitado_id === $libro_ofrecido_id
        ) {
            return false;
        }


        // Obtener libro que la persona quiere recibir
        $libroSolicitado =
            $this->libroModelo->obtenerPorId(
                $libro_solicitado_id
            );


        // Obtener libro que la persona ofrece
        $libroOfrecido =
            $this->libroModelo->obtenerPorId(
                $libro_ofrecido_id
            );


        if (!$libroSolicitado || !$libroOfrecido) {
            return false;
        }


        $propietario_id =
            (int)$libroSolicitado['usuario_id'];


        // Evitar solicitarle un intercambio a uno mismo
        if ($propietario_id === $solicitante_id) {
            return false;
        }


        // Verificar que el libro ofrecido realmente pertenezca al usuario
        if (
            (int)$libroOfrecido['usuario_id'] !== $solicitante_id
        ) {
            return false;
        }


        // Ambos libros deben seguir disponibles.
        if (
            ($libroSolicitado['estado_disponibilidad'] ?? 'disponible')
            !== 'disponible'
            ||
            ($libroOfrecido['estado_disponibilidad'] ?? 'disponible')
            !== 'disponible'
        ) {
            return false;
        }


        // ========================================================
        // RESERVAR LOS DOS LIBROS + CREAR SOLICITUD
        // ========================================================

        $this->conexion->begin_transaction();

        try {

            // Reservar el libro solicitado.
            if (!$this->libroModelo->reservarSiDisponible($libro_solicitado_id)) {
                $this->conexion->rollback();
                return false;
            }


            // Reservar el libro que ofrece el solicitante.
            if (!$this->libroModelo->reservarSiDisponible($libro_ofrecido_id)) {
                $this->conexion->rollback();
                return false;
            }


            // Crear solicitud de intercambio.
            $resultado = $this->modelo->crear(
                $libro_solicitado_id,
                $libro_ofrecido_id,
                $solicitante_id,
                $propietario_id
            );


            if (!$resultado) {
                $this->conexion->rollback();
                return false;
            }


            $this->conexion->commit();

        } catch (Throwable $e) {

            $this->conexion->rollback();
            return false;
        }


        // ========================================================
        // NOTIFICAR AL PROPIETARIO DEL LIBRO
        // ========================================================

        $tituloLibro =
            $libroSolicitado['titulo'] ?? 'un libro';


        $this->notificacionModelo->crear(
            $propietario_id,
            'intercambio',
            'Nueva solicitud de intercambio',
            'Alguien quiere intercambiar su libro por "' .
            $tituloLibro .
            '". Revisa tus solicitudes de intercambio.',
            'index.php?accion=intercambios_recibidos'
        );


        return true;
    }


    // ============================================================
    // SOLICITUDES RECIBIDAS
    // ============================================================

    public function recibidos()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['id_usuario'])) {
            return [];
        }

        return $this->modelo->obtenerRecibidos(
            (int)$_SESSION['id_usuario']
        );
    }


    // ============================================================
    // SOLICITUDES ENVIADAS
    // ============================================================

    public function enviados()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['id_usuario'])) {
            return [];
        }

        return $this->modelo->obtenerEnviados(
            (int)$_SESSION['id_usuario']
        );
    }


    // ============================================================
    // ACEPTAR SOLICITUD
    // ============================================================

    public function aceptar()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['id_usuario'])) {
            return false;
        }

        $propietario_id =
            (int)$_SESSION['id_usuario'];

        $intercambio_id =
            (int)($_POST['intercambio_id'] ?? 0);


        if ($intercambio_id <= 0) {
            return false;
        }


        // Obtener información del intercambio
        $intercambio =
            $this->modelo->obtenerPorId(
                $intercambio_id
            );


        if (!$intercambio) {
            return false;
        }


        // Verificar que quien acepta sea el propietario
        if (
            (int)$intercambio['propietario_id']
            !== $propietario_id
        ) {
            return false;
        }


        // Verificar que todavía esté pendiente
        if (
            ($intercambio['estado'] ?? '') !== 'pendiente'
        ) {
            return false;
        }


        $libroSolicitadoId =
            (int)$intercambio['libro_solicitado_id'];

        $libroOfrecidoId =
            (int)$intercambio['libro_ofrecido_id'];


        // ========================================================
        // ACEPTAR + CAMBIAR LOS DOS LIBROS EN UNA TRANSACCIÓN
        // ========================================================

        $this->conexion->begin_transaction();

        try {

            // Ambos libros deben continuar reservados.
            if (!$this->libroModelo->estanReservadosParaIntercambio(
                $libroSolicitadoId,
                $libroOfrecidoId
            )) {
                $this->conexion->rollback();
                return false;
            }


            // Cambiar solicitud a aceptada.
            if (!$this->modelo->aceptar(
                $intercambio_id,
                $propietario_id
            )) {
                $this->conexion->rollback();
                return false;
            }


            // Los dos libros pasan a Historia.
            if (!$this->libroModelo->marcarIntercambiadoSiReservado(
                $libroSolicitadoId
            )) {
                $this->conexion->rollback();
                return false;
            }


            if (!$this->libroModelo->marcarIntercambiadoSiReservado(
                $libroOfrecidoId
            )) {
                $this->conexion->rollback();
                return false;
            }


            $this->conexion->commit();

        } catch (Throwable $e) {

            $this->conexion->rollback();
            return false;
        }


        // ========================================================
        // NOTIFICAR AL SOLICITANTE
        // ========================================================

        $this->notificacionModelo->crear(
            (int)$intercambio['solicitante_id'],
            'intercambio',
            '¡Intercambio aceptado!',
            'Tu solicitud de intercambio fue aceptada. Los dos libros ahora forman parte del intercambio.',
            'index.php?accion=intercambios_enviados'
        );


        return true;
    }


    // ============================================================
    // RECHAZAR SOLICITUD
    // ============================================================

    public function rechazar()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['id_usuario'])) {
            return false;
        }

        $propietario_id =
            (int)$_SESSION['id_usuario'];

        $intercambio_id =
            (int)($_POST['intercambio_id'] ?? 0);


        if ($intercambio_id <= 0) {
            return false;
        }


        // Obtener información del intercambio
        $intercambio =
            $this->modelo->obtenerPorId(
                $intercambio_id
            );


        if (!$intercambio) {
            return false;
        }


        // Verificar propietario
        if (
            (int)$intercambio['propietario_id']
            !== $propietario_id
        ) {
            return false;
        }


        // Solo se puede rechazar una solicitud pendiente.
        if (
            ($intercambio['estado'] ?? '') !== 'pendiente'
        ) {
            return false;
        }


        $libroSolicitadoId =
            (int)$intercambio['libro_solicitado_id'];

        $libroOfrecidoId =
            (int)$intercambio['libro_ofrecido_id'];


        // ========================================================
        // RECHAZAR + LIBERAR LOS DOS LIBROS
        // ========================================================

        $this->conexion->begin_transaction();

        try {

            if (!$this->modelo->rechazar(
                $intercambio_id,
                $propietario_id
            )) {
                $this->conexion->rollback();
                return false;
            }


            // Los dos libros vuelven a estar disponibles.
            if (!$this->libroModelo->marcarDisponibleSiReservado(
                $libroSolicitadoId
            )) {
                $this->conexion->rollback();
                return false;
            }


            if (!$this->libroModelo->marcarDisponibleSiReservado(
                $libroOfrecidoId
            )) {
                $this->conexion->rollback();
                return false;
            }


            $this->conexion->commit();

        } catch (Throwable $e) {

            $this->conexion->rollback();
            return false;
        }


        // ========================================================
        // NOTIFICAR AL SOLICITANTE
        // ========================================================

        $this->notificacionModelo->crear(
            (int)$intercambio['solicitante_id'],
            'intercambio',
            'Solicitud de intercambio rechazada',
            'La persona propietaria del libro rechazó tu solicitud de intercambio.',
            'index.php?accion=intercambios_enviados'
        );


        return true;
    }
}