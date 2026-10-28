<?php
if(session_status() === PHP_SESSION_NONE){
    session_start();
}
include "views/partials/navbar.php";

/*
|--------------------------------------------------------------------------
| Presentación de las notificaciones (solo formato de lo que ya llega)
|--------------------------------------------------------------------------
*/

$notificaciones = $notificaciones ?? [];
$notificacionesNoLeidas = $notificacionesNoLeidas ?? 0;

$ubNotifDias = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];

/* Tipo -> icono, tono de color y etiqueta (los tipos reales del sistema). */
$ubNotifTipos = [
    'nueva_compra'      => ['bi-bag-check',          'blue',  'Nueva compra'],
    'compra_confirmada' => ['bi-check-circle-fill',  'green', 'Compra confirmada'],
    'compra_cancelada'  => ['bi-x-circle-fill',      'red',   'Compra cancelada'],
    'intercambio'       => ['bi-arrow-left-right',   'gold',  'Intercambio'],
];

/* "Hace 5 min", "Hace 3 h", "Ayer", día de la semana o fecha. */
$ubNotifHora = function ($valor) use ($ubNotifDias) {

    $ts = !empty($valor) ? strtotime($valor) : false;

    if (!$ts) {
        return '';
    }

    $dif = max(0, time() - $ts);
    $hoy = strtotime('today');

    if ($dif < 60) {
        return 'Ahora';
    }

    if ($ts >= $hoy) {
        return $dif < 3600
            ? 'Hace ' . floor($dif / 60) . ' min'
            : 'Hace ' . floor($dif / 3600) . ' h';
    }

    if ($ts >= $hoy - 86400) {
        return 'Ayer';
    }

    if ($ts >= $hoy - 6 * 86400) {
        return $ubNotifDias[(int)date('w', $ts)];
    }

    return date('d/m/Y', $ts);
};

/* Agrupación como en un centro de notificaciones: Hoy, Ayer, Esta semana... */
$ubNotifGrupo = function ($valor) {

    $ts = !empty($valor) ? strtotime($valor) : false;

    if (!$ts) {
        return 'Anteriores';
    }

    $hoy = strtotime('today');

    if ($ts >= $hoy) {
        return 'Hoy';
    }

    if ($ts >= $hoy - 86400) {
        return 'Ayer';
    }

    if ($ts >= $hoy - 6 * 86400) {
        return 'Esta semana';
    }

    return 'Anteriores';
};

$ubNotifGrupoActual = '';
?>
<link rel="stylesheet" href="assets/css/ub-notificaciones.css">

<section class="ub-notif">

    <div class="ub-notif__stage">

        <!-- ENCABEZADO -->
        <header class="ub-notif__head reveal">

            <div>
                <span class="ub-notif__eyebrow">Tu actividad</span>

                <h1>
                    Notificaciones
                    <i class="bi bi-bell ub-icon" aria-hidden="true"></i>
                </h1>

                <p>Aquí podrás ver las novedades de tus compras y ventas.</p>
            </div>

            <?php if(!empty($notificaciones)){ ?>
                <form method="POST" action="index.php?accion=notificaciones">
                    <input
                        type="hidden"
                        name="marcar_todas_notificaciones"
                        value="1"
                    >
                    <button
                        type="submit"
                        class="ub-notif__btn"
                        <?php echo $notificacionesNoLeidas > 0 ? '' : 'disabled'; ?>
                    >
                        <i class="bi bi-check2-all ub-icon" aria-hidden="true"></i> Marcar todas como leídas
                    </button>
                </form>
            <?php } ?>

        </header>

        <!-- CONTADOR -->
        <?php if($notificacionesNoLeidas > 0){ ?>
            <div class="ub-notif__count reveal" role="status">
                <span class="ub-notif__count-dot" aria-hidden="true"></span>
                Tienes
                <strong><?php echo (int)$notificacionesNoLeidas; ?></strong>
                notificación<?php echo $notificacionesNoLeidas == 1 ? '' : 'es'; ?>
                pendiente<?php echo $notificacionesNoLeidas == 1 ? '' : 's'; ?>.
            </div>
        <?php } ?>

        <!-- LISTA -->
        <?php if(empty($notificaciones)){ ?>

            <div class="ub-notif__empty reveal">

                <div class="ub-notif__empty-icon">
                    <i class="bi bi-bell" aria-hidden="true"></i>
                </div>

                <h2>No tienes notificaciones</h2>

                <p>
                    Cuando haya novedades sobre tus compras o ventas,
                    aparecerán aquí.
                </p>

                <a href="index.php?accion=catalogo" class="ub-notif__btn ub-notif__btn--primary">
                    Explorar catálogo
                </a>

            </div>

        <?php }else{ ?>

            <div class="ub-notif__list">

                <?php foreach($notificaciones as $notificacion){

                    $tipo = $notificacion['tipo'] ?? '';

                    $datosTipo = $ubNotifTipos[$tipo] ?? ['bi-bell', 'slate', 'Notificación'];

                    $icono   = $datosTipo[0];
                    $tono    = $datosTipo[1];
                    $etiqueta = $datosTipo[2];

                    $fechaRaw = $notificacion['fecha_creacion'] ?? '';
                    $fechaTs  = !empty($fechaRaw) ? strtotime($fechaRaw) : false;
                    $fechaCompleta = $fechaTs ? date('d/m/Y H:i', $fechaTs) : '';

                    $grupo = $ubNotifGrupo($fechaRaw);

                    $tieneEnlace = !empty($notificacion['enlace']);
                    $noLeida = empty($notificacion['leida']);
                ?>

                    <?php if($grupo !== $ubNotifGrupoActual){
                        $ubNotifGrupoActual = $grupo; ?>
                        <h2 class="ub-notif__group reveal"><?php echo htmlspecialchars($grupo, ENT_QUOTES, 'UTF-8'); ?></h2>
                    <?php } ?>

                    <article class="ub-notif-card ub-notif-card--<?php echo $tono; ?> <?php echo $noLeida ? 'is-unread' : 'is-read'; ?> reveal">

                        <?php if($noLeida){ ?>
                            <span class="ub-notif-card__pulse" title="Sin leer" aria-hidden="true"></span>
                        <?php } ?>

                        <div class="ub-notif-card__icon">
                            <i class="bi <?php echo $icono; ?>" aria-hidden="true"></i>
                        </div>

                        <div class="ub-notif-card__body">

                            <div class="ub-notif-card__top">
                                <span class="ub-notif-card__kind"><?php echo htmlspecialchars($etiqueta, ENT_QUOTES, 'UTF-8'); ?></span>
                                <time class="ub-notif-card__time"
                                      <?php echo $fechaTs ? 'datetime="' . date('c', $fechaTs) . '" title="' . $fechaCompleta . '"' : ''; ?>>
                                    <?php echo htmlspecialchars($ubNotifHora($fechaRaw), ENT_QUOTES, 'UTF-8'); ?>
                                </time>
                            </div>

                            <h3 class="ub-notif-card__title">
                                <?php if($tieneEnlace){ ?>
                                    <a class="ub-notif-card__open" href="<?php echo htmlspecialchars($notificacion['enlace'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($notificacion['titulo'], ENT_QUOTES, 'UTF-8'); ?>
                                    </a>
                                <?php }else{ ?>
                                    <?php echo htmlspecialchars($notificacion['titulo'], ENT_QUOTES, 'UTF-8'); ?>
                                <?php } ?>
                            </h3>

                            <p class="ub-notif-card__text">
                                <?php echo htmlspecialchars($notificacion['mensaje'], ENT_QUOTES, 'UTF-8'); ?>
                            </p>

                            <?php if($tieneEnlace || $noLeida){ ?>
                                <div class="ub-notif-card__actions">

                                    <?php if($tieneEnlace){ ?>
                                        <a
                                            href="<?php echo htmlspecialchars($notificacion['enlace'], ENT_QUOTES, 'UTF-8'); ?>"
                                            class="ub-notif__btn ub-notif__btn--primary ub-notif__btn--sm"
                                        >
                                            Ver detalles <i class="bi bi-arrow-right" aria-hidden="true"></i>
                                        </a>
                                    <?php } ?>

                                    <?php if($noLeida){ ?>
                                        <form
                                            method="POST"
                                            action="index.php?accion=notificaciones"
                                        >
                                            <input
                                                type="hidden"
                                                name="marcar_notificacion"
                                                value="1"
                                            >
                                            <input
                                                type="hidden"
                                                name="notificacion_id"
                                                value="<?php echo (int)$notificacion['id']; ?>"
                                            >
                                            <input
                                                type="hidden"
                                                name="volver"
                                                value="notificaciones"
                                            >
                                            <button
                                                type="submit"
                                                class="ub-notif__btn ub-notif__btn--sm"
                                            >
                                                <i class="bi bi-check-lg" aria-hidden="true"></i> Marcar leída
                                            </button>
                                        </form>
                                    <?php } ?>

                                </div>
                            <?php } ?>

                        </div>

                    </article>

                <?php } ?>

            </div>

        <?php } ?>

    </div>

</section>

<?php include "views/partials/footer.php"; ?>
