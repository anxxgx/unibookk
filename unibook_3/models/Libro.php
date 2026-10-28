<?php

class Libro
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }


    public function publicar(
        $titulo,
        $autor,
        $editorial,
        $universidad,
        $carrera,
        $semestre,
        $materia,
        $descripcion,
        $precio,
        $estado,
        $tipo_publicacion,
        $imagen,
        $usuario_id
    ){
        $sql = "INSERT INTO libros
        (
            titulo,
            autor,
            editorial,
            universidad,
            carrera,
            semestre,
            materia,
            descripcion,
            precio,
            estado,
            tipo_publicacion,
            imagen,
            usuario_id
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->conexion->prepare($sql);

        if(!$stmt){
            return false;
        }

        $stmt->bind_param(
            "ssssssssdsssi",
            $titulo,
            $autor,
            $editorial,
            $universidad,
            $carrera,
            $semestre,
            $materia,
            $descripcion,
            $precio,
            $estado,
            $tipo_publicacion,
            $imagen,
            $usuario_id
        );

        $resultado = $stmt->execute();
        $afectadas = $stmt->affected_rows;

        $stmt->close();

        return $resultado && $afectadas > 0;
    }


    public function obtenerTodos()
    {
        $sql = "SELECT *
                FROM libros
                ORDER BY titulo ASC, id ASC";

        return $this->conexion->query($sql);
    }


    public function obtenerUltimosIngresados($limite = 4)
    {
        $limite = max(1, (int)$limite);

        $sql = "SELECT *
                FROM libros
                ORDER BY fecha_publicacion DESC, id DESC
                LIMIT " . $limite;

        $resultado = $this->conexion->query($sql);

        if (!$resultado) {
            return [];
        }

        return $resultado->fetch_all(MYSQLI_ASSOC);
    }


    public function obtenerPorId($id)
    {
        $sql = "SELECT *
                FROM libros
                WHERE id = ?";

        $stmt = $this->conexion->prepare($sql);

        if(!$stmt){
            return null;
        }

        $stmt->bind_param("i", $id);

        $stmt->execute();

        $resultado = $stmt->get_result();

        $libro = $resultado->fetch_assoc();

        $stmt->close();

        return $libro;
    }


    public function obtenerPorUsuario($usuario_id)
    {
        $sql = "SELECT *
                FROM libros
                WHERE usuario_id = ?
                ORDER BY fecha_publicacion DESC";

        $stmt = $this->conexion->prepare($sql);

        if(!$stmt){
            return false;
        }

        $stmt->bind_param("i", $usuario_id);

        $stmt->execute();

        $resultado = $stmt->get_result();

        $stmt->close();

        return $resultado;
    }


    public function eliminar($id, $usuario_id)
    {
        /*
         * Una publicación involucrada en una compra no se borra: el detalle
         * e historial de esa operación deben seguir siendo consultables.
         * Tampoco se permite borrar un libro reservado.
         */
        $sql = "DELETE FROM libros
                WHERE id = ?
                AND usuario_id = ?
                AND estado_disponibilidad = 'disponible'
                AND NOT EXISTS (
                    SELECT 1 FROM compras WHERE compras.libro_id = libros.id
                )";

        $stmt = $this->conexion->prepare($sql);

        if(!$stmt){
            return false;
        }

        $stmt->bind_param("ii", $id, $usuario_id);

        $resultado = $stmt->execute();
        $afectadas = $stmt->affected_rows;

        $stmt->close();

        return $resultado && $afectadas > 0;
    }


    /*
    |--------------------------------------------------------------------------
    | DISPONIBILIDAD
    |--------------------------------------------------------------------------
    */


    public function reservarSiDisponible($id)
    {
        $sql = "UPDATE libros
                SET estado_disponibilidad = 'reservado'
                WHERE id = ?
                AND estado_disponibilidad = 'disponible'";

        $stmt = $this->conexion->prepare($sql);

        if(!$stmt){
            return false;
        }

        $stmt->bind_param("i", $id);

        $stmt->execute();

        $afectadas = $stmt->affected_rows;

        $stmt->close();

        return $afectadas > 0;
    }


    public function marcarDisponible($id)
    {
        $sql = "UPDATE libros
                SET estado_disponibilidad = 'disponible'
                WHERE id = ?";

        $stmt = $this->conexion->prepare($sql);

        if(!$stmt){
            return false;
        }

        $resultado = $stmt->execute();

        $stmt->close();

        return $resultado;
    }


    /*
    |--------------------------------------------------------------------------
    | INTERCAMBIO
    |--------------------------------------------------------------------------
    */


    public function estanReservadosParaIntercambio(
        $libroSolicitadoId,
        $libroOfrecidoId
    ) {
        $sql = "SELECT id, estado_disponibilidad
                FROM libros
                WHERE id IN (?, ?)";

        $stmt = $this->conexion->prepare($sql);

        if(!$stmt){
            return false;
        }

        $stmt->bind_param(
            "ii",
            $libroSolicitadoId,
            $libroOfrecidoId
        );

        $stmt->execute();

        $resultado = $stmt->get_result();

        $libros = [];

        while($libro = $resultado->fetch_assoc()){
            $libros[(int)$libro['id']] =
                $libro['estado_disponibilidad'];
        }

        $stmt->close();

        return (
            isset($libros[$libroSolicitadoId]) &&
            isset($libros[$libroOfrecidoId]) &&
            $libros[$libroSolicitadoId] === 'reservado' &&
            $libros[$libroOfrecidoId] === 'reservado'
        );
    }


    public function marcarIntercambiadoSiReservado($id)
    {
        $sql = "UPDATE libros
                SET estado_disponibilidad = 'intercambiado'
                WHERE id = ?
                AND estado_disponibilidad = 'reservado'";

        $stmt = $this->conexion->prepare($sql);

        if(!$stmt){
            return false;
        }

        $stmt->bind_param("i", $id);

        $stmt->execute();

        $afectadas = $stmt->affected_rows;

        $stmt->close();

        return $afectadas > 0;
    }


    public function marcarDisponibleSiReservado($id)
    {
        $sql = "UPDATE libros
                SET estado_disponibilidad = 'disponible'
                WHERE id = ?
                AND estado_disponibilidad = 'reservado'";

        $stmt = $this->conexion->prepare($sql);

        if(!$stmt){
            return false;
        }

        $stmt->bind_param("i", $id);

        $stmt->execute();

        $afectadas = $stmt->affected_rows;

        $stmt->close();

        return $afectadas > 0;
    }


    /*
    |--------------------------------------------------------------------------
    | VENTA
    |--------------------------------------------------------------------------
    */


    public function marcarVendidoSiReservado($id)
    {
        $sql = "UPDATE libros
                SET estado_disponibilidad = 'vendido'
                WHERE id = ?
                AND estado_disponibilidad = 'reservado'";

        $stmt = $this->conexion->prepare($sql);

        if(!$stmt){
            return false;
        }

        $stmt->bind_param("i", $id);

        $stmt->execute();

        $afectadas = $stmt->affected_rows;

        $stmt->close();

        return $afectadas > 0;
    }


    /*
    |--------------------------------------------------------------------------
    | HISTORIA
    |--------------------------------------------------------------------------
    */


    public function obtenerHistorial()
    {
        $sql = "SELECT *
                FROM libros
                WHERE estado_disponibilidad IN ('vendido', 'intercambiado')
                ORDER BY fecha_publicacion DESC, id DESC";

        $resultado = $this->conexion->query($sql);

        if(!$resultado){
            return [];
        }

        $historial = [];

        while($libro = $resultado->fetch_assoc()){
            $historial[] = $libro;
        }

        return $historial;
    }
}
