<?php

require_once "models/Usuario.php";

class UsuarioController {

    private $modelo;


    public function __construct($conexion){

        $this->modelo = new Usuario($conexion);

    }


    public function registrar(){

        $nombre = trim($_POST['nombre'] ?? '');
        $apellido = trim($_POST['apellido'] ?? '');
        $correo = strtolower(trim($_POST['correo'] ?? ''));

        if (
            $nombre === '' ||
            $apellido === '' ||
            !filter_var($correo, FILTER_VALIDATE_EMAIL) ||
            strlen($_POST['password'] ?? '') < 8
        ) {
            return false;
        }

        $password = password_hash(
            $_POST['password'],
            PASSWORD_DEFAULT
        );


        return $this->modelo->registrar(
            $nombre,
            $apellido,
            $correo,
            $password
        );

    }


    public function login(){

        $correo = $_POST['correo'];
        $password = $_POST['password'];


        $usuario = $this->modelo->buscarPorCorreo($correo);


        if($usuario){

            if(password_verify(
                $password,
                $usuario['password']
            )){


                if(session_status() === PHP_SESSION_NONE){
                    session_start();
                }


                $_SESSION['id_usuario'] = $usuario['id'];

                $_SESSION['nombre'] = $usuario['nombre'];


                header(
                    "Location: index.php?accion=dashboard"
                );

                exit();


            }else{

                echo "Contraseña incorrecta";

            }


        }else{

            echo "Usuario no encontrado";

        }

    }


    /*
     * Nuevo método (Fase 2 — Detalle del libro).
     * Devuelve los datos públicos del vendedor de un libro,
     * a partir de su usuario_id, para mostrarlos en el detalle.
     */
    public function obtenerPorId($id){

        return $this->modelo->buscarPorId($id);

    }


    /*
    |--------------------------------------------------------------------------
    | FASE 4 — Mi perfil
    |--------------------------------------------------------------------------
    | Todo lo nuevo vive aquí abajo; los métodos anteriores no cambian.
    */

    const PERFIL_FOTO_MAX_BYTES = 4194304;      // 4 MB
    const PERFIL_FOTO_MAX_PIXELES = 16000000;   // 16 megapíxeles
    const PERFIL_FOTO_LADO = 640;               // la foto se guarda cuadrada
    const PERFIL_DIR_FOTOS = 'assets/uploads/perfiles/';


    /* Token CSRF de la sesión (se crea una vez y se reutiliza). */
    public static function csrfToken(){

        if(session_status() === PHP_SESSION_NONE){
            session_start();
        }

        if(empty($_SESSION['csrf_perfil'])){
            $_SESSION['csrf_perfil'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_perfil'];
    }

    private function csrfValido(){

        $enviado = $_POST['csrf'] ?? '';

        return is_string($enviado)
            && !empty($_SESSION['csrf_perfil'])
            && hash_equals($_SESSION['csrf_perfil'], $enviado);
    }


    /* Datos del usuario autenticado para la vista de perfil. */
    public function perfilPropio(){

        return $this->modelo->obtenerPerfil(
            (int)$_SESSION['id_usuario']
        );
    }

    public function columnasPerfil(){

        return $this->modelo->columnasPerfil();
    }


    /*
     * Ruta de la foto lista para imprimir en <img>, o null si el usuario no
     * tiene foto o el archivo ya no existe (en ese caso la vista muestra el
     * avatar predeterminado).
     */
    public function rutaFotoValida($ruta){

        if(!is_string($ruta) || $ruta === ''){
            return null;
        }

        if(strpos($ruta, self::PERFIL_DIR_FOTOS) !== 0){
            return null;
        }

        if(!preg_match('~^[A-Za-z0-9_]+\.(jpg|png|webp)$~', substr($ruta, strlen(self::PERFIL_DIR_FOTOS)))){
            return null;
        }

        if(!is_file(dirname(__DIR__) . '/' . $ruta)){
            return null;
        }

        return $ruta;
    }


    /*
     * Punto único de entrada de los formularios de Mi perfil (POST a
     * index.php?accion=perfil). Siempre termina con redirección
     * (Post/Redirect/Get) para que recargar la página no reenvíe nada.
     */
    public function procesarPerfil(){

        if(session_status() === PHP_SESSION_NONE){
            session_start();
        }

        if(!isset($_SESSION['id_usuario'])){
            header("Location: index.php?accion=login");
            exit();
        }

        $uid = (int)$_SESSION['id_usuario'];

        // Si el archivo supera post_max_size, PHP vacía $_POST y $_FILES.
        if(empty($_POST) && empty($_FILES) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0){
            $this->flashPerfil('error', 'La imagen supera el tamaño máximo que acepta el servidor. Prueba con una de menos de 4 MB.');
        }

        if(!$this->csrfValido()){
            $this->flashPerfil('error', 'La sesión del formulario expiró. Recarga la página e inténtalo de nuevo.');
        }

        if(isset($_POST['perfil_foto'])){

            $resultado = $this->guardarFotoPerfil($uid);

            $this->flashPerfil(
                $resultado['ok'] ? 'ok' : 'error',
                $resultado['mensaje']
            );
        }

        if(isset($_POST['perfil_quitar_foto'])){

            $resultado = $this->quitarFotoPerfil($uid);

            $this->flashPerfil(
                $resultado['ok'] ? 'ok' : 'error',
                $resultado['mensaje']
            );
        }

        if(isset($_POST['perfil_guardar'])){

            $this->guardarDatosPerfil($uid);
        }

        $this->flashPerfil('error', 'No se reconoció la acción solicitada.');
    }


    private function flashPerfil($tipo, $mensaje, array $extra = []){

        $_SESSION['perfil_flash'] = array_merge(
            ['tipo' => $tipo, 'mensaje' => $mensaje],
            $extra
        );

        header("Location: index.php?accion=perfil");

        exit();
    }


    private function limpiarTexto($valor, $multilinea = false){

        $valor = is_string($valor) ? $valor : '';

        $valor = str_replace("\r\n", "\n", $valor);
        $valor = str_replace("\r", "\n", $valor);

        // Quita caracteres de control (conserva el salto de línea).
        $valor = preg_replace('/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]/', '', $valor);

        if($multilinea){
            $valor = preg_replace("/\n{3,}/", "\n\n", $valor);
        }else{
            $compactado = preg_replace('/\s+/u', ' ', str_replace("\n", ' ', $valor));

            // Con UTF-8 inválido preg_replace devuelve null: se descarta el valor.
            $valor = $compactado === null ? '' : $compactado;
        }

        return trim($valor);
    }


    private function guardarDatosPerfil($uid){

        $columnas = $this->modelo->columnasPerfil();

        $datos = [
            'nombre'   => $this->limpiarTexto($_POST['nombre'] ?? ''),
            'apellido' => $this->limpiarTexto($_POST['apellido'] ?? ''),
        ];

        if(!empty($columnas['universidad'])){
            $datos['universidad'] = $this->limpiarTexto($_POST['universidad'] ?? '');
        }

        if(!empty($columnas['carrera'])){
            $datos['carrera'] = $this->limpiarTexto($_POST['carrera'] ?? '');
        }

        if(!empty($columnas['semestre'])){
            $datos['semestre'] = $this->limpiarTexto($_POST['semestre'] ?? '');
        }

        if(!empty($columnas['biografia'])){
            $datos['biografia'] = $this->limpiarTexto($_POST['biografia'] ?? '', true);
        }

        // Valores originales para repoblar el formulario si hay errores.
        $conservar = ['abrir' => true, 'valores' => $datos];

        foreach($datos as $valor){
            if(!mb_check_encoding($valor, 'UTF-8')){
                $this->flashPerfil('error', 'Hay caracteres no válidos en el formulario.', $conservar);
            }
        }

        $errores = [];

        if($datos['nombre'] === '' || mb_strlen($datos['nombre'], 'UTF-8') > 100){
            $errores[] = 'El nombre es obligatorio (máximo 100 caracteres).';
        }

        if($datos['apellido'] === '' || mb_strlen($datos['apellido'], 'UTF-8') > 100){
            $errores[] = 'El apellido es obligatorio (máximo 100 caracteres).';
        }

        if(isset($datos['universidad']) && mb_strlen($datos['universidad'], 'UTF-8') > 150){
            $errores[] = 'La universidad admite hasta 150 caracteres.';
        }

        if(isset($datos['carrera']) && mb_strlen($datos['carrera'], 'UTF-8') > 150){
            $errores[] = 'La carrera admite hasta 150 caracteres.';
        }

        if(isset($datos['semestre'])){

            $validos = array_map('strval', range(1, 12));

            if($datos['semestre'] !== '' && !in_array($datos['semestre'], $validos, true)){
                $errores[] = 'Elige un semestre entre 1 y 12.';
            }
        }

        if(isset($datos['biografia']) && mb_strlen($datos['biografia'], 'UTF-8') > 300){
            $errores[] = 'La biografía admite hasta 300 caracteres.';
        }

        if($errores){
            $this->flashPerfil('error', implode(' ', $errores), $conservar);
        }

        // Los campos opcionales vacíos se guardan como NULL.
        foreach(['universidad', 'carrera', 'semestre', 'biografia'] as $campo){
            if(isset($datos[$campo]) && $datos[$campo] === ''){
                $datos[$campo] = null;
            }
        }

        if(!$this->modelo->actualizarPerfil($uid, $datos)){
            $this->flashPerfil('error', 'No se pudieron guardar los cambios. Inténtalo de nuevo.', $conservar);
        }

        // La barra superior lee el nombre desde la sesión.
        $_SESSION['nombre'] = $datos['nombre'];

        $this->flashPerfil('ok', 'Tu perfil se actualizó correctamente.');
    }


    /* Solo borra archivos de ESTE usuario dentro de la carpeta de perfiles. */
    private function borrarArchivoFoto($ruta, $uid){

        if(!is_string($ruta) || $ruta === ''){
            return;
        }

        if(strpos($ruta, self::PERFIL_DIR_FOTOS) !== 0){
            return;
        }

        $nombre = substr($ruta, strlen(self::PERFIL_DIR_FOTOS));

        if(!preg_match('~^u' . (int)$uid . '_[a-f0-9]{24}\.(jpg|png|webp)$~', $nombre)){
            return;
        }

        $completa = dirname(__DIR__) . '/' . self::PERFIL_DIR_FOTOS . $nombre;

        if(is_file($completa)){
            @unlink($completa);
        }
    }


    private function guardarFotoPerfil($uid){

        $columnas = $this->modelo->columnasPerfil();

        if(empty($columnas['foto_perfil'])){
            return ['ok' => false, 'mensaje' => 'La foto de perfil todavía no está activada: falta ejecutar database/fase4_perfil.sql.'];
        }

        if(!isset($_FILES['foto']) || !is_array($_FILES['foto'])){
            return ['ok' => false, 'mensaje' => 'Selecciona una imagen para tu foto de perfil.'];
        }

        $archivo = $_FILES['foto'];

        switch($archivo['error']){
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                return ['ok' => false, 'mensaje' => 'Selecciona una imagen para tu foto de perfil.'];
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return ['ok' => false, 'mensaje' => 'La imagen es demasiado pesada. El máximo es 4 MB.'];
            default:
                return ['ok' => false, 'mensaje' => 'No se pudo recibir la imagen. Inténtalo de nuevo.'];
        }

        if(!is_uploaded_file($archivo['tmp_name'])){
            return ['ok' => false, 'mensaje' => 'El archivo recibido no es válido.'];
        }

        if($archivo['size'] > self::PERFIL_FOTO_MAX_BYTES || $archivo['size'] <= 0){
            return ['ok' => false, 'mensaje' => 'La imagen es demasiado pesada. El máximo es 4 MB.'];
        }

        // Tipo REAL del contenido, no la extensión que declara el nombre.
        $tipos = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
        ];

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($archivo['tmp_name']);

        if(!isset($tipos[$mime])){
            return ['ok' => false, 'mensaje' => 'Formato no permitido. Usa una imagen JPG, PNG o WebP.'];
        }

        // Debe ser realmente una imagen decodificable y del mismo tipo.
        $info = @getimagesize($archivo['tmp_name']);

        if($info === false || ($info['mime'] ?? '') !== $mime){
            return ['ok' => false, 'mensaje' => 'El archivo no es una imagen válida.'];
        }

        $ancho = (int)$info[0];
        $alto = (int)$info[1];

        if($ancho < 64 || $alto < 64){
            return ['ok' => false, 'mensaje' => 'La imagen es demasiado pequeña (mínimo 64 × 64 píxeles).'];
        }

        if($ancho * $alto > self::PERFIL_FOTO_MAX_PIXELES){
            return ['ok' => false, 'mensaje' => 'La imagen tiene demasiados píxeles. Usa una de máximo 16 megapíxeles.'];
        }

        $extension = $tipos[$mime];

        $carpeta = dirname(__DIR__) . '/' . self::PERFIL_DIR_FOTOS;

        if(!is_dir($carpeta) && !@mkdir($carpeta, 0755, true)){
            return ['ok' => false, 'mensaje' => 'No se pudo preparar la carpeta de fotos.'];
        }

        // Nombre generado por el servidor: nunca se usa el nombre original.
        $nombre = 'u' . (int)$uid . '_' . bin2hex(random_bytes(12)) . '.' . $extension;

        $destino = $carpeta . $nombre;

        $guardada = $this->procesarImagenPerfil(
            $archivo['tmp_name'],
            $destino,
            $mime,
            $ancho,
            $alto
        );

        // Sin GD (o sin soporte del formato) se guarda el archivo ya validado.
        if(!$guardada){
            $guardada = move_uploaded_file($archivo['tmp_name'], $destino);
        }

        if(!$guardada || !is_file($destino)){
            return ['ok' => false, 'mensaje' => 'No se pudo guardar la imagen. Inténtalo de nuevo.'];
        }

        @chmod($destino, 0644);

        $perfil = $this->modelo->obtenerPerfil($uid);
        $anterior = $perfil['foto_perfil'] ?? null;

        $rutaNueva = self::PERFIL_DIR_FOTOS . $nombre;

        if(!$this->modelo->actualizarFoto($uid, $rutaNueva)){
            @unlink($destino);
            return ['ok' => false, 'mensaje' => 'No se pudo guardar la foto en tu perfil. Inténtalo de nuevo.'];
        }

        $this->borrarArchivoFoto($anterior, $uid);

        return ['ok' => true, 'mensaje' => 'Tu foto de perfil se actualizó correctamente.'];
    }


    /*
     * Recorta al centro en cuadrado, corrige la orientación EXIF, reduce a
     * 640 px y vuelve a codificar la imagen. Re-codificar elimina cualquier
     * dato extra (metadatos, contenido incrustado) del archivo original.
     * Devuelve false si GD no está disponible para ese formato.
     */
    private function procesarImagenPerfil($origen, $destino, $mime, $ancho, $alto){

        if(!extension_loaded('gd')){
            return false;
        }

        $crear = [
            'image/jpeg' => 'imagecreatefromjpeg',
            'image/png'  => 'imagecreatefrompng',
            'image/webp' => 'imagecreatefromwebp',
        ];

        $guardar = [
            'image/jpeg' => 'imagejpeg',
            'image/png'  => 'imagepng',
            'image/webp' => 'imagewebp',
        ];

        if(!function_exists($crear[$mime]) || !function_exists($guardar[$mime])){
            return false;
        }

        $imagen = @$crear[$mime]($origen);

        if(!$imagen){
            return false;
        }

        // Orientación de fotos tomadas con el celular.
        if($mime === 'image/jpeg' && function_exists('exif_read_data') && function_exists('imagerotate')){

            $exif = @exif_read_data($origen);
            $orientacion = (int)($exif['Orientation'] ?? 1);
            $grados = [3 => 180, 6 => -90, 8 => 90];

            if(isset($grados[$orientacion])){
                $rotada = imagerotate($imagen, $grados[$orientacion], 0);
                if($rotada){
                    imagedestroy($imagen);
                    $imagen = $rotada;
                }
            }
        }

        $w = imagesx($imagen);
        $h = imagesy($imagen);

        $lado = min($w, $h);
        $salida = min($lado, self::PERFIL_FOTO_LADO);

        $lienzo = imagecreatetruecolor($salida, $salida);

        if($mime !== 'image/jpeg'){
            imagealphablending($lienzo, false);
            imagesavealpha($lienzo, true);
            imagefill($lienzo, 0, 0, imagecolorallocatealpha($lienzo, 0, 0, 0, 127));
        }

        imagecopyresampled(
            $lienzo,
            $imagen,
            0, 0,
            (int)(($w - $lado) / 2), (int)(($h - $lado) / 2),
            $salida, $salida,
            $lado, $lado
        );

        if($mime === 'image/jpeg'){
            $ok = imagejpeg($lienzo, $destino, 86);
        }elseif($mime === 'image/png'){
            $ok = imagepng($lienzo, $destino, 6);
        }else{
            $ok = imagewebp($lienzo, $destino, 85);
        }

        imagedestroy($imagen);
        imagedestroy($lienzo);

        return (bool)$ok;
    }


    private function quitarFotoPerfil($uid){

        $columnas = $this->modelo->columnasPerfil();

        if(empty($columnas['foto_perfil'])){
            return ['ok' => false, 'mensaje' => 'La foto de perfil todavía no está activada.'];
        }

        $perfil = $this->modelo->obtenerPerfil($uid);

        if(!$perfil || empty($perfil['foto_perfil'])){
            return ['ok' => false, 'mensaje' => 'Todavía no tienes una foto de perfil.'];
        }

        $anterior = $perfil['foto_perfil'];

        if(!$this->modelo->actualizarFoto($uid, null)){
            return ['ok' => false, 'mensaje' => 'No se pudo quitar la foto. Inténtalo de nuevo.'];
        }

        $this->borrarArchivoFoto($anterior, $uid);

        return ['ok' => true, 'mensaje' => 'Quitaste tu foto de perfil.'];
    }

}
