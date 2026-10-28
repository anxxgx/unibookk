<?php

require_once "models/Libro.php";

class LibroController {

    private $modelo;


    public function __construct($conexion){

        $this->modelo = new Libro($conexion);

    }


    public function publicar(){

        if(session_status() === PHP_SESSION_NONE){

            session_start();

        }

        if (!isset($_SESSION['id_usuario'])) {
            return false;
        }

        $camposRequeridos = ['titulo', 'autor', 'tipo_publicacion'];
        foreach ($camposRequeridos as $campo) {
            if (trim($_POST[$campo] ?? '') === '') {
                return false;
            }
        }

        $tipoPublicacion = trim($_POST['tipo_publicacion'] ?? '');
        $estado = trim($_POST['estado'] ?? '');
        $precio = filter_var($_POST['precio'] ?? null, FILTER_VALIDATE_FLOAT);

        if (
            !in_array($tipoPublicacion, ['Venta', 'Intercambio'], true) ||
            !in_array($estado, ['Nuevo', 'Usado'], true) ||
            $precio === false || $precio < 0
        ) {
            return false;
        }

        $rutaImagen = "";

        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] !== UPLOAD_ERR_NO_FILE) {

            if ($_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
                return false;
            }

            if ($_FILES['imagen']['size'] > 5 * 1024 * 1024) {
                return false;
            }

            $tipoArchivo = (new finfo(FILEINFO_MIME_TYPE))->file(
                $_FILES['imagen']['tmp_name']
            );
            $extensiones = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp',
            ];

            if (!isset($extensiones[$tipoArchivo])) {
                return false;
            }

            $nombreImagen = bin2hex(random_bytes(16)) . '.' . $extensiones[$tipoArchivo];

            $rutaImagen =
                "assets/uploads/" .
                $nombreImagen;

            if (!move_uploaded_file(
                $_FILES['imagen']['tmp_name'],
                $rutaImagen
            )) {
                return false;
            }

        }


        return $this->modelo->publicar(

            trim($_POST['titulo']),
            trim($_POST['autor']),
            trim($_POST['editorial'] ?? ''),
            trim($_POST['universidad'] ?? ''),
            trim($_POST['carrera'] ?? ''),
            trim($_POST['semestre'] ?? ''),
            trim($_POST['materia'] ?? ''),
            trim($_POST['descripcion'] ?? ''),
            $precio,
            $estado,
            $tipoPublicacion,
            $rutaImagen,
            $_SESSION['id_usuario']

        );

    }


    public function listar(){

        return $this->modelo->obtenerTodos();

    }


    public function ultimosIngresados(){

        return $this->modelo->obtenerUltimosIngresados();

    }


    public function detalle($id){

        return $this->modelo->obtenerPorId($id);

    }


    public function misPublicaciones($usuario_id){

        return $this->modelo->obtenerPorUsuario(
            $usuario_id
        );

    }


    /*
     * HISTORIAL
     *
     * Obtiene los libros que ya fueron
     * vendidos o intercambiados.
     */

    public function historial(){

        return $this->modelo->obtenerHistorial();

    }


    public function eliminar($id){

        if(session_status() === PHP_SESSION_NONE){

            session_start();

        }

        $usuario_id = $_SESSION['id_usuario'];


        /*
         * Primero buscamos el libro para obtener
         * la imagen que tiene guardada.
         */

        $libro =
            $this->modelo->obtenerPorId($id);


        if(!$libro){

            return false;

        }


        /*
         * Seguridad:
         * comprobamos que el libro pertenece
         * al usuario que está conectado.
         */

        if(
            $libro['usuario_id'] != $usuario_id
        ){

            return false;

        }


        /*
         * Eliminamos el registro de la base de datos.
         */

        $resultado =
            $this->modelo->eliminar(
                $id,
                $usuario_id
            );


        /*
         * Si se eliminó correctamente,
         * eliminamos también la imagen.
         */

        if(
            $resultado &&
            !empty($libro['imagen'])
        ){

            $rutaCompleta =
                __DIR__ .
                "/../" .
                $libro['imagen'];


            if(file_exists($rutaCompleta)){

                unlink($rutaCompleta);

            }

        }


        return $resultado;

    }

}
