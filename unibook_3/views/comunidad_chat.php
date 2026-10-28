<?php

/*
 * FASE 5 — Comunidad UniBook: conversación privada entre dos personas.
 * El controlador ya verificó que la persona participa en la conversación.
 */

include "views/partials/comunidad_helpers.php";
include "views/partials/navbar.php";

if (empty($comDatos['listo']) || !isset($comDatos['otro'])) {
    include "views/partials/comunidad_sin_instalar.php";
    include "views/partials/footer.php";
    return;
}

$conv = $comDatos['conversacion'];
$mensajes = $comDatos['mensajes'] ?? [];
$otro = $comDatos['otro'];
$esNueva = !empty($comDatos['nueva']);
$pubNueva = $comDatos['publicacion'] ?? null;

$chatTitulo = $esNueva ? (string)$pubNueva['titulo'] : (string)$conv['publicacion_titulo'];
$chatPubId = $esNueva ? (int)$pubNueva['id'] : (int)($conv['publicacion_id'] ?? 0);

$chatCat = $esNueva ? ($comCategorias[$pubNueva['categoria']] ?? null) : null;
$chatCatNombre = $esNueva ? ($comCatCorta[$pubNueva['categoria']] ?? '') : '';

$chatDia = '';

/* Quién publicó (para la tarjeta de contexto). */
if ($esNueva) {
    $chatAutorPub = trim(($pubNueva['autor_nombre'] ?? '') . ' ' . ($pubNueva['autor_apellido'] ?? ''));
    $chatFechaPub = $comFechaLarga($pubNueva['fecha_creacion'] ?? '');
} else {
    $chatAutorPub = trim(($conv['autor_nombre'] ?? '') . ' ' . ($conv['autor_apellido'] ?? ''));
    $chatFechaPub = '';
}

?>

<section class="uc uc--chat" id="uc">

    <nav class="uc-crumbs" aria-label="Ruta de navegación">
        <a href="index.php?accion=comunidad">Comunidad</a>
        <i class="bi bi-chevron-right" aria-hidden="true"></i>
        <a href="index.php?accion=comunidad_mensajes">Mensajes</a>
        <i class="bi bi-chevron-right" aria-hidden="true"></i>
        <span aria-current="page"><?= $comE($otro['nombre']) ?></span>
    </nav>

    <?php $comImprimirFlash(); ?>

    <div class="uc-chat ub-panel">

        <header class="uc-chat__head">

            <a class="uc-chat__back" href="index.php?accion=comunidad_mensajes" aria-label="Volver a Mensajes">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
            </a>

            <?= $comAvatar($otro['nombre'], $otro['foto'] ?? null, 'uc-avatar--md') ?>

            <div class="uc-chat__who">
                <strong><?= $comE($otro['nombre']) ?></strong>
                <small><i class="bi bi-lock-fill" aria-hidden="true"></i>Conversación privada</small>
            </div>

            <?php if ($chatPubId > 0) { ?>
                <a class="uc-chat__about" href="index.php?accion=comunidad_publicacion&amp;id=<?= $chatPubId ?>" title="Ver la publicación">
                    <span>Sobre:</span> <?= $comE($chatTitulo) ?>
                </a>
            <?php } else { ?>
                <span class="uc-chat__about is-gone"><span>Sobre:</span> <?= $comE($chatTitulo) ?> · eliminada</span>
            <?php } ?>

        </header>

        <div class="uc-chat__scroll" data-uc-chat tabindex="0" role="log" aria-label="Mensajes de la conversación">

            <div class="uc-context">
                <span class="uc-context__icon" aria-hidden="true"><i class="bi <?= $chatCat ? $comE($chatCat['icono']) : 'bi-chat-square-text' ?>"></i></span>
                <div class="uc-context__body">
                    <span class="uc-context__label"><?= $esNueva ? 'Vas a escribir sobre' : 'Publicación relacionada' ?></span>
                    <strong><?= $comE($chatTitulo) ?></strong>
                    <?php if ($chatAutorPub !== '') { ?>
                        <small>Publicado por <?= $comE($chatAutorPub) ?><?= $chatFechaPub !== '' ? ' · ' . $comE($chatFechaPub) : '' ?></small>
                    <?php } ?>
                </div>
                <?php if ($chatPubId > 0) { ?>
                    <a class="uc-btn uc-btn--glass uc-btn--sm" href="index.php?accion=comunidad_publicacion&amp;id=<?= $chatPubId ?>">Ver</a>
                <?php } ?>
            </div>

            <?php if (!$mensajes) { ?>

                <div class="uc-empty uc-empty--inline">
                    <span class="uc-empty__icon"><i class="bi bi-envelope-paper" aria-hidden="true"></i></span>
                    <h2>Empieza la conversación</h2>
                    <p>Saluda a <?= $comE($otro['nombre']) ?> y cuéntale qué necesitas. Solo ustedes dos verán estos mensajes.</p>
                </div>

            <?php } ?>

            <?php foreach ($mensajes as $m) {

                $mio = (int)$m['remitente_id'] === $comUid;
                $dia = date('Y-m-d', strtotime($m['fecha_creacion']));

                if ($dia !== $chatDia) {
                    $chatDia = $dia;
                    ?>
                    <p class="uc-chat__day"><?= $comE(date('d/m/Y', strtotime($m['fecha_creacion']))) ?></p>
                <?php } ?>

                <div class="uc-msg<?= $mio ? ' uc-msg--mine' : '' ?>">
                    <?php if (!$mio) { ?><?= $comAvatar($otro['nombre'], $otro['foto'] ?? null, 'uc-avatar--xs') ?><?php } ?>
                    <div class="uc-msg__col">
                        <div class="uc-msg__bubble"><?= $comTexto($m['mensaje']) ?></div>
                        <small>
                            <?= $comE($comHora($m['fecha_creacion'])) ?>
                            <?php if ($mio) { ?>
                                · <i class="bi <?= (int)$m['leido'] === 1 ? 'bi-check2-all' : 'bi-check2' ?>" aria-hidden="true"></i> <?= (int)$m['leido'] === 1 ? 'Leído' : 'Enviado' ?>
                            <?php } ?>
                        </small>
                    </div>
                </div>

            <?php } ?>

            <span id="fin"></span>

        </div>

        <form method="post"
              action="index.php?accion=comunidad_chat&amp;<?= $esNueva ? 'publicacion=' . $chatPubId : 'id=' . (int)$conv['id'] ?>"
              class="uc-chat__form" data-uc-submit>

            <input type="hidden" name="com_accion" value="enviar_mensaje">
            <input type="hidden" name="csrf" value="<?= $comE($comCsrf) ?>">
            <input type="hidden" name="nonce" value="<?= $comE($comNonce) ?>">
            <?php if ($esNueva) { ?>
                <input type="hidden" name="publicacion_id" value="<?= $chatPubId ?>">
            <?php } else { ?>
                <input type="hidden" name="conversacion_id" value="<?= (int)$conv['id'] ?>">
            <?php } ?>

            <label class="visually-hidden" for="uc-mensaje">Escribe un mensaje</label>

            <div class="uc-chat__box">
                <textarea class="form-control" id="uc-mensaje" name="mensaje" rows="1" maxlength="1000" required
                          placeholder="Escribe un mensaje…"
                          data-uc-autosize data-uc-submit-key></textarea>

                <button type="submit" class="ub-btn-navy uc-chat__send" data-uc-busy="Enviando…" aria-label="Enviar mensaje">
                    <i class="bi bi-send-fill" aria-hidden="true"></i><span>Enviar</span>
                </button>
            </div>

        </form>

    </div>

    <p class="uc-hint">Conversación privada: solo ustedes dos pueden verla. Recarga la página para ver mensajes nuevos. <span class="uc-hint__keys">Ctrl + Enter para enviar.</span></p>

</section>

<?php include "views/partials/footer.php"; ?>
