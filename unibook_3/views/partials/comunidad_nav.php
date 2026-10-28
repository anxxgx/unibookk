<?php

/*
 * Pestañas de la Comunidad (Publicaciones · Mis publicaciones · Mensajes).
 * Las mismas en todas las pantallas. Solo enlaza rutas existentes.
 * Entrada: $accion (index.php), $comUid, $comSinLeer, $comDatos['filtros']['mias'].
 */

$tabMias = !empty($comDatos['filtros']['mias']);
$tabEnMensajes = in_array($accion, ['comunidad_mensajes', 'comunidad_chat'], true);
$tabEnPublicaciones = !$tabEnMensajes && !$tabMias;

?>
<nav class="uc-tabs" aria-label="Secciones de la Comunidad">

    <a class="uc-tab<?= $tabEnPublicaciones ? ' is-on' : '' ?>"
       href="index.php?accion=comunidad"
       <?= $tabEnPublicaciones ? 'aria-current="page"' : '' ?>>
        <i class="bi bi-chat-square-text" aria-hidden="true"></i><span>Publicaciones</span>
    </a>

    <?php if ($comUid > 0) { ?>

        <a class="uc-tab<?= $tabMias ? ' is-on' : '' ?>"
           href="index.php?accion=comunidad&amp;mias=1"
           <?= $tabMias ? 'aria-current="page"' : '' ?>>
            <i class="bi bi-person" aria-hidden="true"></i><span>Mis publicaciones</span>
        </a>

        <a class="uc-tab<?= $tabEnMensajes ? ' is-on' : '' ?>"
           href="index.php?accion=comunidad_mensajes"
           <?= $tabEnMensajes ? 'aria-current="page"' : '' ?>>
            <i class="bi bi-envelope" aria-hidden="true"></i><span>Mensajes</span>
            <?php if ($comSinLeer > 0) { ?>
                <span class="uc-tab__count" aria-label="<?= $comSinLeer ?> sin leer">(<?= $comSinLeer > 99 ? '99+' : $comSinLeer ?>)</span>
            <?php } ?>
        </a>

    <?php } ?>

</nav>
