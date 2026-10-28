<?php

require_once "models/Comunidad.php";
require_once "models/Notificacion.php";

/*
 * FASE 5 — Comunidad UniBook.
 *
 * Flujo:
 *  - Las rutas GET (accion=comunidad*) llaman a preparar() ANTES de que
 *    index.php imprima HTML, así cualquier redirección es segura.
 *  - Los formularios POST llevan el campo com_accion y los procesa
 *    procesarPost(). Todos terminan en redirección (Post/Redirect/Get).
 *
 * Reglas de estado de una publicación:
 *  - activa:   acepta comentarios, respuestas y conversaciones nuevas.
 *  - resuelta: solo lectura (no hay comentarios ni conversaciones nuevas),
 *    pero las conversaciones privadas ya abiertas pueden continuar.
 *  - cerrada:  igual que resuelta.
 *  El autor puede reabrir (volver a "activa") cuando quiera.
 */

class ComunidadController
{
    const POR_PAGINA        = 10;
    const COMENTARIOS_PAGINA = 15;

    const MAX_TITULO     = 150;
    const MIN_TITULO     = 5;
    const MAX_CONTENIDO  = 3000;
    const MIN_CONTENIDO  = 10;
    const MAX_COMENTARIO = 1500;
    const MAX_MENSAJE    = 1000;

    const IMG_MAX_BYTES   = 4194304;    // 4 MB
    const IMG_MAX_PIXELES = 16000000;   // 16 megapíxeles
    const IMG_MAX_LADO    = 1600;
    const IMG_DIR         = 'assets/uploads/comunidad/';

    private $modelo;
    private $notificaciones;
    private $listo = null;
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
        $this->modelo = new Comunidad($conexion);
        $this->notificaciones = new Notificacion($conexion);
    }


    /* ------------------------------------------------------------
     * Catálogos (únicas fuentes de verdad para vistas y validación)
     * ------------------------------------------------------------ */

    public static function categorias()
    {
        return [
            'busco_libro' => [
                'etiqueta' => 'Busco un libro',
                'icono'    => 'bi-search-heart',
            ],
            'recurso_academico' => [
                'etiqueta' => 'Necesito un recurso académico',
                'icono'    => 'bi-journal-bookmark',
            ],
            'pregunta_universitaria' => [
                'etiqueta' => 'Pregunta universitaria',
                'icono'    => 'bi-patch-question',
            ],
            'recomendacion' => [
                'etiqueta' => 'Recomendación de libro',
                'icono'    => 'bi-stars',
            ],
            'otro' => [
                'etiqueta' => 'Otro tema académico',
                'icono'    => 'bi-mortarboard',
            ],
        ];
    }

    public static function modalidades()
    {
        return [
            'cualquiera' => 'Compra o intercambio',
            'compra'     => 'Compra',
            'intercambio' => 'Intercambio',
        ];
    }

    public static function estados()
    {
        return [
            'activa'   => ['etiqueta' => 'Activa',   'icono' => 'bi-circle'],
            'resuelta' => ['etiqueta' => 'Resuelta', 'icono' => 'bi-check-circle-fill'],
            'cerrada'  => ['etiqueta' => 'Cerrada',  'icono' => 'bi-lock-fill'],
        ];
    }


    /* ------------------------------------------------------------
     * Sesión, CSRF y tokens de un solo uso
     * ------------------------------------------------------------ */

    private function sesion()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function usuarioActual()
    {
        $this->sesion();

        return isset($_SESSION['id_usuario'])
            ? (int)$_SESSION['id_usuario']
            : 0;
    }

    public static function csrfToken()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['csrf_comunidad'])) {
            $_SESSION['csrf_comunidad'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_comunidad'];
    }

    private function csrfValido()
    {
        $enviado = $_POST['csrf'] ?? '';

        return is_string($enviado)
            && !empty($_SESSION['csrf_comunidad'])
            && hash_equals($_SESSION['csrf_comunidad'], $enviado);
    }

    /*
     * Token de un solo uso para acciones que crean datos. Si el mismo
     * formulario se envía dos veces (doble clic, recargar, botón atrás),
     * el segundo envío se descarta.
     */
    public static function nonce()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['com_nonces']) || !is_array($_SESSION['com_nonces'])) {
            $_SESSION['com_nonces'] = [];
        }

        $token = bin2hex(random_bytes(16));

        $_SESSION['com_nonces'][$token] = time();

        /* Se conservan solo los 40 más recientes. */
        if (count($_SESSION['com_nonces']) > 40) {
            $_SESSION['com_nonces'] = array_slice(
                $_SESSION['com_nonces'],
                -40,
                null,
                true
            );
        }

        return $token;
    }

    private function consumirNonce()
    {
        $token = $_POST['nonce'] ?? '';

        if (
            !is_string($token)
            || $token === ''
            || empty($_SESSION['com_nonces'])
            || !isset($_SESSION['com_nonces'][$token])
        ) {
            return false;
        }

        unset($_SESSION['com_nonces'][$token]);

        return true;
    }

    private function flash($tipo, $mensaje)
    {
        $_SESSION['com_flash'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
    }

    /* Lee y borra el aviso pendiente (se muestra una sola vez). */
    public function tomarFlash()
    {
        $this->sesion();

        $f = $_SESSION['com_flash'] ?? null;

        unset($_SESSION['com_flash']);

        return is_array($f) ? $f : null;
    }

    /* Datos escritos en un formulario que falló la validación. */
    public function tomarAntiguo($formulario)
    {
        $this->sesion();

        $o = $_SESSION['com_old'] ?? null;

        if (is_array($o) && ($o['form'] ?? '') === $formulario) {
            unset($_SESSION['com_old']);
            return is_array($o['valores'] ?? null) ? $o['valores'] : null;
        }

        return null;
    }

    private function guardarAntiguo($formulario, array $valores)
    {
        $_SESSION['com_old'] = ['form' => $formulario, 'valores' => $valores];
    }

    private function url($accion, array $q = [], $ancla = '')
    {
        return 'index.php?' . http_build_query(
            array_merge(['accion' => $accion], $q)
        ) . $ancla;
    }

    private function ir($url)
    {
        header('Location: ' . $url);
        exit();
    }


    /* ------------------------------------------------------------
     * Texto: limpieza y validación
     * ------------------------------------------------------------ */

    private function limpiar($valor, $multilinea = false)
    {
        if (!is_string($valor)) {
            return '';
        }

        /* UTF-8 inválido: se descarta. */
        if (!preg_match('//u', $valor)) {
            return '';
        }

        $valor = str_replace(["\r\n", "\r"], "\n", $valor);

        $valor = (string)preg_replace(
            '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u',
            '',
            $valor
        );

        if ($multilinea) {
            $valor = (string)preg_replace("/\n{3,}/", "\n\n", $valor);
        } else {
            $valor = (string)preg_replace('/\s+/u', ' ', $valor);
        }

        return trim($valor);
    }

    private function largo($texto)
    {
        return function_exists('mb_strlen')
            ? mb_strlen($texto, 'UTF-8')
            : strlen($texto);
    }

    private function recortar($texto, $max)
    {
        if ($this->largo($texto) <= $max) {
            return $texto;
        }

        return function_exists('mb_substr')
            ? mb_substr($texto, 0, $max - 1, 'UTF-8') . '…'
            : substr($texto, 0, $max - 1) . '…';
    }

    private function nombreCorto(array $fila, $prefijo = 'autor_')
    {
        return trim(
            ($fila[$prefijo . 'nombre'] ?? '') . ' '
            . ($fila[$prefijo . 'apellido'] ?? '')
        );
    }


    /* ------------------------------------------------------------
     * Datos para las vistas (se llaman antes de imprimir HTML)
     * ------------------------------------------------------------ */

    public function tablasListas()
    {
        if ($this->listo === null) {
            $this->listo = $this->modelo->tablasListas();
        }

        return $this->listo;
    }

    public function contarNoLeidos()
    {
        $uid = $this->usuarioActual();

        if ($uid <= 0 || !$this->tablasListas()) {
            return 0;
        }

        return $this->modelo->contarNoLeidos($uid);
    }

    public function sugerenciasAcademicas()
    {
        return $this->modelo->sugerenciasAcademicas();
    }

    /*
     * Punto de entrada de las rutas GET de Comunidad. Devuelve el arreglo
     * de datos que usa la vista y puede redirigir (aún no hay HTML).
     */
    public function preparar($accion)
    {
        $this->sesion();

        $uid = $this->usuarioActual();

        $datos = [
            'listo' => $this->tablasListas(),
            'uid'   => $uid,
            'flash' => $this->tomarFlash(),
        ];

        /* Un POST que supera post_max_size llega vacío. */
        if (
            $_SERVER['REQUEST_METHOD'] === 'POST'
            && empty($_POST)
            && !empty($_SERVER['CONTENT_LENGTH'])
        ) {
            $datos['flash'] = [
                'tipo' => 'error',
                'mensaje' => 'El envío es demasiado grande. Usa una imagen de máximo 4 MB.',
            ];
        }

        if (!$datos['listo']) {
            return $datos;
        }

        $requiereLogin = ['comunidad_editar', 'comunidad_mensajes', 'comunidad_chat'];

        if ($uid <= 0 && in_array($accion, $requiereLogin, true)) {
            $this->ir('index.php?accion=login');
        }

        switch ($accion) {

            case 'comunidad':
                return $datos + $this->datosFeed($uid);

            case 'comunidad_publicacion':
                return $datos + $this->datosDetalle($uid);

            case 'comunidad_editar':
                return $datos + $this->datosEditar($uid);

            case 'comunidad_mensajes':
                return $datos + [
                    'conversaciones' => $this->modelo->listarConversaciones($uid),
                ];

            case 'comunidad_chat':
                return $datos + $this->datosChat($uid);
        }

        return $datos;
    }

    private function datosFeed($uid)
    {
        $categorias = self::categorias();

        $categoria = (string)($_GET['categoria'] ?? '');

        if (!isset($categorias[$categoria])) {
            $categoria = '';
        }

        $q = $this->limpiar((string)($_GET['q'] ?? ''), false);
        $q = $this->recortar($q, 80);

        $soloAbiertas = !empty($_GET['abiertas']);
        $mias = !empty($_GET['mias']) && $uid > 0;

        $filtros = [
            'categoria'     => $categoria,
            'q'             => $q,
            'solo_abiertas' => $soloAbiertas,
            'usuario_id'    => $mias ? $uid : 0,
        ];

        $total = $this->modelo->contarPublicaciones($filtros);
        $paginas = max(1, (int)ceil($total / self::POR_PAGINA));
        $pagina = min(max(1, (int)($_GET['pagina'] ?? 1)), $paginas);

        return [
            'filtros'       => [
                'categoria' => $categoria,
                'q'         => $q,
                'abiertas'  => $soloAbiertas,
                'mias'      => $mias,
            ],
            'publicaciones' => $this->modelo->listarPublicaciones(
                $filtros,
                self::POR_PAGINA,
                ($pagina - 1) * self::POR_PAGINA
            ),
            'total'         => $total,
            'pagina'        => $pagina,
            'paginas'       => $paginas,
            'sugerencias'   => $this->modelo->sugerenciasAcademicas(),
        ];
    }

    private function datosDetalle($uid)
    {
        $id = (int)($_GET['id'] ?? 0);

        $pub = $id > 0 ? $this->modelo->obtenerPublicacion($id) : null;

        if (!$pub) {
            $this->flash('error', 'Esa publicación no existe o fue eliminada.');
            $this->ir($this->url('comunidad'));
        }

        $totalRaices = $this->modelo->contarComentariosRaiz($id);
        $paginas = max(1, (int)ceil($totalRaices / self::COMENTARIOS_PAGINA));
        $pagina = min(max(1, (int)($_GET['cpag'] ?? 1)), $paginas);

        $raices = $this->modelo->listarComentariosRaiz(
            $id,
            self::COMENTARIOS_PAGINA,
            ($pagina - 1) * self::COMENTARIOS_PAGINA
        );

        $respuestas = [];

        foreach ($this->modelo->listarRespuestas(array_column($raices, 'id')) as $r) {
            $respuestas[(int)$r['parent_id']][] = $r;
        }

        $esAutor = $uid > 0 && (int)$pub['usuario_id'] === $uid;

        $sugeridos = [];

        if (
            $pub['categoria'] === 'busco_libro'
            && $pub['estado'] === 'activa'
        ) {
            $buscar = trim((string)($pub['libro_titulo'] ?? ''));

            if ($buscar === '') {
                $buscar = (string)$pub['titulo'];
            }

            $sugeridos = $this->modelo->sugerirLibros($buscar, $pub['usuario_id'], 3);
        }

        $miConversacion = 0;

        if ($uid > 0 && !$esAutor) {
            $c = $this->modelo->buscarConversacion($id, $uid);
            $miConversacion = $c ? (int)$c['id'] : 0;
        }

        return [
            'pub'             => $pub,
            'es_autor'        => $esAutor,
            'raices'          => $raices,
            'respuestas'      => $respuestas,
            'total_raices'    => $totalRaices,
            'pagina'          => $pagina,
            'paginas'         => $paginas,
            'sugeridos'       => $sugeridos,
            'mi_conversacion' => $miConversacion,
            'total_chats'     => $esAutor
                ? $this->modelo->contarConversacionesPublicacion($id)
                : 0,
        ];
    }

    private function datosEditar($uid)
    {
        $id = (int)($_GET['id'] ?? 0);

        $pub = $id > 0 ? $this->modelo->obtenerPublicacion($id) : null;

        if (!$pub) {
            $this->flash('error', 'Esa publicación no existe o fue eliminada.');
            $this->ir($this->url('comunidad'));
        }

        if ((int)$pub['usuario_id'] !== $uid) {
            $this->flash('error', 'Solo el autor puede editar esta publicación.');
            $this->ir($this->url('comunidad_publicacion', ['id' => $id]));
        }

        return [
            'pub'         => $pub,
            'sugerencias' => $this->modelo->sugerenciasAcademicas(),
        ];
    }

    private function datosChat($uid)
    {
        $convId = (int)($_GET['id'] ?? 0);
        $pubId = (int)($_GET['publicacion'] ?? 0);

        /* --- Conversación existente --- */
        if ($convId > 0) {

            $conv = $this->modelo->obtenerConversacion($convId);

            /*
             * Si no existe o el usuario no participa, la respuesta es la
             * misma: no se revela si el ID existe.
             */
            if (!$conv || !$this->esParticipante($conv, $uid)) {
                $this->flash('error', 'No encontramos esa conversación.');
                $this->ir($this->url('comunidad_mensajes'));
            }

            $this->modelo->marcarLeidos($convId, $uid);

            $esAutor = (int)$conv['autor_id'] === $uid;

            return [
                'conversacion' => $conv,
                'mensajes'     => $this->modelo->listarMensajes($convId),
                'otro'         => [
                    'id'       => $esAutor ? (int)$conv['interesado_id'] : (int)$conv['autor_id'],
                    'nombre'   => $esAutor
                        ? $this->nombreCorto($conv, 'interesado_')
                        : $this->nombreCorto($conv, 'autor_'),
                    'foto'     => $esAutor ? $conv['interesado_foto'] : $conv['autor_foto'],
                ],
                'nueva'        => false,
            ];
        }

        /* --- Conversación nueva desde una publicación --- */
        $pub = $pubId > 0 ? $this->modelo->obtenerPublicacion($pubId) : null;

        if (!$pub) {
            $this->flash('error', 'Esa publicación no existe o fue eliminada.');
            $this->ir($this->url('comunidad'));
        }

        if ((int)$pub['usuario_id'] === $uid) {
            $this->flash('error', 'No puedes enviarte mensajes a ti mismo.');
            $this->ir($this->url('comunidad_publicacion', ['id' => $pubId]));
        }

        $existente = $this->modelo->buscarConversacion($pubId, $uid);

        if ($existente) {
            $this->ir($this->url('comunidad_chat', ['id' => (int)$existente['id']]));
        }

        if ($pub['estado'] !== 'activa') {
            $this->flash('error', 'Esta publicación ya no recibe mensajes nuevos.');
            $this->ir($this->url('comunidad_publicacion', ['id' => $pubId]));
        }

        return [
            'conversacion' => null,
            'publicacion'  => $pub,
            'mensajes'     => [],
            'otro'         => [
                'id'     => (int)$pub['usuario_id'],
                'nombre' => $this->nombreCorto($pub, 'autor_'),
                'foto'   => $pub['autor_foto'],
            ],
            'nueva'        => true,
        ];
    }

    private function esParticipante(array $conv, $uid)
    {
        return (int)$conv['autor_id'] === (int)$uid
            || (int)$conv['interesado_id'] === (int)$uid;
    }


    /* ------------------------------------------------------------
     * Formularios POST
     * ------------------------------------------------------------ */

    public function procesarPost()
    {
        $this->sesion();

        $uid = $this->usuarioActual();

        /* Sin sesión: se usa el login existente. */
        if ($uid <= 0) {
            $this->ir('index.php?accion=login');
        }

        if (!$this->tablasListas()) {
            $this->flash('error', 'La Comunidad aún no está instalada en la base de datos.');
            $this->ir($this->url('comunidad'));
        }

        if (!$this->csrfValido()) {
            $this->flash('error', 'Tu sesión de seguridad expiró. Vuelve a intentarlo.');
            $this->ir($this->url('comunidad'));
        }

        $accion = (string)($_POST['com_accion'] ?? '');

        switch ($accion) {

            case 'publicar':
                $url = $this->accionPublicar($uid);
                break;

            case 'editar_publicacion':
                $url = $this->accionEditarPublicacion($uid);
                break;

            case 'estado_publicacion':
                $url = $this->accionEstado($uid);
                break;

            case 'eliminar_publicacion':
                $url = $this->accionEliminarPublicacion($uid);
                break;

            case 'comentar':
                $url = $this->accionComentar($uid);
                break;

            case 'editar_comentario':
                $url = $this->accionEditarComentario($uid);
                break;

            case 'eliminar_comentario':
                $url = $this->accionEliminarComentario($uid);
                break;

            case 'enviar_mensaje':
                $url = $this->accionEnviarMensaje($uid);
                break;

            default:
                $this->flash('error', 'No reconocemos esa acción.');
                $url = $this->url('comunidad');
        }

        $this->ir($url);
    }

    /* ---------- Publicaciones ---------- */

    /* Lee y valida el formulario de publicación. [datos, error|null]. */
    private function leerPublicacion()
    {
        $categorias = self::categorias();

        $categoria = (string)($_POST['categoria'] ?? '');
        $titulo = $this->limpiar((string)($_POST['titulo'] ?? ''), false);
        $contenido = $this->limpiar((string)($_POST['contenido'] ?? ''), true);
        $asignatura = $this->limpiar((string)($_POST['asignatura'] ?? ''), false);
        $carrera = $this->limpiar((string)($_POST['carrera'] ?? ''), false);
        $libroTitulo = $this->limpiar((string)($_POST['libro_titulo'] ?? ''), false);
        $libroAutor = $this->limpiar((string)($_POST['libro_autor'] ?? ''), false);
        $modalidad = (string)($_POST['modalidad'] ?? '');

        $valores = [
            'categoria'    => $categoria,
            'titulo'       => $titulo,
            'contenido'    => $contenido,
            'asignatura'   => $asignatura,
            'carrera'      => $carrera,
            'libro_titulo' => $libroTitulo,
            'libro_autor'  => $libroAutor,
            'modalidad'    => $modalidad,
        ];

        $error = null;

        if (!isset($categorias[$categoria])) {
            $error = 'Elige una categoría válida.';
        } elseif ($this->largo($titulo) < self::MIN_TITULO) {
            $error = 'El título debe tener al menos ' . self::MIN_TITULO . ' caracteres.';
        } elseif ($this->largo($titulo) > self::MAX_TITULO) {
            $error = 'El título no puede superar ' . self::MAX_TITULO . ' caracteres.';
        } elseif ($this->largo($contenido) < self::MIN_CONTENIDO) {
            $error = 'Cuéntanos un poco más: el contenido debe tener al menos ' . self::MIN_CONTENIDO . ' caracteres.';
        } elseif ($this->largo($contenido) > self::MAX_CONTENIDO) {
            $error = 'El contenido no puede superar ' . self::MAX_CONTENIDO . ' caracteres.';
        } elseif ($this->largo($asignatura) > 120 || $this->largo($carrera) > 120) {
            $error = 'La asignatura y la carrera pueden tener máximo 120 caracteres.';
        } elseif ($this->largo($libroTitulo) > 150 || $this->largo($libroAutor) > 100) {
            $error = 'El título del libro (150) o el autor (100) es demasiado largo.';
        }

        $conLibro = in_array($categoria, ['busco_libro', 'recomendacion'], true);

        if (!$conLibro) {
            $libroTitulo = '';
            $libroAutor = '';
        }

        if ($categoria !== 'busco_libro' || !isset(self::modalidades()[$modalidad])) {
            $modalidad = '';
        }

        $datos = [
            'categoria'    => $categoria,
            'titulo'       => $titulo,
            'contenido'    => $contenido,
            'asignatura'   => $asignatura !== '' ? $asignatura : null,
            'carrera'      => $carrera !== '' ? $carrera : null,
            'libro_titulo' => $libroTitulo !== '' ? $libroTitulo : null,
            'libro_autor'  => $libroAutor !== '' ? $libroAutor : null,
            'modalidad'    => $modalidad !== '' ? $modalidad : null,
            'imagen'       => null,
        ];

        return [$datos, $valores, $error];
    }

    private function accionPublicar($uid)
    {
        if (!$this->consumirNonce()) {
            /* Ya se envió (doble clic o recarga): no se duplica. */
            return $this->url('comunidad');
        }

        list($datos, $valores, $error) = $this->leerPublicacion();

        if ($error !== null) {
            $this->flash('error', $error);
            $this->guardarAntiguo('crear', $valores);
            return $this->url('comunidad', [], '#nueva');
        }

        $img = $this->guardarImagen($_FILES['imagen'] ?? null);

        if (!$img['ok']) {
            $this->flash('error', $img['mensaje']);
            $this->guardarAntiguo('crear', $valores);
            return $this->url('comunidad', [], '#nueva');
        }

        $datos['imagen'] = $img['ruta'];

        $id = $this->modelo->crearPublicacion($uid, $datos);

        if ($id <= 0) {
            $this->borrarImagen($img['ruta']);
            $this->flash('error', 'No se pudo publicar. Inténtalo de nuevo en un momento.');
            $this->guardarAntiguo('crear', $valores);
            return $this->url('comunidad', [], '#nueva');
        }

        $this->flash('ok', 'Tu publicación ya está en la Comunidad.');

        return $this->url('comunidad_publicacion', ['id' => $id]);
    }

    private function accionEditarPublicacion($uid)
    {
        $id = (int)($_POST['id'] ?? 0);

        $pub = $id > 0 ? $this->modelo->obtenerPublicacion($id) : null;

        if (!$pub) {
            $this->flash('error', 'Esa publicación no existe o fue eliminada.');
            return $this->url('comunidad');
        }

        if ((int)$pub['usuario_id'] !== $uid) {
            $this->flash('error', 'Solo el autor puede editar esta publicación.');
            return $this->url('comunidad_publicacion', ['id' => $id]);
        }

        list($datos, $valores, $error) = $this->leerPublicacion();

        $volver = $this->url('comunidad_editar', ['id' => $id]);

        if ($error !== null) {
            $this->flash('error', $error);
            $this->guardarAntiguo('editar_' . $id, $valores);
            return $volver;
        }

        $anterior = $pub['imagen'];
        $datos['imagen'] = $anterior;
        $imagenNueva = null;

        $img = $this->guardarImagen($_FILES['imagen'] ?? null);

        if (!$img['ok']) {
            $this->flash('error', $img['mensaje']);
            $this->guardarAntiguo('editar_' . $id, $valores);
            return $volver;
        }

        if ($img['ruta'] !== null) {
            $imagenNueva = $img['ruta'];
            $datos['imagen'] = $imagenNueva;
        } elseif (!empty($_POST['quitar_imagen'])) {
            $datos['imagen'] = null;
        }

        if (!$this->modelo->actualizarPublicacion($id, $uid, $datos)) {

            $this->borrarImagen($imagenNueva);
            $this->flash('error', 'No se pudieron guardar los cambios. Inténtalo de nuevo.');
            $this->guardarAntiguo('editar_' . $id, $valores);

            return $volver;
        }

        if ($anterior && $anterior !== $datos['imagen']) {
            $this->borrarImagen($anterior);
        }

        $this->flash('ok', 'Cambios guardados.');

        return $this->url('comunidad_publicacion', ['id' => $id]);
    }

    private function accionEstado($uid)
    {
        $id = (int)($_POST['id'] ?? 0);
        $estado = (string)($_POST['estado'] ?? '');

        if (!isset(self::estados()[$estado])) {
            $this->flash('error', 'Estado no válido.');
            return $this->url('comunidad_publicacion', ['id' => $id]);
        }

        $pub = $id > 0 ? $this->modelo->obtenerPublicacion($id) : null;

        if (!$pub) {
            $this->flash('error', 'Esa publicación no existe o fue eliminada.');
            return $this->url('comunidad');
        }

        if ((int)$pub['usuario_id'] !== $uid) {
            $this->flash('error', 'Solo el autor puede cambiar el estado.');
            return $this->url('comunidad_publicacion', ['id' => $id]);
        }

        if ($this->modelo->cambiarEstado($id, $uid, $estado)) {

            $mensajes = [
                'activa'   => 'La publicación volvió a estar activa.',
                'resuelta' => '¡Genial! La publicación quedó marcada como resuelta.',
                'cerrada'  => 'La publicación quedó cerrada.',
            ];

            $this->flash('ok', $mensajes[$estado]);

        } else {

            $this->flash('error', 'No se pudo cambiar el estado.');
        }

        return $this->url('comunidad_publicacion', ['id' => $id]);
    }

    private function accionEliminarPublicacion($uid)
    {
        $id = (int)($_POST['id'] ?? 0);

        $pub = $id > 0 ? $this->modelo->obtenerPublicacion($id) : null;

        if (!$pub) {
            $this->flash('error', 'Esa publicación no existe o fue eliminada.');
            return $this->url('comunidad');
        }

        if ((int)$pub['usuario_id'] !== $uid) {
            $this->flash('error', 'Solo el autor puede eliminar esta publicación.');
            return $this->url('comunidad_publicacion', ['id' => $id]);
        }

        if ($this->modelo->eliminarPublicacion($id, $uid)) {

            $this->borrarImagen($pub['imagen']);
            $this->flash('ok', 'Publicación eliminada.');

            return $this->url('comunidad');
        }

        $this->flash('error', 'No se pudo eliminar la publicación.');

        return $this->url('comunidad_publicacion', ['id' => $id]);
    }

    /* ---------- Comentarios ---------- */

    private function accionComentar($uid)
    {
        $pubId = (int)($_POST['publicacion_id'] ?? 0);
        $parentId = (int)($_POST['parent_id'] ?? 0);
        $cpag = max(1, (int)($_POST['cpag'] ?? 1));

        $pub = $pubId > 0 ? $this->modelo->obtenerPublicacion($pubId) : null;

        if (!$pub) {
            $this->flash('error', 'Esa publicación no existe o fue eliminada.');
            return $this->url('comunidad');
        }

        $volver = $this->url('comunidad_publicacion', ['id' => $pubId, 'cpag' => $cpag], '#comentarios');

        if (!$this->consumirNonce()) {
            return $volver;
        }

        if ($pub['estado'] !== 'activa') {
            $this->flash('error', 'Esta publicación ya no admite comentarios nuevos.');
            return $volver;
        }

        $texto = $this->limpiar((string)($_POST['contenido'] ?? ''), true);

        if ($texto === '') {
            $this->flash('error', 'Escribe algo antes de enviar.');
            return $volver;
        }

        if ($this->largo($texto) > self::MAX_COMENTARIO) {
            $this->flash('error', 'El comentario no puede superar ' . self::MAX_COMENTARIO . ' caracteres.');
            return $volver;
        }

        /* Respuestas: el padre debe ser de esta publicación; un solo nivel. */
        $padre = null;

        if ($parentId > 0) {

            $padre = $this->modelo->obtenerComentario($parentId);

            if (
                !$padre
                || (int)$padre['publicacion_id'] !== $pubId
                || (int)$padre['eliminado'] === 1
            ) {
                $this->flash('error', 'No se puede responder a ese comentario.');
                return $volver;
            }

            if (!empty($padre['parent_id'])) {
                $parentId = (int)$padre['parent_id'];
            }
        }

        if ($this->modelo->comentarioDuplicadoReciente($pubId, $uid, $parentId, $texto)) {
            return $volver;
        }

        $nuevoId = $this->modelo->crearComentario($pubId, $uid, $parentId, $texto);

        if ($nuevoId <= 0) {
            $this->flash('error', 'No se pudo enviar el comentario. Inténtalo de nuevo.');
            return $volver;
        }

        $this->notificarComentario($pub, $padre, $uid, $texto);

        /* Un comentario nuevo se ve en la última página; una respuesta, donde está. */
        if ($parentId <= 0) {

            $paginas = max(1, (int)ceil(
                $this->modelo->contarComentariosRaiz($pubId) / self::COMENTARIOS_PAGINA
            ));

            $cpag = $paginas;
        }

        $this->flash('ok', $parentId > 0 ? 'Respuesta publicada.' : 'Comentario publicado.');

        return $this->url(
            'comunidad_publicacion',
            ['id' => $pubId, 'cpag' => $cpag],
            '#comentario-' . $nuevoId
        );
    }

    private function accionEditarComentario($uid)
    {
        $id = (int)($_POST['comentario_id'] ?? 0);
        $cpag = max(1, (int)($_POST['cpag'] ?? 1));

        $com = $id > 0 ? $this->modelo->obtenerComentario($id) : null;

        if (!$com) {
            $this->flash('error', 'Ese comentario no existe.');
            return $this->url('comunidad');
        }

        $pubId = (int)$com['publicacion_id'];
        $volver = $this->url('comunidad_publicacion', ['id' => $pubId, 'cpag' => $cpag], '#comentario-' . $id);

        if ((int)$com['usuario_id'] !== $uid) {
            $this->flash('error', 'Solo puedes editar tus propios comentarios.');
            return $volver;
        }

        $texto = $this->limpiar((string)($_POST['contenido'] ?? ''), true);

        if ($texto === '' || $this->largo($texto) > self::MAX_COMENTARIO) {
            $this->flash('error', 'El comentario debe tener entre 1 y ' . self::MAX_COMENTARIO . ' caracteres.');
            return $volver;
        }

        if ($this->modelo->editarComentario($id, $uid, $texto)) {
            $this->flash('ok', 'Comentario actualizado.');
        } else {
            $this->flash('error', 'No se pudo actualizar el comentario.');
        }

        return $volver;
    }

    private function accionEliminarComentario($uid)
    {
        $id = (int)($_POST['comentario_id'] ?? 0);
        $cpag = max(1, (int)($_POST['cpag'] ?? 1));

        $com = $id > 0 ? $this->modelo->obtenerComentario($id) : null;

        if (!$com) {
            $this->flash('error', 'Ese comentario no existe.');
            return $this->url('comunidad');
        }

        $pubId = (int)$com['publicacion_id'];
        $volver = $this->url('comunidad_publicacion', ['id' => $pubId, 'cpag' => $cpag], '#comentarios');

        if ((int)$com['usuario_id'] !== $uid) {
            $this->flash('error', 'Solo puedes eliminar tus propios comentarios.');
            return $volver;
        }

        if ($this->modelo->eliminarComentario($id, $uid)) {
            $this->flash('ok', 'Comentario eliminado.');
        } else {
            $this->flash('error', 'No se pudo eliminar el comentario.');
        }

        return $volver;
    }

    /* ---------- Mensajes privados ---------- */

    private function accionEnviarMensaje($uid)
    {
        $convId = (int)($_POST['conversacion_id'] ?? 0);
        $pubId = (int)($_POST['publicacion_id'] ?? 0);

        $texto = $this->limpiar((string)($_POST['mensaje'] ?? ''), true);

        /* ----- Conversación ya existente ----- */
        if ($convId > 0) {

            $conv = $this->modelo->obtenerConversacion($convId);

            if (!$conv || !$this->esParticipante($conv, $uid)) {
                $this->flash('error', 'No encontramos esa conversación.');
                return $this->url('comunidad_mensajes');
            }

            $volver = $this->url('comunidad_chat', ['id' => $convId], '#fin');

            if (!$this->consumirNonce()) {
                return $volver;
            }

            if ($texto === '' || $this->largo($texto) > self::MAX_MENSAJE) {
                $this->flash('error', 'El mensaje debe tener entre 1 y ' . self::MAX_MENSAJE . ' caracteres.');
                return $volver;
            }

            if (!$this->modelo->enviarMensaje($convId, $uid, $texto)) {
                $this->flash('error', 'No se pudo enviar el mensaje. Inténtalo de nuevo.');
            }

            return $volver;
        }

        /* ----- Conversación nueva desde una publicación ----- */
        $pub = $pubId > 0 ? $this->modelo->obtenerPublicacion($pubId) : null;

        if (!$pub) {
            $this->flash('error', 'Esa publicación no existe o fue eliminada.');
            return $this->url('comunidad');
        }

        $autorId = (int)$pub['usuario_id'];
        $volverPub = $this->url('comunidad_publicacion', ['id' => $pubId]);

        if ($autorId === $uid) {
            $this->flash('error', 'No puedes enviarte mensajes a ti mismo.');
            return $volverPub;
        }

        $existente = $this->modelo->buscarConversacion($pubId, $uid);

        if (!$existente && $pub['estado'] !== 'activa') {
            $this->flash('error', 'Esta publicación ya no recibe mensajes nuevos.');
            return $volverPub;
        }

        $volverNueva = $this->url('comunidad_chat', ['publicacion' => $pubId]);

        if (!$this->consumirNonce()) {
            return $existente
                ? $this->url('comunidad_chat', ['id' => (int)$existente['id']], '#fin')
                : $volverNueva;
        }

        if ($texto === '' || $this->largo($texto) > self::MAX_MENSAJE) {
            $this->flash('error', 'El mensaje debe tener entre 1 y ' . self::MAX_MENSAJE . ' caracteres.');
            return $volverNueva;
        }

        $esNueva = false;

        if ($existente) {

            $convId = (int)$existente['id'];

        } else {

            $convId = $this->modelo->crearConversacion(
                $pubId,
                $this->recortar((string)$pub['titulo'], 150),
                $autorId,
                $uid
            );

            /* Carrera entre dos envíos: la clave única ya la creó el otro. */
            if ($convId <= 0) {

                $de_nuevo = $this->modelo->buscarConversacion($pubId, $uid);
                $convId = $de_nuevo ? (int)$de_nuevo['id'] : 0;

            } else {

                $esNueva = true;
            }
        }

        if ($convId <= 0 || !$this->modelo->enviarMensaje($convId, $uid, $texto)) {
            $this->flash('error', 'No se pudo enviar el mensaje. Inténtalo de nuevo.');
            return $volverNueva;
        }

        if ($esNueva) {

            $this->notificaciones->crear(
                $autorId,
                'comunidad_mensaje',
                'Nuevo mensaje privado',
                $this->quienSoy() . ' te escribió sobre "' . $this->recortar((string)$pub['titulo'], 60) . '".',
                $this->url('comunidad_chat', ['id' => $convId])
            );
        }

        return $this->url('comunidad_chat', ['id' => $convId], '#fin');
    }


    /* ------------------------------------------------------------
     * Notificaciones
     * ------------------------------------------------------------ */

    private function quienSoy()
    {
        $nombre = trim((string)($_SESSION['nombre'] ?? ''));

        return $nombre !== '' ? $nombre : 'Alguien';
    }

    private function notificarComentario(array $pub, $padre, $uid, $texto)
    {
        $enlace = $this->url('comunidad_publicacion', ['id' => (int)$pub['id']], '#comentarios');
        $titulo = $this->recortar((string)$pub['titulo'], 60);
        $avisados = [];

        /* Al autor de la publicación. */
        $autorId = (int)$pub['usuario_id'];

        if ($autorId !== (int)$uid) {

            $this->notificaciones->crear(
                $autorId,
                'comunidad_comentario',
                'Nuevo comentario en tu publicación',
                $this->quienSoy() . ' comentó en "' . $titulo . '".',
                $enlace
            );

            $avisados[$autorId] = true;
        }

        /* Al autor del comentario respondido (si no se le avisó ya). */
        if (is_array($padre)) {

            $padreId = (int)$padre['usuario_id'];

            if ($padreId !== (int)$uid && !isset($avisados[$padreId])) {

                $this->notificaciones->crear(
                    $padreId,
                    'comunidad_comentario',
                    'Respondieron a tu comentario',
                    $this->quienSoy() . ' respondió a tu comentario en "' . $titulo . '".',
                    $enlace
                );
            }
        }
    }


    /* ------------------------------------------------------------
     * Imágenes (opcionales)
     * ------------------------------------------------------------ */

    /* Ruta guardada en la base solo si cumple el formato esperado. */
    public function rutaImagenValida($ruta)
    {
        if (!is_string($ruta) || $ruta === '') {
            return null;
        }

        if (strpos($ruta, self::IMG_DIR) !== 0) {
            return null;
        }

        $nombre = substr($ruta, strlen(self::IMG_DIR));

        if (!preg_match('~^[A-Za-z0-9_]+\.(jpg|png|webp)$~', $nombre)) {
            return null;
        }

        if (!is_file(dirname(__DIR__) . '/' . $ruta)) {
            return null;
        }

        return $ruta;
    }

    private function borrarImagen($ruta)
    {
        if (!is_string($ruta) || $ruta === '') {
            return;
        }

        if (
            strpos($ruta, self::IMG_DIR) === 0
            && preg_match('~^[A-Za-z0-9_]+\.(jpg|png|webp)$~', substr($ruta, strlen(self::IMG_DIR)))
        ) {
            $archivo = dirname(__DIR__) . '/' . $ruta;

            if (is_file($archivo)) {
                @unlink($archivo);
            }
        }
    }

    /*
     * Devuelve ['ok'=>bool, 'ruta'=>string|null, 'mensaje'=>string].
     * Sin archivo adjunto es ok=true y ruta=null.
     */
    private function guardarImagen($archivo)
    {
        if (!is_array($archivo) || !isset($archivo['error'])) {
            return ['ok' => true, 'ruta' => null, 'mensaje' => ''];
        }

        switch ($archivo['error']) {

            case UPLOAD_ERR_OK:
                break;

            case UPLOAD_ERR_NO_FILE:
                return ['ok' => true, 'ruta' => null, 'mensaje' => ''];

            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return ['ok' => false, 'ruta' => null, 'mensaje' => 'La imagen es demasiado pesada. El máximo es 4 MB.'];

            default:
                return ['ok' => false, 'ruta' => null, 'mensaje' => 'No se pudo recibir la imagen. Inténtalo de nuevo.'];
        }

        if (!is_uploaded_file($archivo['tmp_name'])) {
            return ['ok' => false, 'ruta' => null, 'mensaje' => 'El archivo recibido no es válido.'];
        }

        if ($archivo['size'] <= 0 || $archivo['size'] > self::IMG_MAX_BYTES) {
            return ['ok' => false, 'ruta' => null, 'mensaje' => 'La imagen es demasiado pesada. El máximo es 4 MB.'];
        }

        /* Tipo REAL del contenido, no la extensión del nombre. */
        $tipos = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
        ];

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($archivo['tmp_name']);

        if (!isset($tipos[$mime])) {
            return ['ok' => false, 'ruta' => null, 'mensaje' => 'Formato no permitido. Usa una imagen JPG, PNG o WebP.'];
        }

        $info = @getimagesize($archivo['tmp_name']);

        if ($info === false || ($info['mime'] ?? '') !== $mime) {
            return ['ok' => false, 'ruta' => null, 'mensaje' => 'El archivo no es una imagen válida.'];
        }

        $ancho = (int)$info[0];
        $alto = (int)$info[1];

        if ($ancho < 32 || $alto < 32) {
            return ['ok' => false, 'ruta' => null, 'mensaje' => 'La imagen es demasiado pequeña.'];
        }

        if ($ancho * $alto > self::IMG_MAX_PIXELES) {
            return ['ok' => false, 'ruta' => null, 'mensaje' => 'La imagen tiene demasiados píxeles (máximo 16 megapíxeles).'];
        }

        $carpeta = dirname(__DIR__) . '/' . self::IMG_DIR;

        if (!is_dir($carpeta) && !@mkdir($carpeta, 0755, true)) {
            return ['ok' => false, 'ruta' => null, 'mensaje' => 'No se pudo preparar la carpeta de imágenes.'];
        }

        /* Nombre generado por el servidor; nunca el original. */
        $nombre = 'c_' . bin2hex(random_bytes(14)) . '.' . $tipos[$mime];
        $destino = $carpeta . $nombre;

        $guardada = $this->reprocesarImagen($archivo['tmp_name'], $destino, $mime, $ancho, $alto);

        /* Sin GD el archivo ya validado se guarda tal cual. */
        if (!$guardada) {
            $guardada = move_uploaded_file($archivo['tmp_name'], $destino);
        }

        if (!$guardada || !is_file($destino)) {
            return ['ok' => false, 'ruta' => null, 'mensaje' => 'No se pudo guardar la imagen. Inténtalo de nuevo.'];
        }

        @chmod($destino, 0644);

        return ['ok' => true, 'ruta' => self::IMG_DIR . $nombre, 'mensaje' => ''];
    }

    /*
     * Re-codifica la imagen con GD: elimina metadatos y cualquier carga
     * oculta, y limita el tamaño. Devuelve false si GD no puede hacerlo.
     */
    private function reprocesarImagen($origen, $destino, $mime, $ancho, $alto)
    {
        if (!function_exists('imagecreatetruecolor')) {
            return false;
        }

        $cargadores = [
            'image/jpeg' => 'imagecreatefromjpeg',
            'image/png'  => 'imagecreatefrompng',
            'image/webp' => 'imagecreatefromwebp',
        ];

        $guardadores = [
            'image/jpeg' => 'imagejpeg',
            'image/png'  => 'imagepng',
            'image/webp' => 'imagewebp',
        ];

        if (
            !function_exists($cargadores[$mime])
            || !function_exists($guardadores[$mime])
        ) {
            return false;
        }

        $img = @$cargadores[$mime]($origen);

        if (!$img) {
            return false;
        }

        $escala = min(1, self::IMG_MAX_LADO / max($ancho, $alto));

        if ($escala < 1) {

            $nuevoAncho = max(1, (int)round($ancho * $escala));
            $nuevoAlto = max(1, (int)round($alto * $escala));

            $copia = imagecreatetruecolor($nuevoAncho, $nuevoAlto);

            if ($mime !== 'image/jpeg') {
                imagealphablending($copia, false);
                imagesavealpha($copia, true);
            }

            imagecopyresampled($copia, $img, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto);
            imagedestroy($img);

            $img = $copia;
        }

        $ok = false;

        if ($mime === 'image/jpeg') {
            $ok = imagejpeg($img, $destino, 86);
        } elseif ($mime === 'image/png') {
            $ok = imagepng($img, $destino, 6);
        } else {
            $ok = imagewebp($img, $destino, 86);
        }

        imagedestroy($img);

        return (bool)$ok;
    }
}
