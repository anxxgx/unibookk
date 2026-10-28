<?php

/*
 * FASE 5 — Comunidad UniBook: detalle de una publicación con sus comentarios.
 */

include "views/partials/comunidad_helpers.php";
include "views/partials/navbar.php";

if (empty($comDatos['listo']) || empty($comDatos['pub'])) {
    include "views/partials/comunidad_sin_instalar.php";
    include "views/partials/footer.php";
    return;
}

$pub = $comDatos['pub'];
$esAutor = !empty($comDatos['es_autor']);
$raices = $comDatos['raices'] ?? [];
$respuestas = $comDatos['respuestas'] ?? [];
$pagina = (int)($comDatos['pagina'] ?? 1);
$paginas = (int)($comDatos['paginas'] ?? 1);
$sugeridos = $comDatos['sugeridos'] ?? [];
$miConversacion = (int)($comDatos['mi_conversacion'] ?? 0);
$totalChats = (int)($comDatos['total_chats'] ?? 0);

$pId = (int)$pub['id'];
$pNombre = trim(($pub['autor_nombre'] ?? '') . ' ' . ($pub['autor_apellido'] ?? ''));
$pCat = $comCategorias[$pub['categoria']] ?? $comCategorias['otro'];
$pCatNombre = $comCatCorta[$pub['categoria']] ?? $pCat['etiqueta'];
$pEstado = $pub['estado'];
$pActiva = $pEstado === 'activa';
$pImg = $comunidadController->rutaImagenValida($pub['imagen'] ?? null);
$pTotalComentarios = (int)$pub['total_comentarios'];

$comLinkPagina = function ($n) use ($pId) {
    return 'index.php?' . http_build_query(['accion' => 'comunidad_publicacion', 'id' => $pId, 'cpag' => $n]) . '#comentarios';
};

?>

<section class="uc uc--detail" id="uc">

    <div class="uc-topbar">
        <a class="uc-btn uc-btn--glass uc-btn--sm" href="index.php?accion=comunidad"><i class="bi bi-arrow-left" aria-hidden="true"></i>Volver a Comunidad</a>
        <nav class="uc-crumbs" aria-label="Ruta de navegación">
            <a href="index.php?accion=comunidad">Comunidad</a>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
            <a href="index.php?accion=comunidad&amp;categoria=<?= $comE($pub['categoria']) ?>"><?= $comE($pCatNombre) ?></a>
        </nav>
    </div>

    <?php include "views/partials/comunidad_nav.php"; ?>

    <?php $comImprimirFlash(); ?>

    <article class="uc-post ub-panel<?= !$pActiva ? ' is-' . $comE($pEstado) : '' ?>">

        <header class="uc-card__head">

            <?= $comAvatar($pNombre, $pub['autor_foto'] ?? null, 'uc-avatar--md') ?>

            <div class="uc-card__who">
                <strong><?= $comE($pNombre) ?></strong>
                <small>
                    <?php if (!empty($pub['autor_universidad'])) { ?><?= $comE($pub['autor_universidad']) ?> · <?php } ?>
                    <time datetime="<?= $comE($pub['fecha_creacion']) ?>"><?= $comE($comFechaLarga($pub['fecha_creacion'])) ?></time>
                    <?php if (!empty($pub['fecha_edicion'])) { ?> · editada<?php } ?>
                </small>
            </div>

            <span class="uc-status uc-status--<?= $comE($pEstado) ?>">
                <i class="bi <?= $comE($comEstadoIcono[$pEstado] ?? 'bi-circle') ?>" aria-hidden="true"></i><?= $comE($comEstadoNombre[$pEstado] ?? '') ?>
            </span>

        </header>

        <span class="uc-tag"><i class="bi <?= $comE($pCat['icono']) ?>" aria-hidden="true"></i><?= $comE($pCatNombre) ?></span>

        <h1 class="uc-post__title"><?= $comE($pub['titulo']) ?></h1>

        <?php if (!$pActiva) { ?>
            <p class="uc-note">
                <i class="bi <?= $pEstado === 'resuelta' ? 'bi-check-circle-fill' : 'bi-lock-fill' ?>" aria-hidden="true"></i>
                <?= $pEstado === 'resuelta'
                    ? 'Esta publicación fue marcada como resuelta. Ya no recibe comentarios nuevos.'
                    : 'Esta publicación está cerrada. Ya no recibe comentarios nuevos.' ?>
            </p>
        <?php } ?>

        <p class="uc-post__text"><?= $comTexto($pub['contenido']) ?></p>

        <?php if (!empty($pub['libro_titulo']) || !empty($pub['libro_autor']) || !empty($pub['modalidad'])) { ?>
            <dl class="uc-book">
                <?php if (!empty($pub['libro_titulo'])) { ?><div><dt>Libro</dt><dd><?= $comE($pub['libro_titulo']) ?></dd></div><?php } ?>
                <?php if (!empty($pub['libro_autor'])) { ?><div><dt>Autor</dt><dd><?= $comE($pub['libro_autor']) ?></dd></div><?php } ?>
                <?php if (!empty($pub['modalidad']) && isset($comModalidades[$pub['modalidad']])) { ?><div><dt>Modalidad</dt><dd><?= $comE($comModalidades[$pub['modalidad']]) ?></dd></div><?php } ?>
            </dl>
        <?php } ?>

        <?php if (!empty($pub['asignatura']) || !empty($pub['carrera'])) { ?>
            <p class="uc-card__meta">
                <?php if (!empty($pub['asignatura'])) { ?><span><i class="bi bi-journal-text" aria-hidden="true"></i><?= $comE($pub['asignatura']) ?></span><?php } ?>
                <?php if (!empty($pub['carrera'])) { ?><span><i class="bi bi-mortarboard" aria-hidden="true"></i><?= $comE($pub['carrera']) ?></span><?php } ?>
            </p>
        <?php } ?>

        <?php if ($pImg) { ?>
            <figure class="uc-post__img"><img src="<?= $comE($pImg) ?>" alt="Imagen de la publicación" loading="lazy"></figure>
        <?php } ?>

        <?php if ($esAutor) { ?>

            <div class="uc-owner" role="group" aria-labelledby="uc-owner-titulo">

                <p class="uc-owner__title" id="uc-owner-titulo"><i class="bi bi-gear" aria-hidden="true"></i>Gestionar tu publicación</p>

                <div class="uc-owner__actions">

                    <a class="uc-btn uc-btn--glass" href="index.php?accion=comunidad_editar&amp;id=<?= $pId ?>"><i class="bi bi-pencil" aria-hidden="true"></i>Editar</a>

                    <?php if ($pActiva) { ?>
                        <form method="post" action="index.php?accion=comunidad_publicacion&amp;id=<?= $pId ?>" data-uc-submit>
                            <input type="hidden" name="com_accion" value="estado_publicacion">
                            <input type="hidden" name="csrf" value="<?= $comE($comCsrf) ?>">
                            <input type="hidden" name="id" value="<?= $pId ?>">
                            <input type="hidden" name="estado" value="resuelta">
                            <button type="submit" class="uc-btn uc-btn--ok"><i class="bi bi-check-circle" aria-hidden="true"></i>Marcar como resuelta</button>
                        </form>
                        <form method="post" action="index.php?accion=comunidad_publicacion&amp;id=<?= $pId ?>" data-uc-submit>
                            <input type="hidden" name="com_accion" value="estado_publicacion">
                            <input type="hidden" name="csrf" value="<?= $comE($comCsrf) ?>">
                            <input type="hidden" name="id" value="<?= $pId ?>">
                            <input type="hidden" name="estado" value="cerrada">
                            <button type="submit" class="uc-btn uc-btn--glass"><i class="bi bi-lock" aria-hidden="true"></i>Cerrar</button>
                        </form>
                    <?php } else { ?>
                        <form method="post" action="index.php?accion=comunidad_publicacion&amp;id=<?= $pId ?>" data-uc-submit>
                            <input type="hidden" name="com_accion" value="estado_publicacion">
                            <input type="hidden" name="csrf" value="<?= $comE($comCsrf) ?>">
                            <input type="hidden" name="id" value="<?= $pId ?>">
                            <input type="hidden" name="estado" value="activa">
                            <button type="submit" class="uc-btn uc-btn--glass"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>Reabrir</button>
                        </form>
                    <?php } ?>

                    <?php if ($totalChats > 0) { ?>
                        <a class="uc-btn uc-btn--glass" href="index.php?accion=comunidad_mensajes"><i class="bi bi-chat-dots" aria-hidden="true"></i><?= $totalChats ?> <?= $totalChats === 1 ? 'conversación' : 'conversaciones' ?></a>
                    <?php } ?>

                    <details class="uc-inline uc-inline--danger uc-owner__delete">
                        <summary><i class="bi bi-trash3" aria-hidden="true"></i>Eliminar</summary>
                        <form method="post" action="index.php?accion=comunidad_publicacion&amp;id=<?= $pId ?>" class="uc-inline__form uc-inline__form--row" data-uc-submit>
                            <input type="hidden" name="com_accion" value="eliminar_publicacion">
                            <input type="hidden" name="csrf" value="<?= $comE($comCsrf) ?>">
                            <input type="hidden" name="id" value="<?= $pId ?>">
                            <span>Se borrarán también sus comentarios. ¿Continuar?</span>
                            <button type="submit" class="uc-btn uc-btn--danger">Sí, eliminar</button>
                        </form>
                    </details>

                </div>

            </div>

        <?php } else { ?>

            <div class="uc-cta">

                <?php if ($comUid > 0) { ?>

                    <?php if ($miConversacion > 0) { ?>
                        <a class="ub-btn-navy" href="index.php?accion=comunidad_chat&amp;id=<?= $miConversacion ?>"><i class="bi bi-chat-dots" aria-hidden="true"></i>Ir a la conversación</a>
                    <?php } elseif ($pActiva) { ?>
                        <a class="ub-btn-navy" href="index.php?accion=comunidad_chat&amp;publicacion=<?= $pId ?>"><i class="bi bi-envelope" aria-hidden="true"></i>Enviar mensaje</a>
                    <?php } ?>

                    <?php if ($pActiva) { ?>
                        <a class="uc-btn uc-btn--glass" href="#uc-comentario" data-uc-focus="uc-comentario"><i class="bi bi-chat-left-text" aria-hidden="true"></i>Comentar</a>
                    <?php } ?>

                    <p class="uc-cta__hint">Los mensajes privados solo los ven ustedes dos.</p>

                <?php } else { ?>

                    <a class="ub-btn-navy" href="index.php?accion=login"><i class="bi bi-box-arrow-in-right" aria-hidden="true"></i>Inicia sesión para escribirle</a>
                    <p class="uc-cta__hint">Con tu cuenta podrás comentar y enviarle un mensaje privado.</p>

                <?php } ?>

            </div>

        <?php } ?>

    </article>


    <?php if ($sugeridos) { ?>
        <aside class="uc-suggest ub-panel" aria-label="Libros disponibles que podrían servirte">

            <h2><i class="bi bi-lightbulb" aria-hidden="true"></i>Libros disponibles en UniBook</h2>
            <p>Estas publicaciones podrían resolver esta necesidad.</p>

            <ul>
                <?php foreach ($sugeridos as $l) { ?>
                    <li>
                        <a href="index.php?accion=detalle_libro&amp;id=<?= (int)$l['id'] ?>">
                            <strong><?= $comE($l['titulo']) ?></strong>
                            <span><?= $comE($l['autor']) ?> · <?= $comE($l['tipo_publicacion'] ?? '') ?><?php if (($l['tipo_publicacion'] ?? '') === 'Venta' && $l['precio'] !== null) { ?> · $<?= $comE(number_format((float)$l['precio'], 0, ',', '.')) ?><?php } ?></span>
                        </a>
                        <i class="bi bi-chevron-right" aria-hidden="true"></i>
                    </li>
                <?php } ?>
            </ul>

        </aside>
    <?php } ?>


    <!-- ===================== COMENTARIOS ===================== -->

    <section class="uc-comments ub-panel" id="comentarios" aria-labelledby="uc-comentarios-titulo">

        <h2 id="uc-comentarios-titulo">
            <i class="bi bi-chat-left-text" aria-hidden="true"></i>
            <?= $pTotalComentarios ?> <?= $pTotalComentarios === 1 ? 'comentario' : 'comentarios' ?>
        </h2>

        <?php if ($comUid > 0 && $pActiva) { ?>

            <form method="post" action="index.php?accion=comunidad_publicacion&amp;id=<?= $pId ?>" class="uc-comment-form" data-uc-submit>
                <input type="hidden" name="com_accion" value="comentar">
                <input type="hidden" name="csrf" value="<?= $comE($comCsrf) ?>">
                <input type="hidden" name="nonce" value="<?= $comE($comNonce) ?>">
                <input type="hidden" name="publicacion_id" value="<?= $pId ?>">
                <input type="hidden" name="cpag" value="<?= $pagina ?>">
                <?= $comAvatar($_SESSION['nombre'] ?? '', null, 'uc-avatar--sm') ?>
                <div class="uc-comment-form__box">
                    <label class="visually-hidden" for="uc-comentario">Escribe un comentario</label>
                    <textarea class="form-control" id="uc-comentario" name="contenido" rows="2" maxlength="1500" required
                              placeholder="Aporta una respuesta o recomendación…"
                              data-uc-autosize data-uc-count="1500" data-uc-submit-key></textarea>
                    <div class="uc-comment-form__row">
                        <small class="uc-count" data-uc-counter></small>
                        <button type="submit" class="ub-btn-navy ub-btn-navy--sm" data-uc-busy="Enviando…"><i class="bi bi-send" aria-hidden="true"></i>Comentar</button>
                    </div>
                </div>
            </form>

        <?php } elseif ($comUid <= 0) { ?>

            <p class="uc-note"><i class="bi bi-person-lock" aria-hidden="true"></i><a href="index.php?accion=login">Inicia sesión</a> para comentar y responder.</p>

        <?php } ?>

        <?php if (!$raices) { ?>

            <div class="uc-empty uc-empty--inline">
                <i class="bi bi-chat-dots" aria-hidden="true"></i>
                <p><?= $pActiva ? 'Nadie ha comentado todavía. ¡Puedes ser la primera persona en ayudar!' : 'Esta publicación no tiene comentarios.' ?></p>
            </div>

        <?php } else { ?>

            <div class="uc-thread">

                <?php foreach ($raices as $cm) {

                    $cmEsRespuesta = false;
                    include "views/partials/comunidad_comentario.php";

                    $hijas = $respuestas[(int)$cm['id']] ?? [];

                    if ($hijas) { ?>
                        <div class="uc-replies">
                            <?php foreach ($hijas as $cm) {
                                $cmEsRespuesta = true;
                                include "views/partials/comunidad_comentario.php";
                            } ?>
                        </div>
                    <?php }

                } ?>

            </div>

            <?php if ($paginas > 1) { ?>
                <nav class="uc-pager" aria-label="Paginación de comentarios">

                    <?php if ($pagina > 1) { ?>
                        <a class="uc-btn uc-btn--glass" href="<?= $comE($comLinkPagina($pagina - 1)) ?>"><i class="bi bi-chevron-left" aria-hidden="true"></i>Anteriores</a>
                    <?php } else { ?><span></span><?php } ?>

                    <span class="uc-pager__info">Página <?= $pagina ?> de <?= $paginas ?></span>

                    <?php if ($pagina < $paginas) { ?>
                        <a class="uc-btn uc-btn--glass" href="<?= $comE($comLinkPagina($pagina + 1)) ?>">Siguientes<i class="bi bi-chevron-right" aria-hidden="true"></i></a>
                    <?php } else { ?><span></span><?php } ?>

                </nav>
            <?php } ?>

        <?php } ?>

    </section>

</section>

<?php include "views/partials/footer.php"; ?>
