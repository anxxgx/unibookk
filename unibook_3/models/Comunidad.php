<?php

/*
 * FASE 5 — Comunidad UniBook.
 * Acceso a datos de publicaciones, comentarios y mensajes privados.
 * Todas las consultas son preparadas; los valores nunca se concatenan.
 */

class Comunidad
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }


    /* ------------------------------------------------------------
     * Utilidades internas
     * ------------------------------------------------------------ */

    /* SELECT preparado: devuelve un arreglo de filas ([] si falla). */
    private function filas($sql, $tipos, array $params)
    {
        try {

            $stmt = $this->conexion->prepare($sql);

            if (!$stmt) {
                return [];
            }

            if ($tipos !== '') {
                $stmt->bind_param($tipos, ...$params);
            }

            if (!$stmt->execute()) {
                $stmt->close();
                return [];
            }

            $resultado = $stmt->get_result();
            $filas = [];

            while ($fila = $resultado->fetch_assoc()) {
                $filas[] = $fila;
            }

            $stmt->close();

            return $filas;

        } catch (\Throwable $e) {

            return [];
        }
    }

    private function fila($sql, $tipos, array $params)
    {
        $filas = $this->filas($sql, $tipos, $params);

        return $filas ? $filas[0] : null;
    }

    /* INSERT/UPDATE/DELETE preparado: devuelve filas afectadas o false. */
    private function ejecutar($sql, $tipos, array $params)
    {
        try {

            $stmt = $this->conexion->prepare($sql);

            if (!$stmt) {
                return false;
            }

            if ($tipos !== '') {
                $stmt->bind_param($tipos, ...$params);
            }

            if (!$stmt->execute()) {
                $stmt->close();
                return false;
            }

            $afectadas = $stmt->affected_rows;

            $stmt->close();

            return $afectadas;

        } catch (\Throwable $e) {

            return false;
        }
    }

    private function idInsertado()
    {
        return (int)$this->conexion->insert_id;
    }


    /* ¿Se aplicó la migración database/fase5_comunidad.sql? (1 consulta) */
    public function tablasListas()
    {
        try {

            $r = $this->conexion->query("SHOW TABLES LIKE 'comunidad\\_%'");

            if (!$r) {
                return false;
            }

            $existentes = [];

            while ($fila = $r->fetch_row()) {
                $existentes[strtolower((string)$fila[0])] = true;
            }

            foreach ([
                'comunidad_publicaciones',
                'comunidad_comentarios',
                'comunidad_conversaciones',
                'comunidad_mensajes',
            ] as $tabla) {

                if (!isset($existentes[$tabla])) {
                    return false;
                }
            }

            return true;

        } catch (\Throwable $e) {

            return false;
        }
    }


    /* ------------------------------------------------------------
     * Publicaciones
     * ------------------------------------------------------------ */

    /* Construye WHERE + parámetros a partir de filtros ya validados. */
    private function condicionesFeed(array $f, &$tipos, array &$params)
    {
        $where = ['1=1'];
        $tipos = '';
        $params = [];

        if (!empty($f['categoria'])) {
            $where[] = 'p.categoria = ?';
            $tipos .= 's';
            $params[] = $f['categoria'];
        }

        if (!empty($f['solo_abiertas'])) {
            $where[] = "p.estado = 'activa'";
        }

        if (!empty($f['usuario_id'])) {
            $where[] = 'p.usuario_id = ?';
            $tipos .= 'i';
            $params[] = (int)$f['usuario_id'];
        }

        if (isset($f['q']) && $f['q'] !== '') {

            /* Se escapan los comodines para que el usuario no los controle. */
            $like = '%' . addcslashes($f['q'], '%_\\') . '%';

            $where[] = '(p.titulo LIKE ? OR p.contenido LIKE ? OR p.libro_titulo LIKE ? OR p.asignatura LIKE ?)';
            $tipos .= 'ssss';
            array_push($params, $like, $like, $like, $like);
        }

        return implode(' AND ', $where);
    }

    public function contarPublicaciones(array $filtros)
    {
        $tipos = '';
        $params = [];
        $where = $this->condicionesFeed($filtros, $tipos, $params);

        $fila = $this->fila(
            "SELECT COUNT(*) AS total FROM comunidad_publicaciones p WHERE " . $where,
            $tipos,
            $params
        );

        return $fila ? (int)$fila['total'] : 0;
    }

    public function listarPublicaciones(array $filtros, $limite, $offset)
    {
        $tipos = '';
        $params = [];
        $where = $this->condicionesFeed($filtros, $tipos, $params);

        $sql = "SELECT
                    p.*,
                    u.nombre AS autor_nombre,
                    u.apellido AS autor_apellido,
                    u.foto_perfil AS autor_foto,
                    u.universidad AS autor_universidad,
                    (
                        SELECT COUNT(*)
                        FROM comunidad_comentarios c
                        WHERE c.publicacion_id = p.id
                        AND c.eliminado = 0
                    ) AS total_comentarios
                FROM comunidad_publicaciones p
                INNER JOIN usuarios u ON u.id = p.usuario_id
                WHERE " . $where . "
                ORDER BY p.fecha_creacion DESC, p.id DESC
                LIMIT ? OFFSET ?";

        $tipos .= 'ii';
        $params[] = (int)$limite;
        $params[] = (int)$offset;

        return $this->filas($sql, $tipos, $params);
    }

    public function obtenerPublicacion($id)
    {
        return $this->fila(
            "SELECT
                p.*,
                u.nombre AS autor_nombre,
                u.apellido AS autor_apellido,
                u.foto_perfil AS autor_foto,
                u.universidad AS autor_universidad,
                u.carrera AS autor_carrera,
                (
                    SELECT COUNT(*)
                    FROM comunidad_comentarios c
                    WHERE c.publicacion_id = p.id
                    AND c.eliminado = 0
                ) AS total_comentarios
            FROM comunidad_publicaciones p
            INNER JOIN usuarios u ON u.id = p.usuario_id
            WHERE p.id = ?",
            'i',
            [(int)$id]
        );
    }

    public function crearPublicacion($uid, array $d)
    {
        $ok = $this->ejecutar(
            "INSERT INTO comunidad_publicaciones
                (usuario_id, categoria, titulo, contenido, asignatura,
                 carrera, libro_titulo, libro_autor, modalidad, imagen)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            'isssssssss',
            [
                (int)$uid,
                $d['categoria'],
                $d['titulo'],
                $d['contenido'],
                $d['asignatura'],
                $d['carrera'],
                $d['libro_titulo'],
                $d['libro_autor'],
                $d['modalidad'],
                $d['imagen'],
            ]
        );

        return ($ok !== false && $ok > 0) ? $this->idInsertado() : 0;
    }

    /* Solo el autor puede editar: el usuario va dentro del WHERE. */
    public function actualizarPublicacion($id, $uid, array $d)
    {
        $r = $this->ejecutar(
            "UPDATE comunidad_publicaciones
             SET categoria = ?, titulo = ?, contenido = ?, asignatura = ?,
                 carrera = ?, libro_titulo = ?, libro_autor = ?,
                 modalidad = ?, imagen = ?, fecha_edicion = NOW()
             WHERE id = ? AND usuario_id = ?",
            'sssssssssii',
            [
                $d['categoria'],
                $d['titulo'],
                $d['contenido'],
                $d['asignatura'],
                $d['carrera'],
                $d['libro_titulo'],
                $d['libro_autor'],
                $d['modalidad'],
                $d['imagen'],
                (int)$id,
                (int)$uid,
            ]
        );

        return $r !== false;
    }

    public function cambiarEstado($id, $uid, $estado)
    {
        $r = $this->ejecutar(
            "UPDATE comunidad_publicaciones
             SET estado = ?
             WHERE id = ? AND usuario_id = ?",
            'sii',
            [$estado, (int)$id, (int)$uid]
        );

        return $r !== false;
    }

    public function eliminarPublicacion($id, $uid)
    {
        $r = $this->ejecutar(
            "DELETE FROM comunidad_publicaciones
             WHERE id = ? AND usuario_id = ?",
            'ii',
            [(int)$id, (int)$uid]
        );

        return $r !== false && $r > 0;
    }

    /* Libros ya publicados en UniBook que podrían resolver una necesidad. */
    public function sugerirLibros($texto, $excluirUsuarioId, $limite = 3)
    {
        $texto = trim((string)$texto);

        if ($texto === '') {
            return [];
        }

        $like = '%' . addcslashes($texto, '%_\\') . '%';

        return $this->filas(
            "SELECT id, titulo, autor, precio, tipo_publicacion, imagen
             FROM libros
             WHERE estado_disponibilidad = 'disponible'
             AND (usuario_id IS NULL OR usuario_id <> ?)
             AND (titulo LIKE ? OR materia LIKE ?)
             ORDER BY fecha_publicacion DESC
             LIMIT ?",
            'issi',
            [(int)$excluirUsuarioId, $like, $like, (int)$limite]
        );
    }

    /* Valores existentes para sugerir asignaturas y carreras. */
    public function sugerenciasAcademicas()
    {
        $materias = $this->filas(
            "SELECT DISTINCT TRIM(materia) AS valor
             FROM libros
             WHERE materia IS NOT NULL AND TRIM(materia) <> ''
             AND CHAR_LENGTH(TRIM(materia)) BETWEEN 3 AND 60
             ORDER BY valor ASC
             LIMIT 40",
            '',
            []
        );

        $carreras = $this->filas(
            "SELECT DISTINCT TRIM(carrera) AS valor
             FROM libros
             WHERE carrera IS NOT NULL AND TRIM(carrera) <> ''
             AND CHAR_LENGTH(TRIM(carrera)) BETWEEN 3 AND 60
             ORDER BY valor ASC
             LIMIT 40",
            '',
            []
        );

        return [
            'asignaturas' => array_column($materias, 'valor'),
            'carreras'    => array_column($carreras, 'valor'),
        ];
    }


    /* ------------------------------------------------------------
     * Comentarios (un nivel de respuestas)
     * ------------------------------------------------------------ */

    /*
     * Un comentario principal eliminado solo se muestra si todavía
     * tiene respuestas visibles (para no dejarlas huérfanas).
     */
    private function condicionRaizVisible()
    {
        return "c.publicacion_id = ?
                AND c.parent_id IS NULL
                AND (
                    c.eliminado = 0
                    OR EXISTS (
                        SELECT 1 FROM comunidad_comentarios r
                        WHERE r.parent_id = c.id AND r.eliminado = 0
                    )
                )";
    }

    public function contarComentariosRaiz($publicacionId)
    {
        $fila = $this->fila(
            "SELECT COUNT(*) AS total
             FROM comunidad_comentarios c
             WHERE " . $this->condicionRaizVisible(),
            'i',
            [(int)$publicacionId]
        );

        return $fila ? (int)$fila['total'] : 0;
    }

    public function listarComentariosRaiz($publicacionId, $limite, $offset)
    {
        return $this->filas(
            "SELECT
                c.*,
                u.nombre AS autor_nombre,
                u.apellido AS autor_apellido,
                u.foto_perfil AS autor_foto
             FROM comunidad_comentarios c
             INNER JOIN usuarios u ON u.id = c.usuario_id
             WHERE " . $this->condicionRaizVisible() . "
             ORDER BY c.id ASC
             LIMIT ? OFFSET ?",
            'iii',
            [(int)$publicacionId, (int)$limite, (int)$offset]
        );
    }

    /* Respuestas visibles de un grupo de comentarios principales. */
    public function listarRespuestas(array $idsPadres)
    {
        $idsPadres = array_values(array_filter(array_map('intval', $idsPadres)));

        if (!$idsPadres) {
            return [];
        }

        $marcas = implode(',', array_fill(0, count($idsPadres), '?'));

        return $this->filas(
            "SELECT
                c.*,
                u.nombre AS autor_nombre,
                u.apellido AS autor_apellido,
                u.foto_perfil AS autor_foto
             FROM comunidad_comentarios c
             INNER JOIN usuarios u ON u.id = c.usuario_id
             WHERE c.parent_id IN (" . $marcas . ")
             AND c.eliminado = 0
             ORDER BY c.id ASC",
            str_repeat('i', count($idsPadres)),
            $idsPadres
        );
    }

    public function obtenerComentario($id)
    {
        return $this->fila(
            "SELECT * FROM comunidad_comentarios WHERE id = ?",
            'i',
            [(int)$id]
        );
    }

    public function crearComentario($publicacionId, $uid, $parentId, $texto)
    {
        $parent = $parentId ? (int)$parentId : null;

        $ok = $this->ejecutar(
            "INSERT INTO comunidad_comentarios
                (publicacion_id, usuario_id, parent_id, contenido)
             VALUES (?, ?, ?, ?)",
            'iiis',
            [(int)$publicacionId, (int)$uid, $parent, $texto]
        );

        return ($ok !== false && $ok > 0) ? $this->idInsertado() : 0;
    }

    /* ¿Mismo texto del mismo usuario en los últimos segundos? */
    public function comentarioDuplicadoReciente($publicacionId, $uid, $parentId, $texto, $segundos = 20)
    {
        $parent = $parentId ? (int)$parentId : 0;

        $fila = $this->fila(
            "SELECT id FROM comunidad_comentarios
             WHERE publicacion_id = ? AND usuario_id = ?
             AND COALESCE(parent_id, 0) = ?
             AND contenido = ?
             AND fecha_creacion >= (NOW() - INTERVAL ? SECOND)
             LIMIT 1",
            'iiisi',
            [(int)$publicacionId, (int)$uid, $parent, $texto, (int)$segundos]
        );

        return $fila !== null;
    }

    public function editarComentario($id, $uid, $texto)
    {
        $r = $this->ejecutar(
            "UPDATE comunidad_comentarios
             SET contenido = ?, fecha_edicion = NOW()
             WHERE id = ? AND usuario_id = ? AND eliminado = 0",
            'sii',
            [$texto, (int)$id, (int)$uid]
        );

        return $r !== false && $r > 0;
    }

    /* Borrado lógico: se vacía el texto y se conservan las respuestas. */
    public function eliminarComentario($id, $uid)
    {
        $r = $this->ejecutar(
            "UPDATE comunidad_comentarios
             SET eliminado = 1, contenido = ''
             WHERE id = ? AND usuario_id = ? AND eliminado = 0",
            'ii',
            [(int)$id, (int)$uid]
        );

        return $r !== false && $r > 0;
    }


    /* ------------------------------------------------------------
     * Mensajes privados
     * ------------------------------------------------------------ */

    public function obtenerConversacion($id)
    {
        return $this->fila(
            "SELECT
                cv.*,
                a.nombre AS autor_nombre,
                a.apellido AS autor_apellido,
                a.foto_perfil AS autor_foto,
                i.nombre AS interesado_nombre,
                i.apellido AS interesado_apellido,
                i.foto_perfil AS interesado_foto,
                p.estado AS publicacion_estado
             FROM comunidad_conversaciones cv
             INNER JOIN usuarios a ON a.id = cv.autor_id
             INNER JOIN usuarios i ON i.id = cv.interesado_id
             LEFT JOIN comunidad_publicaciones p ON p.id = cv.publicacion_id
             WHERE cv.id = ?",
            'i',
            [(int)$id]
        );
    }

    public function buscarConversacion($publicacionId, $interesadoId)
    {
        return $this->fila(
            "SELECT id FROM comunidad_conversaciones
             WHERE publicacion_id = ? AND interesado_id = ?",
            'ii',
            [(int)$publicacionId, (int)$interesadoId]
        );
    }

    public function crearConversacion($publicacionId, $titulo, $autorId, $interesadoId)
    {
        $ok = $this->ejecutar(
            "INSERT INTO comunidad_conversaciones
                (publicacion_id, publicacion_titulo, autor_id, interesado_id)
             VALUES (?, ?, ?, ?)",
            'isii',
            [(int)$publicacionId, $titulo, (int)$autorId, (int)$interesadoId]
        );

        return ($ok !== false && $ok > 0) ? $this->idInsertado() : 0;
    }

    public function enviarMensaje($conversacionId, $remitenteId, $texto)
    {
        $ok = $this->ejecutar(
            "INSERT INTO comunidad_mensajes
                (conversacion_id, remitente_id, mensaje)
             VALUES (?, ?, ?)",
            'iis',
            [(int)$conversacionId, (int)$remitenteId, $texto]
        );

        return $ok !== false && $ok > 0;
    }

    /* Los últimos N mensajes, en orden cronológico. */
    public function listarMensajes($conversacionId, $limite = 200)
    {
        $filas = $this->filas(
            "SELECT id, remitente_id, mensaje, leido, fecha_creacion
             FROM comunidad_mensajes
             WHERE conversacion_id = ?
             ORDER BY id DESC
             LIMIT ?",
            'ii',
            [(int)$conversacionId, (int)$limite]
        );

        return array_reverse($filas);
    }

    public function marcarLeidos($conversacionId, $uid)
    {
        return $this->ejecutar(
            "UPDATE comunidad_mensajes
             SET leido = 1
             WHERE conversacion_id = ? AND remitente_id <> ? AND leido = 0",
            'ii',
            [(int)$conversacionId, (int)$uid]
        );
    }

    public function listarConversaciones($uid)
    {
        return $this->filas(
            "SELECT
                cv.id,
                cv.publicacion_id,
                cv.publicacion_titulo,
                cv.autor_id,
                cv.interesado_id,
                otro.id AS otro_id,
                otro.nombre AS otro_nombre,
                otro.apellido AS otro_apellido,
                otro.foto_perfil AS otro_foto,
                ult.mensaje AS ultimo_mensaje,
                ult.remitente_id AS ultimo_remitente,
                COALESCE(ult.fecha_creacion, cv.fecha_creacion) AS ultima_fecha,
                (
                    SELECT COUNT(*) FROM comunidad_mensajes m
                    WHERE m.conversacion_id = cv.id
                    AND m.remitente_id <> ?
                    AND m.leido = 0
                ) AS no_leidos
             FROM comunidad_conversaciones cv
             INNER JOIN usuarios otro
                ON otro.id = IF(cv.autor_id = ?, cv.interesado_id, cv.autor_id)
             LEFT JOIN comunidad_mensajes ult
                ON ult.id = (
                    SELECT MAX(m2.id) FROM comunidad_mensajes m2
                    WHERE m2.conversacion_id = cv.id
                )
             WHERE cv.autor_id = ? OR cv.interesado_id = ?
             ORDER BY ultima_fecha DESC, cv.id DESC
             LIMIT 100",
            'iiii',
            [(int)$uid, (int)$uid, (int)$uid, (int)$uid]
        );
    }

    public function contarNoLeidos($uid)
    {
        $fila = $this->fila(
            "SELECT COUNT(*) AS total
             FROM comunidad_mensajes m
             INNER JOIN comunidad_conversaciones cv ON cv.id = m.conversacion_id
             WHERE (cv.autor_id = ? OR cv.interesado_id = ?)
             AND m.remitente_id <> ?
             AND m.leido = 0",
            'iii',
            [(int)$uid, (int)$uid, (int)$uid]
        );

        return $fila ? (int)$fila['total'] : 0;
    }

    /* Cuántas personas han escrito al autor por una publicación. */
    public function contarConversacionesPublicacion($publicacionId)
    {
        $fila = $this->fila(
            "SELECT COUNT(*) AS total
             FROM comunidad_conversaciones
             WHERE publicacion_id = ?",
            'i',
            [(int)$publicacionId]
        );

        return $fila ? (int)$fila['total'] : 0;
    }
}
