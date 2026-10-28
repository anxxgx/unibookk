<?php

$mensajeIntercambio = $_GET['intercambio'] ?? '';

?>

<?php include "views/partials/navbar.php"; ?>

<?php include "views/partials/intercambios_ayudas.php"; ?>

<link rel="stylesheet" href="assets/css/ub-intercambios.css">

<?php

/* Solicitudes recibidas: mismos datos de siempre ($intercambiosRecibidos). */
$intercambiosRecibidos = $ixListaRecibidos;

$ixPendientes = 0;

foreach ($intercambiosRecibidos as $ixFila) {
    if (strtolower(trim((string)($ixFila['estado'] ?? 'pendiente'))) === 'pendiente') {
        $ixPendientes++;
    }
}

$ixActiva = 'recibidos';
$ixModo   = 'recibidos';

?>

<section class="ix">

    <?php include "views/partials/intercambios_cabecera.php"; ?>


    <!-- ===================== MENSAJES ===================== -->

    <?php if ($mensajeIntercambio === 'aceptado'): ?>

        <div class="ix-alert ix-alert--success ix-rise" style="--i:1" role="status">
            <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
            <div>
                <strong>¡Intercambio aceptado!</strong>
                La solicitud fue aceptada correctamente.
            </div>
        </div>

    <?php elseif ($mensajeIntercambio === 'rechazado'): ?>

        <div class="ix-alert ix-alert--warning ix-rise" style="--i:1" role="status">
            <i class="bi bi-arrow-return-left" aria-hidden="true"></i>
            <div>
                <strong>Solicitud rechazada</strong>
                La solicitud de intercambio fue rechazada.
            </div>
        </div>

    <?php elseif ($mensajeIntercambio === 'error'): ?>

        <div class="ix-alert ix-alert--danger ix-rise" style="--i:1" role="alert">
            <i class="bi bi-x-circle-fill" aria-hidden="true"></i>
            <div>
                <strong>No se pudo procesar la solicitud.</strong>
                Es posible que la solicitud ya haya sido procesada.
            </div>
        </div>

    <?php elseif ($mensajeIntercambio === 'creado'): ?>

        <div class="ix-alert ix-alert--success ix-rise" style="--i:1" role="status">
            <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
            <div>
                <strong>Solicitud enviada correctamente.</strong>
            </div>
        </div>

    <?php endif; ?>


    <?php if (empty($intercambiosRecibidos)): ?>


        <!-- ===================== ESTADO VACÍO ===================== -->

        <div class="ix-empty ix-rise" style="--i:2">

            <div class="ix-empty__art" aria-hidden="true">
                <span class="ix-empty__ring ix-empty__ring--outer"></span>
                <span class="ix-empty__ring ix-empty__ring--inner"></span>
                <span class="ix-empty__icon"><i class="bi bi-inbox"></i></span>
            </div>

            <h2>No tienes solicitudes todavía</h2>

            <p>
                Cuando otro estudiante quiera intercambiar
                uno de tus libros, su propuesta aparecerá aquí.
            </p>

            <a href="index.php?accion=catalogo" class="ix-btn ix-btn--primary">
                Explorar libros
            </a>

        </div>


    <?php else: ?>


        <!-- ===================== SOLICITUDES ===================== -->

        <div class="ix-listhead ix-rise" style="--i:2">

            <h2>Solicitudes recibidas</h2>

            <p>
                <strong><?= (int)$ixTotalRecibidos ?></strong>
                <?= $ixTotalRecibidos === 1 ? 'solicitud' : 'solicitudes' ?>
                <?php if ($ixPendientes > 0): ?>
                    · <strong class="ix-listhead__alert"><?= (int)$ixPendientes ?></strong>
                    <?= $ixPendientes === 1 ? 'pendiente de respuesta' : 'pendientes de respuesta' ?>
                <?php endif; ?>
            </p>

        </div>

        <ul class="ix-list">

            <?php $ixPos = 0; foreach ($intercambiosRecibidos as $intercambio): ?>

                <?php $ixPos++; include "views/partials/intercambios_tarjeta.php"; ?>

            <?php endforeach; ?>

        </ul>


    <?php endif; ?>

</section>

<?php include "views/partials/footer.php"; ?>
