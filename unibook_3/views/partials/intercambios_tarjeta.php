<?php

/*
|--------------------------------------------------------------------------
| Mis intercambios — tarjeta de una solicitud
| Espera: $intercambio (fila de Intercambio::obtenerRecibidos/obtenerEnviados),
| $ixModo ('recibidos' | 'enviados') y $ixPos (posición, para la animación).
| Las acciones de aceptar/rechazar son los MISMOS formularios de siempre
| (POST a index.php con intercambio_id).
|--------------------------------------------------------------------------
*/

$ixRecibidosModo = ($ixModo ?? 'recibidos') === 'recibidos';

$ixEstadoRaw = strtolower(trim((string)($intercambio['estado'] ?? 'pendiente')));

[$ixEstTxt, $ixEstCls, $ixEstIco] = $ixEstado($ixEstadoRaw);

$ixIdSolicitado = (int)($intercambio['libro_solicitado_id'] ?? 0);
$ixIdOfrecido   = (int)($intercambio['libro_ofrecido_id'] ?? 0);

$ixTituloSolicitado = trim((string)($intercambio['libro_solicitado'] ?? ''));
$ixTituloOfrecido   = trim((string)($intercambio['libro_ofrecido'] ?? ''));

if ($ixTituloSolicitado === '') { $ixTituloSolicitado = 'Libro no disponible'; }
if ($ixTituloOfrecido === '')   { $ixTituloOfrecido   = 'Libro no disponible'; }

$ixPortadaSolicitado = $ixPortadaDe($ixIdSolicitado);
$ixPortadaOfrecido   = $ixPortadaDe($ixIdOfrecido);

if ($ixRecibidosModo) {

    /* Recibidos: el usuario relacionado es quien solicita (nombre/apellido de la consulta). */
    $ixOtro = trim(
        ($intercambio['nombre'] ?? '') . ' ' . ($intercambio['apellido'] ?? '')
    );
    $ixRolOtro = 'Solicitante';
    $ixEtiquetaSolicitado = 'Tu libro';
    $ixEtiquetaOfrecido   = 'Te ofrece';
    $ixIdConsulta = $ixIdOfrecido;
    $ixTextoConsulta = 'Ver libro ofrecido';

} else {

    /* Enviados: el usuario relacionado es el propietario del libro solicitado. */
    $ixOtro = $ixNombreDe($intercambio['propietario_id'] ?? 0);
    $ixRolOtro = 'Propietario';
    $ixEtiquetaSolicitado = 'Libro que deseas';
    $ixEtiquetaOfrecido   = 'Tú ofreciste';
    $ixIdConsulta = $ixIdSolicitado;
    $ixTextoConsulta = 'Ver libro solicitado';
}

$ixFechaTexto = $ixFecha($intercambio['fecha_creacion'] ?? '');

?>

<li class="ix-item">

    <article class="ix-card ix-card--<?= $ixH($ixEstCls) ?> ix-rise" style="--i:<?= min((int)($ixPos ?? 1), 8) + 1 ?>">

        <!-- Usuario y estado -->

        <header class="ix-card__top">

            <div class="ix-user">

                <?php if ($ixOtro !== ''): ?>

                    <span class="ix-user__avatar" aria-hidden="true"><?= $ixH($ixInicial($ixOtro)) ?></span>

                    <div class="ix-user__text">
                        <span class="ix-user__role"><?= $ixH($ixRolOtro) ?></span>
                        <strong class="ix-user__name"><?= $ixH($ixOtro) ?></strong>
                    </div>

                <?php else: ?>

                    <span class="ix-user__avatar" aria-hidden="true"><i class="bi bi-person"></i></span>

                    <div class="ix-user__text">
                        <span class="ix-user__role"><?= $ixH($ixRolOtro) ?></span>
                        <strong class="ix-user__name">Usuario no disponible</strong>
                    </div>

                <?php endif; ?>

            </div>

            <span class="ix-status ix-status--<?= $ixH($ixEstCls) ?>">
                <i class="bi <?= $ixH($ixEstIco) ?>" aria-hidden="true"></i>
                <?= $ixH($ixEstTxt) ?>
            </span>

        </header>


        <!-- Los dos libros -->

        <div class="ix-swap">

            <a class="ix-book" href="index.php?accion=detalle_libro&amp;id=<?= $ixIdSolicitado ?>">

                <span class="ix-book__cover">
                    <?php if ($ixPortadaSolicitado !== ''): ?>
                        <img src="<?= $ixH($ixPortadaSolicitado) ?>" alt="Portada de <?= $ixH($ixTituloSolicitado) ?>" loading="lazy">
                    <?php else: ?>
                        <span class="ix-book__none"><i class="bi bi-book" aria-hidden="true"></i>Sin portada</span>
                    <?php endif; ?>
                </span>

                <span class="ix-book__info">
                    <span class="ix-book__label"><?= $ixH($ixEtiquetaSolicitado) ?></span>
                    <span class="ix-book__title"><?= $ixH($ixTituloSolicitado) ?></span>
                </span>

            </a>

            <span class="ix-swap__icon" aria-hidden="true"><i class="bi bi-arrow-left-right"></i></span>

            <a class="ix-book ix-book--offer" href="index.php?accion=detalle_libro&amp;id=<?= $ixIdOfrecido ?>">

                <span class="ix-book__cover">
                    <?php if ($ixPortadaOfrecido !== ''): ?>
                        <img src="<?= $ixH($ixPortadaOfrecido) ?>" alt="Portada de <?= $ixH($ixTituloOfrecido) ?>" loading="lazy">
                    <?php else: ?>
                        <span class="ix-book__none"><i class="bi bi-book" aria-hidden="true"></i>Sin portada</span>
                    <?php endif; ?>
                </span>

                <span class="ix-book__info">
                    <span class="ix-book__label"><?= $ixH($ixEtiquetaOfrecido) ?></span>
                    <span class="ix-book__title"><?= $ixH($ixTituloOfrecido) ?></span>
                </span>

            </a>

        </div>


        <!-- Fecha y consulta -->

        <div class="ix-card__meta">

            <span class="ix-date">
                <i class="bi bi-calendar3" aria-hidden="true"></i>
                <?php if ($ixFechaTexto !== ''): ?>
                    <time datetime="<?= $ixH($intercambio['fecha_creacion']) ?>"><?= $ixH($ixFechaTexto) ?></time>
                <?php else: ?>
                    Sin fecha
                <?php endif; ?>
            </span>

            <?php if ($ixIdConsulta > 0): ?>
                <a class="ix-link" href="index.php?accion=detalle_libro&amp;id=<?= $ixIdConsulta ?>">
                    <?= $ixH($ixTextoConsulta) ?>
                    <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                </a>
            <?php endif; ?>

        </div>


        <!-- Acciones / resultado -->

        <?php if ($ixRecibidosModo && $ixEstadoRaw === 'pendiente'): ?>

            <div class="ix-actions">

                <form
                    method="POST"
                    action="index.php?accion=intercambios_recibidos"
                    onsubmit="return confirm('¿Seguro que quieres aceptar este intercambio?');"
                >
                    <input type="hidden" name="intercambio_id" value="<?= (int)$intercambio['id'] ?>">

                    <button type="submit" name="aceptar_intercambio" class="ix-btn ix-btn--accept">
                        <i class="bi bi-check-lg" aria-hidden="true"></i>
                        Aceptar
                    </button>
                </form>

                <form
                    method="POST"
                    action="index.php?accion=intercambios_recibidos"
                    onsubmit="return confirm('¿Seguro que quieres rechazar este intercambio?');"
                >
                    <input type="hidden" name="intercambio_id" value="<?= (int)$intercambio['id'] ?>">

                    <button type="submit" name="rechazar_intercambio" class="ix-btn ix-btn--reject">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                        Rechazar
                    </button>
                </form>

            </div>

        <?php elseif ($ixEstadoRaw === 'pendiente'): ?>

            <p class="ix-note ix-note--warning">
                <i class="bi bi-hourglass-split" aria-hidden="true"></i>
                A la espera de la respuesta del propietario.
            </p>

        <?php elseif ($ixEstadoRaw === 'aceptado'): ?>

            <p class="ix-note ix-note--success">
                <i class="bi bi-check-circle" aria-hidden="true"></i>
                <?= $ixRecibidosModo ? 'Este intercambio fue aceptado.' : 'El propietario aceptó tu solicitud.' ?>
            </p>

        <?php elseif ($ixEstadoRaw === 'rechazado'): ?>

            <p class="ix-note ix-note--danger">
                <i class="bi bi-x-circle" aria-hidden="true"></i>
                <?= $ixRecibidosModo ? 'Esta solicitud fue rechazada.' : 'El propietario rechazó tu solicitud.' ?>
            </p>

        <?php endif; ?>

    </article>

</li>
