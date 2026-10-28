<?php

class Usuario {

    private $conexion;

    public function __construct($conexion){
        $this->conexion = $conexion;
    }


    /*
     * FASE 3.0 — Estabilización técnica.
     * Antes esta consulta concatenaba las variables directamente
     * en el SQL (vulnerable a inyección). Ahora usa una consulta
     * preparada. La firma del método y lo que hace no cambian:
     * sigue insertando el mismo registro con las mismas columnas.
     */
    public function registrar($nombre, $apellido, $correo, $password){

        $sql = "INSERT INTO usuarios
        (nombre, apellido, correo, password)
        VALUES
        (?, ?, ?, ?)";

        $stmt = $this->conexion->prepare($sql);

        if(!$stmt){
            return false;
        }

        $stmt->bind_param(
            "ssss",
            $nombre,
            $apellido,
            $correo,
            $password
        );

        $resultado = $stmt->execute();

        $stmt->close();

        return $resultado;
    }


    /*
     * FASE 3.0 — Estabilización técnica.
     * Mismo motivo que "registrar()": se migra a consulta
     * preparada. Sigue devolviendo exactamente lo mismo que
     * antes (un arreglo asociativo o null), así que login()
     * en UsuarioController no necesita ningún cambio.
     */
    public function buscarPorCorreo($correo){

        $sql = "SELECT * FROM usuarios
                WHERE correo = ?";

        $stmt = $this->conexion->prepare($sql);

        if(!$stmt){
            return null;
        }

        $stmt->bind_param("s", $correo);
        $stmt->execute();

        $resultado = $stmt->get_result();
        $usuario = $resultado->fetch_assoc();

        $stmt->close();

        return $usuario;
    }


    /*
     * Nuevo método (Fase 2 — Detalle del libro).
     * Se usa para mostrar la información del vendedor en el
     * detalle de un libro, a partir del "usuario_id" que ya
     * guarda la tabla "libros".
     *
     * Se implementa con una consulta preparada (más segura que
     * los métodos anteriores) porque es código nuevo; no modifica
     * el comportamiento de "registrar" ni "buscarPorCorreo".
     */
    public function buscarPorId($id){

        $sql = "SELECT id, nombre, apellido, correo
                FROM usuarios
                WHERE id = ?";

        $stmt = $this->conexion->prepare($sql);

        if(!$stmt){
            return null;
        }

        $stmt->bind_param("i", $id);
        $stmt->execute();

        $resultado = $stmt->get_result();
        $usuario = $resultado->fetch_assoc();

        $stmt->close();

        return $usuario;
    }


    /*
    |--------------------------------------------------------------------------
    | FASE 4 — Mi perfil
    |--------------------------------------------------------------------------
    | Todo lo nuevo vive aquí abajo; los métodos anteriores no cambian.
    | Las columnas foto_perfil, biografia, universidad, carrera y semestre
    | las crea database/fase4_perfil.sql. Mientras no se haya ejecutado, estos
    | métodos lo detectan y la vista sigue funcionando sin ellas.
    */

    private $columnasPerfilCache = null;

    /*
     * Devuelve qué columnas opcionales del perfil existen realmente en la
     * tabla "usuarios": ['foto_perfil' => bool, 'biografia' => bool, ...].
     */
    public function columnasPerfil(){

        if($this->columnasPerfilCache !== null){
            return $this->columnasPerfilCache;
        }

        $opcionales = [
            'foto_perfil' => false,
            'biografia'   => false,
            'universidad' => false,
            'carrera'     => false,
            'semestre'    => false,
        ];

        try{

            $resultado = $this->conexion->query(
                "SELECT COLUMN_NAME
                 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                 AND TABLE_NAME = 'usuarios'"
            );

            if($resultado){
                while($fila = $resultado->fetch_assoc()){
                    if(array_key_exists($fila['COLUMN_NAME'], $opcionales)){
                        $opcionales[$fila['COLUMN_NAME']] = true;
                    }
                }
            }

        }catch(Throwable $e){
            // Si no se puede consultar, se asume que no hay columnas extra.
        }

        $this->columnasPerfilCache = $opcionales;

        return $opcionales;
    }


    /*
     * Datos del propio perfil. Nunca incluye la contraseña.
     * Las columnas opcionales que todavía no existen llegan como null.
     */
    public function obtenerPerfil($id){

        $columnas = $this->columnasPerfil();

        $campos = ['id', 'nombre', 'apellido', 'correo'];

        foreach($columnas as $columna => $existe){
            if($existe){
                $campos[] = $columna;
            }
        }

        try{

            $stmt = $this->conexion->prepare(
                "SELECT " . implode(', ', $campos) . "
                 FROM usuarios
                 WHERE id = ?"
            );

            if(!$stmt){
                return null;
            }

            $stmt->bind_param("i", $id);
            $stmt->execute();

            $perfil = $stmt->get_result()->fetch_assoc();

            $stmt->close();

        }catch(Throwable $e){
            return null;
        }

        if(!$perfil){
            return null;
        }

        foreach($columnas as $columna => $existe){
            if(!$existe){
                $perfil[$columna] = null;
            }
        }

        return $perfil;
    }


    /*
     * Actualiza SOLO los campos permitidos del usuario indicado.
     * $datos puede traer: nombre, apellido, universidad, carrera,
     * semestre, biografia. Lo que no exista como columna se ignora.
     */
    public function actualizarPerfil($id, array $datos){

        $permitidos = [
            'nombre'      => true,
            'apellido'    => true,
            'universidad' => 'universidad',
            'carrera'     => 'carrera',
            'semestre'    => 'semestre',
            'biografia'   => 'biografia',
        ];

        $columnas = $this->columnasPerfil();

        $asignaciones = [];
        $valores = [];

        foreach($permitidos as $campo => $requisito){

            if(!array_key_exists($campo, $datos)){
                continue;
            }

            if($requisito !== true && empty($columnas[$requisito])){
                continue;
            }

            $asignaciones[] = $campo . " = ?";
            $valores[] = $datos[$campo];
        }

        if(!$asignaciones){
            return false;
        }

        try{

            $stmt = $this->conexion->prepare(
                "UPDATE usuarios
                 SET " . implode(', ', $asignaciones) . "
                 WHERE id = ?"
            );

            if(!$stmt){
                return false;
            }

            $tipos = str_repeat('s', count($valores)) . 'i';
            $valores[] = (int)$id;

            $stmt->bind_param($tipos, ...$valores);

            $resultado = $stmt->execute();

            $stmt->close();

            return $resultado;

        }catch(Throwable $e){
            return false;
        }
    }


    /*
     * Guarda (o quita, con null) la ruta de la foto de perfil.
     */
    public function actualizarFoto($id, $ruta){

        $columnas = $this->columnasPerfil();

        if(empty($columnas['foto_perfil'])){
            return false;
        }

        try{

            $stmt = $this->conexion->prepare(
                "UPDATE usuarios
                 SET foto_perfil = ?
                 WHERE id = ?"
            );

            if(!$stmt){
                return false;
            }

            $stmt->bind_param("si", $ruta, $id);

            $resultado = $stmt->execute();

            $stmt->close();

            return $resultado;

        }catch(Throwable $e){
            return false;
        }
    }

}
