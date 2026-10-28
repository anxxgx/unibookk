<?php

$mensajeIntercambio = $_GET['intercambio'] ?? '';

?>

<?php include "views/partials/navbar.php"; ?>

<?php include "views/partials/intercambios_ayudas.php"; ?>

<link rel="stylesheet" href="assets/css/ub-intercambios.css">

<?php

/* Solicitudes enviadas: mismos datos de siempre ($intercambiosEnviados). */
$intercambiosEnviados = $ixListaEnviados;

$ixPendientes = 0;

foreach ($intercambiosEnviados as $ixFila) {
    if (strtolower(trim((string)($ixFila['estado'] ?? 'pendiente'))) === 'pendiente') {
        $ixPendientes++;
    }
}

$ixActiva = 'enviados';
$ixModo   = 'enviados';

?>

<section class="ix">

    <?php include "views/partials/intercambios_cabecera.php"; ?>


    <!-- ===================== MENSAJES ===================== -->

    <?php if (isset($_GET['intercambio']) && $_GET['intercambio'] === 'creado'): ?>

        <div class="ix-alert ix-alert--success ix-rise" style="--i:1" role="status">
            <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
            <div>
                <strong>¡Solicitud enviada!</strong>
                La solicitud de intercambio fue enviada correctamente.
            </div>
        </div>

    <?php endif; ?>


    <?php if (empty($intercambiosEnviados)): ?>


        <!-- ===================== ESTADO VACÍO ===================== -->

        <div class="ix-empty ix-rise" style="--i:2">

            <div class="ix-empty__art" aria-hidden="true">
                <span class="ix-empty__ring ix-empty__ring--outer"></span>
                <span class="ix-empty__ring ix-empty__ring--inner"></span>
                <span class="ix-empty__icon"><i class="bi bi-send"></i></span>
            </div>

            <h2>Aún no tienes solicitudes</h2>

            <p>
                Cuando solicites un intercambio, aparecerá aquí
                con su estado y el libro que ofreciste.
            </p>

            <a href="index.php?accion=catalogo" class="ix-btn ix-btn--primary">
                Ver catálogo
            </a>

        </div>


    <?php else: ?>


        <!-- ===================== SOLICITUDES ===================== -->

        <div class="ix-listhead ix-rise" style="--i:2">

            <h2>Solicitudes enviadas</h2>

            <p>
                <strong><?= (int)$ixTotalEnviados ?></strong>
                <?= $ixTotalEnviados === 1 ? 'solicitud' : 'solicitudes' ?>
                <?php if ($ixPendientes > 0): ?>
                    · <strong class="ix-listhead__alert"><?= (int)$ixPendientes ?></strong>
                    esperando respuesta
                <?php endif; ?>
            </p>

        </div>

        <ul class="ix-list">

            <?php $ixPos = 0; foreach ($intercambiosEnviados as $intercambio): ?>

                <?php $ixPos++; include "views/partials/intercambios_tarjeta.php"; ?>

            <?php endforeach; ?>

        </ul>


    <?php endif; ?>

</section>

<?php include "views/partials/footer.php"; ?>
