<?php

/*
|--------------------------------------------------------------------------
| Mis intercambios — cabecera con pestañas
| Espera: $ixActiva ('recibidos' | 'enviados'), $ixTotalRecibidos,
| $ixTotalEnviados (de intercambios_ayudas.php).
|--------------------------------------------------------------------------
*/

$ixActiva = ($ixActiva ?? 'recibidos') === 'enviados' ? 'enviados' : 'recibidos';

?>

<header class="ix-hero ix-rise" style="--i:0">

    <div class="ix-hero__top">

        <span class="ix-chip">
            <i class="bi bi-arrow-left-right" aria-hidden="true"></i>
            Mi actividad
        </span>

        <a href="index.php?accion=catalogo" class="ix-btn ix-btn--glass ix-btn--sm">
            <i class="bi bi-bookshelf" aria-hidden="true"></i>
            Ver catálogo
        </a>

    </div>

    <h1 class="ix-title">Mis intercambios</h1>

    <p class="ix-lead">
        Gestiona las propuestas que recibes por tus libros y sigue
        el estado de las que has enviado a otros estudiantes.
    </p>

    <nav class="ix-tabs" aria-label="Tipo de intercambios">

        <a
            href="index.php?accion=intercambios_recibidos"
            class="ix-tab<?= $ixActiva === 'recibidos' ? ' is-active' : '' ?>"
            <?= $ixActiva === 'recibidos' ? 'aria-current="page"' : '' ?>
        >
            <i class="bi bi-inbox" aria-hidden="true"></i>
            <span>Recibidos</span>
            <span class="ix-tab__count" aria-label="<?= (int)$ixTotalRecibidos ?> solicitudes recibidas"><?= (int)$ixTotalRecibidos ?></span>
        </a>

        <a
            href="index.php?accion=intercambios_enviados"
            class="ix-tab<?= $ixActiva === 'enviados' ? ' is-active' : '' ?>"
            <?= $ixActiva === 'enviados' ? 'aria-current="page"' : '' ?>
        >
            <i class="bi bi-send" aria-hidden="true"></i>
            <span>Enviados</span>
            <span class="ix-tab__count" aria-label="<?= (int)$ixTotalEnviados ?> solicitudes enviadas"><?= (int)$ixTotalEnviados ?></span>
        </a>

    </nav>

</header>
