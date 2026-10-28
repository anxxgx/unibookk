<?php

/*
 * FASE 5 — un comentario (principal o respuesta).
 * Variables de entrada: $cm (fila), $cmEsRespuesta (bool), $pub (publicación),
 * $comDatos['pagina'] (página actual de comentarios) y los helpers.
 */

$cmId        = (int)$cm['id'];
$cmEliminado = (int)$cm['eliminado'] === 1;
$cmEsMio     = $comUid > 0 && (int)$cm['usuario_id'] === $comUid;
$cmEsAutorPub = (int)$cm['usuario_id'] === (int)$pub['usuario_id'];
$cmNombre    = trim(($cm['autor_nombre'] ?? '') . ' ' . ($cm['autor_apellido'] ?? ''));
$cmActiva    = ($pub['estado'] ?? '') === 'activa';
$cmPagina    = (int)($comDatos['pagina'] ?? 1);

?>
<article class="uc-comment<?= $cmEsRespuesta ? ' uc-comment--reply' : '' ?><?= $cmEliminado ? ' is-deleted' : '' ?>" id="comentario-<?= $cmId ?>">

    <?php if ($cmEliminado) { ?>

        <p class="uc-comment__gone"><i class="bi bi-slash-circle" aria-hidden="true"></i> Este comentario fue eliminado.</p>

    <?php } else { ?>

        <?= $comAvatar($cmNombre, $cm['autor_foto'] ?? null, 'uc-avatar--sm') ?>

        <div class="uc-comment__body">

            <header class="uc-comment__head">
                <strong><?= $comE($cmNombre) ?></strong>
                <?php if ($cmEsAutorPub) { ?><span class="uc-tag uc-tag--author">Autor</span><?php } ?>
                <time datetime="<?= $comE($cm['fecha_creacion']) ?>" title="<?= $comE($comFechaCompleta($cm['fecha_creacion'])) ?>"><?= $comE($comFecha($cm['fecha_creacion'])) ?></time>
                <?php if (!empty($cm['fecha_edicion'])) { ?><span class="uc-comment__edited">(editado)</span><?php } ?>
            </header>

            <p class="uc-comment__text"><?= $comTexto($cm['contenido']) ?></p>

            <div class="uc-comment__actions">

                <?php if ($comUid > 0 && $cmActiva) { ?>
                    <details class="uc-inline uc-inline--reply">
                        <summary><i class="bi bi-reply" aria-hidden="true"></i> Responder</summary>
                        <form method="post" action="index.php?accion=comunidad_publicacion&amp;id=<?= (int)$pub['id'] ?>" class="uc-inline__form" data-uc-submit>
                            <input type="hidden" name="com_accion" value="comentar">
                            <input type="hidden" name="csrf" value="<?= $comE($comCsrf) ?>">
                            <input type="hidden" name="nonce" value="<?= $comE($comNonce) ?>">
                            <input type="hidden" name="publicacion_id" value="<?= (int)$pub['id'] ?>">
                            <input type="hidden" name="parent_id" value="<?= $cmId ?>">
                            <input type="hidden" name="cpag" value="<?= $cmPagina ?>">
                            <textarea class="form-control" name="contenido" rows="2" maxlength="1500" required placeholder="Escribe tu respuesta…" aria-label="Respuesta" data-uc-autosize></textarea>
                            <button type="submit" class="ub-btn-navy ub-btn-navy--sm">Responder</button>
                        </form>
                    </details>
                <?php } ?>

                <?php if ($cmEsMio) { ?>

                    <details class="uc-inline">
                        <summary><i class="bi bi-pencil" aria-hidden="true"></i> Editar</summary>
                        <form method="post" action="index.php?accion=comunidad_publicacion&amp;id=<?= (int)$pub['id'] ?>" class="uc-inline__form" data-uc-submit>
                            <input type="hidden" name="com_accion" value="editar_comentario">
                            <input type="hidden" name="csrf" value="<?= $comE($comCsrf) ?>">
                            <input type="hidden" name="comentario_id" value="<?= $cmId ?>">
                            <input type="hidden" name="cpag" value="<?= $cmPagina ?>">
                            <textarea class="form-control" name="contenido" rows="3" maxlength="1500" required aria-label="Editar comentario" data-uc-autosize><?= $comE($cm['contenido']) ?></textarea>
                            <button type="submit" class="ub-btn-navy ub-btn-navy--sm">Guardar</button>
                        </form>
                    </details>

                    <details class="uc-inline uc-inline--danger">
                        <summary><i class="bi bi-trash3" aria-hidden="true"></i> Eliminar</summary>
                        <form method="post" action="index.php?accion=comunidad_publicacion&amp;id=<?= (int)$pub['id'] ?>" class="uc-inline__form uc-inline__form--row" data-uc-submit>
                            <input type="hidden" name="com_accion" value="eliminar_comentario">
                            <input type="hidden" name="csrf" value="<?= $comE($comCsrf) ?>">
                            <input type="hidden" name="comentario_id" value="<?= $cmId ?>">
                            <input type="hidden" name="cpag" value="<?= $cmPagina ?>">
                            <span>¿Eliminar este comentario?</span>
                            <button type="submit" class="uc-btn uc-btn--danger">Sí, eliminar</button>
                        </form>
                    </details>

                <?php } ?>

            </div>

        </div>

    <?php } ?>

</article>
