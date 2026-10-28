<?php

class Compra
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }


    /*
    ============================================================
    CREAR COMPRA
    ============================================================
    */

    public function crear(
        $libro_id,
        $comprador_id,
        $vendedor_id,
        $precio,
        $metodo_pago
    ) {

        $sql = "INSERT INTO compras
                (
                    libro_id,
                    comprador_id,
                    vendedor_id,
                    precio,
                    metodo_pago,
                    estado
                )
                VALUES (?, ?, ?, ?, ?, 'pendiente')";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param(
            "iiids",
            $libro_id,
            $comprador_id,
            $vendedor_id,
            $precio,
            $metodo_pago
        );

        $resultado = $stmt->execute();

        $id = $this->conexion->insert_id;

        $stmt->close();

        return $resultado ? $id : false;
    }


    /*
    ============================================================
    OBTENER DETALLE DE COMPRA
    ============================================================
    */

    public function obtenerDetalleParaUsuario(
        $compra_id,
        $usuario_id
    ) {

        $sql = "SELECT
                    c.*,

                    l.titulo,
                    l.autor,
                    l.imagen,
                    l.descripcion,
                    l.tipo_publicacion,
                    l.estado_disponibilidad,

                    comprador.nombre AS nombre_comprador,
                    comprador.apellido AS apellido_comprador,
                    comprador.correo AS correo_comprador,

                    vendedor.nombre AS nombre_vendedor,
                    vendedor.apellido AS apellido_vendedor,
                    vendedor.correo AS correo_vendedor

                FROM compras c

                INNER JOIN libros l
                    ON l.id = c.libro_id

                INNER JOIN usuarios comprador
                    ON comprador.id = c.comprador_id

                INNER JOIN usuarios vendedor
                    ON vendedor.id = c.vendedor_id

                WHERE c.id = ?

                AND (
                    c.comprador_id = ?
                    OR c.vendedor_id = ?
                )

                LIMIT 1";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            return null;
        }

        $stmt->bind_param(
            "iii",
            $compra_id,
            $usuario_id,
            $usuario_id
        );

        $stmt->execute();

        $resultado = $stmt->get_result();

        $compra = $resultado->fetch_assoc();

        $stmt->close();

        return $compra;
    }


    /*
    ============================================================
    MIS COMPRAS
    ============================================================
    */

    public function obtenerPorComprador($usuario_id)
    {

        $sql = "SELECT
                    c.*,

                    l.titulo,
                    l.autor,
                    l.imagen,
                    l.tipo_publicacion,
                    l.estado_disponibilidad,

                    vendedor.nombre AS nombre_vendedor,
                    vendedor.apellido AS apellido_vendedor

                FROM compras c

                INNER JOIN libros l
                    ON l.id = c.libro_id

                INNER JOIN usuarios vendedor
                    ON vendedor.id = c.vendedor_id

                WHERE c.comprador_id = ?

                ORDER BY c.fecha_compra DESC";

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

        $compras = [];

        while ($fila = $resultado->fetch_assoc()) {
            $compras[] = $fila;
        }

        $stmt->close();

        return $compras;
    }


    /*
    ============================================================
    MIS VENTAS
    ============================================================
    */

    public function obtenerPorVendedor($usuario_id)
    {

        $sql = "SELECT
                    c.*,

                    l.titulo,
                    l.autor,
                    l.imagen,
                    l.tipo_publicacion,
                    l.estado_disponibilidad,

                    comprador.nombre AS nombre_comprador,
                    comprador.apellido AS apellido_comprador

                FROM compras c

                INNER JOIN libros l
                    ON l.id = c.libro_id

                INNER JOIN usuarios comprador
                    ON comprador.id = c.comprador_id

                WHERE c.vendedor_id = ?

                ORDER BY c.fecha_compra DESC";

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

        $ventas = [];

        while ($fila = $resultado->fetch_assoc()) {
            $ventas[] = $fila;
        }

        $stmt->close();

        return $ventas;
    }


    /*
    ============================================================
    PANEL DE VENTAS (SOLO LECTURA)
    ============================================================

    Alimenta el tablero de "Mis ventas". No modifica datos.

    obtenerOperacionesVendedor():
      Devuelve las operaciones del vendedor (siempre filtradas
      por vendedor_id) con una sola consulta preparada.
      - $dias_atras = null  -> todas las operaciones.
      - $dias_atras = N     -> las que tienen fecha_compra desde
                               el inicio del día de hoy menos N
                               días (CURDATE() de MySQL, la misma
                               hora con la que se guardan las
                               fechas).
      Usa los mismos JOIN que obtenerPorVendedor() y que el
      detalle de la operación, de modo que cada fila listada tiene
      un detalle consultable.
    */

    public function obtenerOperacionesVendedor(
        $vendedor_id,
        $dias_atras = null
    ) {

        $sql = "SELECT
                    c.id,
                    c.libro_id,
                    c.precio,
                    c.metodo_pago,
                    c.estado,
                    c.fecha_compra,
                    c.fecha_actualizacion,

                    l.titulo,
                    l.autor,
                    l.imagen,
                    l.tipo_publicacion,

                    comprador.nombre AS nombre_comprador,
                    comprador.apellido AS apellido_comprador

                FROM compras c

                INNER JOIN libros l
                    ON l.id = c.libro_id

                INNER JOIN usuarios comprador
                    ON comprador.id = c.comprador_id

                WHERE c.vendedor_id = ?";

        if ($dias_atras !== null) {
            $sql .= " AND c.fecha_compra >=
                      DATE_SUB(CURDATE(), INTERVAL ? DAY)";
        }

        $sql .= " ORDER BY c.fecha_compra DESC, c.id DESC";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            return [];
        }

        if ($dias_atras !== null) {

            $vendedor_id = (int)$vendedor_id;
            $dias_atras  = (int)$dias_atras;

            $stmt->bind_param("ii", $vendedor_id, $dias_atras);

        } else {

            $vendedor_id = (int)$vendedor_id;

            $stmt->bind_param("i", $vendedor_id);
        }

        $stmt->execute();

        $resultado = $stmt->get_result();

        $filas = [];

        while ($fila = $resultado->fetch_assoc()) {
            $filas[] = $fila;
        }

        $stmt->close();

        return $filas;
    }


    /*
    Fecha y hora actuales según MySQL. Es la referencia para
    "hoy" en los periodos del tablero y para el indicador de
    actualización, así los periodos y las fechas guardadas
    usan el mismo reloj.
    */

    public function obtenerFechaActualBD()
    {

        $resultado = $this->conexion->query(
            "SELECT
                DATE_FORMAT(CURDATE(), '%Y-%m-%d') AS hoy,
                DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s') AS ahora"
        );

        $fila = $resultado ? $resultado->fetch_assoc() : null;

        if (!$fila) {

            return [
                'hoy'   => date('Y-m-d'),
                'ahora' => date('Y-m-d H:i:s'),
            ];
        }

        return $fila;
    }


    /*
    Cuenta TODAS las operaciones del vendedor (sin periodo). Se usa
    solo para distinguir "aún no tienes ventas" de "no hay ventas
    en este periodo".
    */

    public function contarOperacionesVendedor($vendedor_id)
    {

        $sql = "SELECT COUNT(*) AS total
                FROM compras c
                INNER JOIN libros l
                    ON l.id = c.libro_id
                INNER JOIN usuarios comprador
                    ON comprador.id = c.comprador_id
                WHERE c.vendedor_id = ?";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            return 0;
        }

        $vendedor_id = (int)$vendedor_id;

        $stmt->bind_param("i", $vendedor_id);

        $stmt->execute();

        $fila = $stmt->get_result()->fetch_assoc();

        $stmt->close();

        return (int)($fila['total'] ?? 0);
    }


    /*
    ============================================================
    CONFIRMAR VENTA
    ============================================================

    Cuando el vendedor acepta:

    1. La compra pasa a confirmada.
    2. El libro pasa a vendido.
    3. El libro deja de estar disponible en catálogo.
    */

    public function confirmarVenta(
        $compra_id,
        $vendedor_id
    ) {

        $this->conexion->begin_transaction();

        try {

            /*
            --------------------------------------------
            BUSCAR LA COMPRA
            --------------------------------------------
            */

            $sql = "SELECT
                        id,
                        libro_id
                    FROM compras
                    WHERE id = ?
                    AND vendedor_id = ?
                    AND estado = 'pendiente'
                    LIMIT 1";

            $stmt = $this->conexion->prepare($sql);

            if (!$stmt) {
                throw new Exception("Error preparando compra.");
            }

            $stmt->bind_param(
                "ii",
                $compra_id,
                $vendedor_id
            );

            $stmt->execute();

            $resultado = $stmt->get_result();

            $compra = $resultado->fetch_assoc();

            $stmt->close();


            if (!$compra) {

                throw new Exception(
                    "La compra ya no está pendiente."
                );
            }


            $libro_id = (int)$compra['libro_id'];


            /*
            --------------------------------------------
            CAMBIAR LIBRO A VENDIDO
            --------------------------------------------
            */

            $sql = "UPDATE libros
                    SET estado_disponibilidad = 'vendido'
                    WHERE id = ?
                    AND estado_disponibilidad = 'reservado'";

            $stmt = $this->conexion->prepare($sql);

            if (!$stmt) {
                throw new Exception(
                    "Error preparando actualización del libro."
                );
            }

            $stmt->bind_param(
                "i",
                $libro_id
            );

            $stmt->execute();

            if ($stmt->affected_rows <= 0) {

                $stmt->close();

                throw new Exception(
                    "El libro ya no está reservado."
                );
            }

            $stmt->close();


            /*
            --------------------------------------------
            CONFIRMAR COMPRA
            --------------------------------------------
            */

            $sql = "UPDATE compras

                    SET
                        estado = 'confirmada',
                        fecha_actualizacion = NOW()

                    WHERE id = ?
                    AND vendedor_id = ?
                    AND estado = 'pendiente'";

            $stmt = $this->conexion->prepare($sql);

            if (!$stmt) {
                throw new Exception(
                    "Error preparando confirmación."
                );
            }

            $stmt->bind_param(
                "ii",
                $compra_id,
                $vendedor_id
            );

            $stmt->execute();

            if ($stmt->affected_rows <= 0) {

                $stmt->close();

                throw new Exception(
                    "No se pudo confirmar la compra."
                );
            }

            $stmt->close();


            /*
            --------------------------------------------
            CONFIRMAR TODO
            --------------------------------------------
            */

            $this->conexion->commit();

            return true;

        } catch (Exception $e) {

            $this->conexion->rollback();

            return false;
        }
    }


    /*
    ============================================================
    CANCELAR / RECHAZAR VENTA
    ============================================================

    Si el vendedor rechaza:

    1. La compra pasa a cancelada.
    2. El libro vuelve a disponible.
    3. Puede aparecer otra vez en el catálogo.
    */

    public function cancelarVenta(
        $compra_id,
        $vendedor_id
    ) {

        $this->conexion->begin_transaction();

        try {

            /*
            --------------------------------------------
            BUSCAR LA COMPRA
            --------------------------------------------
            */

            $sql = "SELECT
                        id,
                        libro_id
                    FROM compras
                    WHERE id = ?
                    AND vendedor_id = ?
                    AND estado = 'pendiente'
                    LIMIT 1";

            $stmt = $this->conexion->prepare($sql);

            if (!$stmt) {
                throw new Exception(
                    "Error preparando compra."
                );
            }

            $stmt->bind_param(
                "ii",
                $compra_id,
                $vendedor_id
            );

            $stmt->execute();

            $resultado = $stmt->get_result();

            $compra = $resultado->fetch_assoc();

            $stmt->close();


            if (!$compra) {

                throw new Exception(
                    "La compra ya no está pendiente."
                );
            }


            $libro_id = (int)$compra['libro_id'];


            /*
            --------------------------------------------
            DEVOLVER LIBRO A DISPONIBLE
            --------------------------------------------
            */

            $sql = "UPDATE libros
                    SET estado_disponibilidad = 'disponible'
                    WHERE id = ?
                    AND estado_disponibilidad = 'reservado'";

            $stmt = $this->conexion->prepare($sql);

            if (!$stmt) {
                throw new Exception(
                    "Error preparando devolución del libro."
                );
            }

            $stmt->bind_param(
                "i",
                $libro_id
            );

            $stmt->execute();

            $stmt->close();


            /*
            --------------------------------------------
            CANCELAR COMPRA
            --------------------------------------------
            */

            $sql = "UPDATE compras

                    SET
                        estado = 'cancelada',
                        fecha_actualizacion = NOW()

                    WHERE id = ?
                    AND vendedor_id = ?
                    AND estado = 'pendiente'";

            $stmt = $this->conexion->prepare($sql);

            if (!$stmt) {
                throw new Exception(
                    "Error preparando cancelación."
                );
            }

            $stmt->bind_param(
                "ii",
                $compra_id,
                $vendedor_id
            );

            $stmt->execute();

            if ($stmt->affected_rows <= 0) {

                $stmt->close();

                throw new Exception(
                    "No se pudo cancelar la compra."
                );
            }

            $stmt->close();


            /*
            --------------------------------------------
            CONFIRMAR CAMBIOS
            --------------------------------------------
            */

            $this->conexion->commit();

            return true;

        } catch (Exception $e) {

            $this->conexion->rollback();

            return false;
        }
    }
}
