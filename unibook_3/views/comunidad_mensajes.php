<?php

/* FASE 5 — Comunidad UniBook: bandeja de conversaciones privadas. */

include "views/partials/comunidad_helpers.php";
include "views/partials/navbar.php";

if (empty($comDatos['listo'])) {
    include "views/partials/comunidad_sin_instalar.php";
    include "views/partials/footer.php";
    return;
}

$conversaciones = $comDatos['conversaciones'] ?? [];

$totalConv = count($conversaciones);
$totalSinLeer = 0;

foreach ($conversaciones as $c) {
    $totalSinLeer += (int)$c['no_leidos'];
}

?>

<section class="uc" id="uc">

    <header class="uc-pagehead">

        <div class="uc-pagehead__text">
            <h1>Mensajes de la Comunidad</h1>
            <p>Tus conversaciones privadas con quienes publican en la Comunidad. Toca una para abrirla y responder.</p>
        </div>

        <a class="uc-btn uc-btn--glass uc-pagehead__back" href="index.php?accion=comunidad">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>Volver a Comunidad
        </a>

    </header>

    <?php include "views/partials/comunidad_nav.php"; ?>

    <?php $comImprimirFlash(); ?>

    <?php if (!$conversaciones) { ?>

        <div class="uc-empty uc-empty--steps ub-panel">

            <span class="uc-empty__icon"><i class="bi bi-envelope-open" aria-hidden="true"></i></span>

            <h2>Aún no tienes conversaciones</h2>
            <p>Los mensajes privados empiezan desde una publicación. Así se inicia una:</p>

            <ol class="uc-steps">
                <li><span>1</span>Explora la Comunidad y abre una publicación que te interese.</li>
                <li><span>2</span>Pulsa <strong>Enviar mensaje</strong> y escribe a su autor.</li>
                <li><span>3</span>La conversación aparecerá aquí y podrás seguirla cuando quieras.</li>
            </ol>

            <a class="ub-btn-navy" href="index.php?accion=comunidad"><i class="bi bi-compass" aria-hidden="true"></i>Explorar la Comunidad</a>

            <p class="uc-empty__aside">¿Buscas mensajes sobre un libro del catálogo? Están en <a class="uc-link" href="index.php?accion=mensajes">Chat</a>.</p>

        </div>

    <?php } else { ?>

        <p class="uc-total">
            <?= $totalConv ?> <?= $totalConv === 1 ? 'conversación' : 'conversaciones' ?>
            <?php if ($totalSinLeer > 0) { ?>· <strong class="uc-total__unread"><?= $totalSinLeer ?> sin leer</strong><?php } ?>
        </p>

        <ul class="uc-inbox ub-panel" aria-label="Conversaciones">

            <?php foreach ($conversaciones as $c) {

                $cNombre = trim(($c['otro_nombre'] ?? '') . ' ' . ($c['otro_apellido'] ?? ''));
                $cNoLeidos = (int)$c['no_leidos'];
                $cMio = (int)($c['ultimo_remitente'] ?? 0) === $comUid;
                $cTiene = ($c['ultimo_mensaje'] ?? '') !== '';
                $cResumen = function_exists('mb_strimwidth')
                    ? mb_strimwidth((string)($c['ultimo_mensaje'] ?? ''), 0, 140, '…', 'UTF-8')
                    : substr((string)($c['ultimo_mensaje'] ?? ''), 0, 140);

                ?>

                <li>
                    <a class="uc-inbox__item<?= $cNoLeidos > 0 ? ' is-unread' : '' ?>"
                       href="index.php?accion=comunidad_chat&amp;id=<?= (int)$c['id'] ?>"
                       aria-label="Abrir conversación con <?= $comE($cNombre) ?> sobre <?= $comE($c['publicacion_titulo']) ?><?= $cNoLeidos > 0 ? ', ' . $cNoLeidos . ' sin leer' : '' ?>">

                        <?= $comAvatar($cNombre, $c['otro_foto'] ?? null, 'uc-avatar--lg') ?>

                        <div class="uc-inbox__body">

                            <div class="uc-inbox__top">
                                <strong><?= $comE($cNombre) ?></strong>
                                <span class="uc-inbox__post"><span>En:</span> <?= $comE($c['publicacion_titulo']) ?></span>
                            </div>

                            <p class="uc-inbox__last">
                                <?php if ($cTiene) { ?>
                                    <?php if ($cMio) { ?><span class="uc-inbox__you">Tú:</span> <?php } ?><?= $comE($cResumen) ?>
                                <?php } else { ?>
                                    <em>Sin mensajes todavía</em>
                                <?php } ?>
                            </p>

                        </div>

                        <div class="uc-inbox__side">
                            <time datetime="<?= $comE($c['ultima_fecha']) ?>" title="<?= $comE($comFechaCompleta($c['ultima_fecha'])) ?>"><?= $comE($comFecha($c['ultima_fecha'])) ?></time>
                            <?php if ($cNoLeidos > 0) { ?>
                                <span class="uc-unread" aria-hidden="true"><?= $cNoLeidos > 99 ? '99+' : $cNoLeidos ?></span>
                            <?php } ?>
                        </div>

                        <span class="uc-inbox__go" aria-hidden="true"><i class="bi bi-chevron-right"></i></span>

                    </a>
                </li>

            <?php } ?>

        </ul>

        <p class="uc-hint">¿Buscas mensajes sobre un libro del catálogo? Están en <a class="uc-link" href="index.php?accion=mensajes">Chat</a>.</p>

    <?php } ?>

</section>

<?php include "views/partials/footer.php"; ?>
