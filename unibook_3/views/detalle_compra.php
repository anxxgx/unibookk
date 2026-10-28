<?php

if(session_status() === PHP_SESSION_NONE){
    session_start();
}

?>

<?php include "views/partials/navbar.php"; ?>


<div class="ub-section">

    <div class="container" style="max-width:900px;">


        <?php if(!$detalleCompra): ?>


            <!-- OPERACIÓN NO ENCONTRADA -->

            <div class="catalog-empty reveal">

                <div class="catalog-empty__icon">
                    <i class="bi bi-receipt ub-icon" aria-hidden="true"></i>
                </div>

                <h5 class="fw-bold mb-1">
                    No encontramos esta operación
                </h5>

                <p class="text-muted mb-4">
                    La operación no existe o no tienes permiso
                    para consultarla.
                </p>

                <a
                    href="index.php?accion=catalogo"
                    class="btn-ub btn-ub-dark"
                >
                    ← Volver al catálogo
                </a>

            </div>


        <?php else: ?>


            <?php

            /*
             * Detectamos si el usuario actual es comprador
             * o vendedor.
             */

            $usuarioActual =
                (int)$_SESSION['id_usuario'];

            $esComprador =
                $usuarioActual ===
                (int)$detalleCompra['comprador_id'];


            $esVendedor =
                $usuarioActual ===
                (int)$detalleCompra['vendedor_id'];


            /*
             * Método de pago
             */

            $metodosPago = [

                'pse' =>
                    'PSE',

                'nequi' =>
                    'Nequi',

                'bancolombia' =>
                    'Bancolombia',

                'daviplata' =>
                    'Daviplata'

            ];


            $metodoLabel =
                $metodosPago[
                    strtolower(
                        $detalleCompra['metodo_pago']
                    )
                ]
                ??
                ucfirst(
                    $detalleCompra['metodo_pago']
                );


            /*
             * Estado de la compra
             */

            $estado =
                strtolower(
                    $detalleCompra['estado'] ?? 'pendiente'
                );


            switch($estado){

                case 'pendiente':

                    $estadoTexto =
                        'Pendiente';

                    $estadoClase =
                        'badge-compra-pendiente';

                break;


                case 'confirmada':
                case 'completada':

                    $estadoTexto =
                        'Confirmada';

                    $estadoClase =
                        'badge-compra-confirmada';

                break;


                case 'cancelada':
                case 'rechazada':

                    $estadoTexto =
                        'Cancelada';

                    $estadoClase =
                        'badge-compra-cancelada';

                break;


                default:

                    $estadoTexto =
                        ucfirst($estado);

                    $estadoClase =
                        'badge-compra-pendiente';

                break;

            }


            /*
             * Estado actual del libro
             */

            $estadoLibro =
                strtolower(
                    $detalleCompra['estado_disponibilidad']
                    ?? 'reservado'
                );


            switch($estadoLibro){

                case 'disponible':

                    $estadoLibroTexto =
                        'Disponible';

                    $estadoLibroClase =
                        'text-bg-success';

                break;


                case 'reservado':

                    $estadoLibroTexto =
                        'Reservado';

                    $estadoLibroClase =
                        'text-bg-warning';

                break;


                case 'vendido':

                    $estadoLibroTexto =
                        'Vendido';

                    $estadoLibroClase =
                        'text-bg-dark';

                break;


                case 'intercambiado':

                    $estadoLibroTexto =
                        'Intercambiado';

                    $estadoLibroClase =
                        'text-bg-primary';

                break;


                default:

                    $estadoLibroTexto =
                        ucfirst($estadoLibro);

                    $estadoLibroClase =
                        'text-bg-secondary';

                break;

            }


            /*
             * Fecha
             */

            $fechaCompra =
                !empty($detalleCompra['fecha_compra'])
                ?
                date(
                    'd/m/Y H:i',
                    strtotime(
                        $detalleCompra['fecha_compra']
                    )
                )
                :
                'No disponible';


            /*
             * Nombres
             */

            $nombreComprador =
                trim(
                    $detalleCompra['nombre_comprador']
                    . ' '
                    . $detalleCompra['apellido_comprador']
                );


            $nombreVendedor =
                trim(
                    $detalleCompra['nombre_vendedor']
                    . ' '
                    . $detalleCompra['apellido_vendedor']
                );

            ?>


            <!-- MENSAJES DE RESULTADO -->

            <?php if(isset($_GET['venta'])): ?>

                <?php

                $mensajeVenta = '';
                $claseMensaje = '';

                switch($_GET['venta']){

                    case 'confirmada':

                        $mensajeVenta =
                            'La venta fue confirmada correctamente. El libro ahora aparece como vendido.';

                        $claseMensaje =
                            'alert-success';

                    break;


                    case 'cancelada':

                        $mensajeVenta =
                            'La operación fue cancelada y el libro volvió a estar disponible.';

                        $claseMensaje =
                            'alert-warning';

                    break;


                    case 'estado':

                        $mensajeVenta =
                            'Esta operación ya no está pendiente y no puede modificarse.';

                        $claseMensaje =
                            'alert-warning';

                    break;


                    case 'no_autorizado':

                        $mensajeVenta =
                            'No tienes permiso para modificar esta operación.';

                        $claseMensaje =
                            'alert-danger';

                    break;


                    case 'libro':

                        $mensajeVenta =
                            'No fue posible actualizar el estado del libro. La operación no fue modificada.';

                        $claseMensaje =
                            'alert-danger';

                    break;


                    case 'compra':

                        $mensajeVenta =
                            'No fue posible actualizar la operación.';

                        $claseMensaje =
                            'alert-danger';

                    break;


                    default:

                        $mensajeVenta =
                            'No fue posible realizar esta acción.';

                        $claseMensaje =
                            'alert-danger';

                    break;

                }

                ?>


                <?php if($mensajeVenta): ?>

                    <div
                        class="alert <?= $claseMensaje ?> border-0 rounded-4 reveal mb-4"
                    >

                        <?= htmlspecialchars($mensajeVenta) ?>

                    </div>

                <?php endif; ?>

            <?php endif; ?>



            <!-- ENCABEZADO -->

            <div class="text-center reveal mb-4">

                <div class="confirm-icon">
                    <i class="bi bi-receipt ub-icon" aria-hidden="true"></i>
                </div>

                <p
                    class="text-uppercase fw-semibold text-muted mb-2"
                    style="font-size:12px; letter-spacing:1.5px;"
                >
                    Detalle de operación
                </p>

                <h1 class="fw-bold mb-2">
                    Operación #<?= (int)$detalleCompra['id'] ?>
                </h1>

                <p class="text-muted mb-0">
                    Consulta toda la información de esta operación.
                </p>

            </div>



            <!-- AVISO -->

            <div
                class="alert border-0 rounded-4 reveal mb-4"
                style="
                    background:#fff8e7;
                    color:#765b16;
                "
            >

                <strong><i class="bi bi-exclamation-triangle ub-icon" aria-hidden="true"></i> Proceso simulado</strong>

                <div
                    class="mt-1"
                    style="font-size:14px;"
                >
                    Esta operación forma parte de la simulación
                    académica de UniBook. No se realizó ningún
                    cobro real.
                </div>

            </div>



            <!-- AVISO PARA EL VENDEDOR -->

            <?php if($esVendedor && $estado === 'pendiente'): ?>

                <div
                    class="alert border-0 rounded-4 reveal mb-4"
                    style="
                        background:#eef6ff;
                        color:#174a78;
                    "
                >

                    <strong><i class="bi bi-hand-index-thumb ub-icon" aria-hidden="true"></i> Tienes una venta pendiente</strong>

                    <div
                        class="mt-1"
                        style="font-size:14px;"
                    >
                        Un estudiante quiere comprar este libro.
                        Revisa la información y decide si deseas
                        confirmar o rechazar la operación.
                    </div>

                </div>

            <?php endif; ?>



            <!-- AVISO PARA EL COMPRADOR -->

            <?php if($esComprador && $estado === 'pendiente'): ?>

                <div
                    class="alert border-0 rounded-4 reveal mb-4"
                    style="
                        background:#eef6ff;
                        color:#174a78;
                    "
                >

                    <strong>⏳ Esperando al vendedor</strong>

                    <div
                        class="mt-1"
                        style="font-size:14px;"
                    >
                        Tu compra fue registrada y el libro está
                        reservado. El vendedor debe confirmar la
                        operación.
                    </div>

                </div>

            <?php endif; ?>



            <!-- TARJETA PRINCIPAL -->

            <div class="checkout-summary reveal mb-4">


                <!-- PORTADA -->

                <div class="checkout-summary__thumb">

                    <?php if(!empty($detalleCompra['imagen'])): ?>

                        <img
                            src="<?= htmlspecialchars(
                                $detalleCompra['imagen']
                            ) ?>"
                            alt="Portada del libro"
                        >

                    <?php else: ?>

                        <span>
                            <i class="bi bi-book ub-icon" aria-hidden="true"></i>
                        </span>

                    <?php endif; ?>

                </div>



                <!-- INFORMACIÓN DEL LIBRO -->

                <div class="checkout-summary__info flex-grow-1">

                    <h4>
                        <?= htmlspecialchars(
                            $detalleCompra['titulo']
                        ) ?>
                    </h4>


                    <?php if(!empty($detalleCompra['autor'])): ?>

                        <p>
                            <i class="bi bi-person ub-icon" aria-hidden="true"></i>
                            <?= htmlspecialchars(
                                $detalleCompra['autor']
                            ) ?>
                        </p>

                    <?php endif; ?>


                    <p>
                        <i class="bi bi-receipt ub-icon" aria-hidden="true"></i> Operación:
                        <b>
                            #<?= (int)$detalleCompra['id'] ?>
                        </b>
                    </p>


                    <p>
                        <i class="bi bi-calendar-event ub-icon" aria-hidden="true"></i> Fecha:
                        <b>
                            <?= htmlspecialchars(
                                $fechaCompra
                            ) ?>
                        </b>
                    </p>

                </div>



                <!-- PRECIO -->

                <div class="checkout-summary__price">

                    $
                    <?= number_format(
                        (float)$detalleCompra['precio'],
                        0,
                        ',',
                        '.'
                    ) ?>

                </div>

            </div>



            <!-- INFORMACIÓN -->

            <div class="row g-4 reveal">


                <!-- ESTADO -->

                <div class="col-12 col-md-6">

                    <div
                        class="card border-0 shadow-sm rounded-4 h-100"
                    >

                        <div class="card-body p-4">

                            <p
                                class="text-muted text-uppercase fw-semibold mb-2"
                                style="
                                    font-size:11px;
                                    letter-spacing:1px;
                                "
                            >
                                Estado de la operación
                            </p>

                            <div class="mb-3">

                                <span
                                    class="stamp <?= $estadoClase ?>"
                                >
                                    <?= htmlspecialchars(
                                        $estadoTexto
                                    ) ?>
                                </span>

                            </div>

                            <p
                                class="text-muted mb-0"
                                style="font-size:14px;"
                            >

                                La operación está actualmente
                                registrada como
                                <strong>
                                    <?= htmlspecialchars(
                                        strtolower($estadoTexto)
                                    ) ?>
                                </strong>.

                            </p>

                        </div>

                    </div>

                </div>



                <!-- MÉTODO DE PAGO -->

                <div class="col-12 col-md-6">

                    <div
                        class="card border-0 shadow-sm rounded-4 h-100"
                    >

                        <div class="card-body p-4">

                            <p
                                class="text-muted text-uppercase fw-semibold mb-2"
                                style="
                                    font-size:11px;
                                    letter-spacing:1px;
                                "
                            >
                                Método de pago
                            </p>

                            <h4 class="fw-bold mb-2">

                                <i class="bi bi-credit-card ub-icon" aria-hidden="true"></i>
                                <?= htmlspecialchars(
                                    $metodoLabel
                                ) ?>

                            </h4>

                            <p
                                class="text-muted mb-0"
                                style="font-size:14px;"
                            >
                                Método seleccionado durante
                                el proceso de compra simulada.
                            </p>

                        </div>

                    </div>

                </div>



                <!-- COMPRADOR -->

                <div class="col-12 col-md-6">

                    <div
                        class="card border-0 shadow-sm rounded-4 h-100"
                    >

                        <div class="card-body p-4">

                            <p
                                class="text-muted text-uppercase fw-semibold mb-2"
                                style="
                                    font-size:11px;
                                    letter-spacing:1px;
                                "
                            >
                                Comprador
                            </p>

                            <h5 class="fw-bold mb-2">

                                <i class="bi bi-person-circle ub-icon" aria-hidden="true"></i>
                                <?= htmlspecialchars(
                                    $nombreComprador
                                ) ?>

                            </h5>


                            <?php if($esComprador): ?>

                                <span
                                    class="badge rounded-pill text-bg-light border"
                                >
                                    Tú
                                </span>

                            <?php endif; ?>

                        </div>

                    </div>

                </div>



                <!-- VENDEDOR -->

                <div class="col-12 col-md-6">

                    <div
                        class="card border-0 shadow-sm rounded-4 h-100"
                    >

                        <div class="card-body p-4">

                            <p
                                class="text-muted text-uppercase fw-semibold mb-2"
                                style="
                                    font-size:11px;
                                    letter-spacing:1px;
                                "
                            >
                                Vendedor
                            </p>

                            <h5 class="fw-bold mb-2">

                                <i class="bi bi-person-circle ub-icon" aria-hidden="true"></i>
                                <?= htmlspecialchars(
                                    $nombreVendedor
                                ) ?>

                            </h5>


                            <?php if($esVendedor): ?>

                                <span
                                    class="badge rounded-pill text-bg-light border"
                                >
                                    Tú
                                </span>

                            <?php endif; ?>

                        </div>

                    </div>

                </div>

            </div>



            <!-- ESTADO DEL LIBRO -->

            <div
                class="card border-0 shadow-sm rounded-4 mt-4 reveal"
            >

                <div class="card-body p-4">

                    <div
                        class="d-flex align-items-center justify-content-between flex-wrap gap-3"
                    >

                        <div>

                            <p
                                class="text-muted text-uppercase fw-semibold mb-1"
                                style="
                                    font-size:11px;
                                    letter-spacing:1px;
                                "
                            >
                                Estado del libro
                            </p>

                            <h5 class="fw-bold mb-1">

                                <?php if($estadoLibro === 'vendido'): ?>

                                    <i class="bi bi-box-seam ub-icon" aria-hidden="true"></i> Vendido

                                <?php elseif($estadoLibro === 'disponible'): ?>

                                    <i class="bi bi-bookshelf ub-icon" aria-hidden="true"></i> Disponible

                                <?php elseif($estadoLibro === 'intercambiado'): ?>

                                    <i class="bi bi-arrow-left-right ub-icon" aria-hidden="true"></i> Intercambiado

                                <?php else: ?>

                                    <i class="bi bi-box-seam ub-icon" aria-hidden="true"></i> Reservado

                                <?php endif; ?>

                            </h5>

                            <p
                                class="text-muted mb-0"
                                style="font-size:14px;"
                            >

                                <?php if($estadoLibro === 'vendido'): ?>

                                    La venta fue confirmada y el libro
                                    ya no está disponible para nuevas compras.

                                <?php elseif($estadoLibro === 'disponible'): ?>

                                    El libro está nuevamente disponible
                                    para otros estudiantes.

                                <?php elseif($estadoLibro === 'intercambiado'): ?>

                                    El libro fue marcado como intercambiado.

                                <?php else: ?>

                                    El libro está reservado mientras
                                    la operación se encuentra pendiente.

                                <?php endif; ?>

                            </p>

                        </div>


                        <span
                            class="badge rounded-pill <?= $estadoLibroClase ?> px-3 py-2"
                        >
                            <?= htmlspecialchars($estadoLibroTexto) ?>
                        </span>

                    </div>

                </div>

            </div>



            <!-- ACCIONES DEL VENDEDOR -->

            <?php if($esVendedor && $estado === 'pendiente'): ?>

                <div
                    class="card border-0 shadow-sm rounded-4 mt-4 reveal"
                >

                    <div class="card-body p-4 text-center">

                        <p
                            class="text-muted text-uppercase fw-semibold mb-2"
                            style="
                                font-size:11px;
                                letter-spacing:1px;
                            "
                        >
                            Gestionar venta
                        </p>

                        <h5 class="fw-bold mb-2">
                            ¿Qué deseas hacer con esta operación?
                        </h5>

                        <p
                            class="text-muted mb-4"
                            style="font-size:14px;"
                        >
                            Al confirmar, el libro pasará a vendido.
                            Si rechazas la operación, volverá a estar disponible.
                        </p>


                        <div
                            class="d-flex gap-3 flex-wrap justify-content-center"
                        >

                            <!-- CONFIRMAR -->

                            <form
                                method="POST"
                                action="index.php?accion=detalle_compra&id=<?= (int)$detalleCompra['id'] ?>"
                            >

                                <input
                                    type="hidden"
                                    name="compra_id"
                                    value="<?= (int)$detalleCompra['id'] ?>"
                                >

                                <button
                                    type="submit"
                                    name="aceptar_venta"
                                    class="btn-ub btn-ub-gold"
                                    onclick="return confirm('¿Confirmar esta venta? El libro pasará a estado vendido.');"
                                >
                                    <i class="bi bi-check-lg ub-icon" aria-hidden="true"></i> Confirmar venta
                                </button>

                            </form>


                            <!-- RECHAZAR -->

                            <form
                                method="POST"
                                action="index.php?accion=detalle_compra&id=<?= (int)$detalleCompra['id'] ?>"
                            >

                                <input
                                    type="hidden"
                                    name="compra_id"
                                    value="<?= (int)$detalleCompra['id'] ?>"
                                >

                                <button
                                    type="submit"
                                    name="rechazar_venta"
                                    class="btn-ub btn-ub-outline"
                                    onclick="return confirm('¿Rechazar esta operación? El libro volverá a estar disponible.');"
                                >
                                    <i class="bi bi-x-lg ub-icon" aria-hidden="true"></i> Rechazar venta
                                </button>

                            </form>

                        </div>

                    </div>

                </div>

            <?php endif; ?>



            <!-- BOTONES -->

            <div
                class="d-flex gap-3 flex-wrap justify-content-center mt-5 reveal"
            >

                <?php if($esComprador): ?>

                    <a
                        href="index.php?accion=mis_compras"
                        class="btn-ub btn-ub-outline"
                    >
                        ← Mis compras
                    </a>

                <?php elseif($esVendedor): ?>

                    <a
                        href="index.php?accion=mis_ventas"
                        class="btn-ub btn-ub-outline"
                    >
                        ← Mis ventas
                    </a>

                <?php endif; ?>


                <a
                    href="index.php?accion=detalle_libro&id=<?= (int)$detalleCompra['libro_id'] ?>"
                    class="btn-ub btn-ub-gold"
                >
                    Ver el libro
                </a>

            </div>


        <?php endif; ?>

    </div>

</div>


<?php include "views/partials/footer.php"; ?>
