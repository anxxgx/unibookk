<?php

/*
 * FASE 5 — Comunidad UniBook: página principal (feed).
 * Datos: $comunidadDatos (lo arma ComunidadController::preparar()).
 * Estilos: assets/css/ub-comunidad.css · Comportamiento: assets/js/ub-comunidad.js
 */

include "views/partials/comunidad_helpers.php";
include "views/partials/navbar.php";

if (empty($comDatos['listo'])) {
    include "views/partials/comunidad_sin_instalar.php";
    include "views/partials/footer.php";
    return;
}

$publicaciones = $comDatos['publicaciones'] ?? [];
$filtros = $comDatos['filtros'] ?? ['categoria' => '', 'q' => '', 'abiertas' => false, 'mias' => false];
$pagina = (int)($comDatos['pagina'] ?? 1);
$paginas = (int)($comDatos['paginas'] ?? 1);
$total = (int)($comDatos['total'] ?? 0);

$hayFiltros = $filtros['q'] !== '' || $filtros['categoria'] !== '' || $filtros['abiertas'] || $filtros['mias'];

/* Enlace al feed conservando los filtros activos. */
$comLinkFeed = function (array $cambios = []) use ($filtros) {

    $q = [
        'accion'    => 'comunidad',
        'categoria' => $filtros['categoria'],
        'q'         => $filtros['q'],
        'abiertas'  => $filtros['abiertas'] ? 1 : '',
        'mias'      => $filtros['mias'] ? 1 : '',
    ];

    foreach ($cambios as $k => $v) {
        $q[$k] = $v;
    }

    $q = array_filter($q, function ($v) {
        return $v !== '' && $v !== null && $v !== 0;
    });

    return 'index.php?' . http_build_query($q);
};

/* Si el formulario falló, se reabre con lo que el usuario había escrito. */
$comAntiguo = $comUid > 0 ? $comunidadController->tomarAntiguo('crear') : null;

$comPerfil = $comUid > 0 ? $usuarioController->obtenerPorId($comUid) : null;

$formValores = $comAntiguo ?? [
    'categoria' => '',
    'carrera'   => is_array($comPerfil) ? ($comPerfil['carrera'] ?? '') : '',
];

?>

<section class="uc" id="uc">

    <header class="uc-banner">

        <div class="uc-banner__text">
            <h1>Comunidad UniBook</h1>
            <p>Comparte, pregunta y encuentra apoyo entre estudiantes.</p>
        </div>

        <?php if ($comUid > 0) { ?>
            <a class="uc-cta-main" href="#nueva" data-uc-open="nueva" aria-label="Crear publicación">
                <i class="bi bi-plus-lg" aria-hidden="true"></i><span>Crear publicación</span>
            </a>
        <?php } else { ?>
            <a class="uc-cta-main" href="index.php?accion=login" aria-label="Inicia sesión para publicar">
                <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i><span>Inicia sesión para publicar</span>
            </a>
        <?php } ?>

    </header>

    <?php include "views/partials/comunidad_nav.php"; ?>

    <?php $comImprimirFlash(); ?>

    <?php if ($comUid > 0) { ?>

        <details class="uc-composer ub-panel" id="nueva" data-uc-composer<?= $comAntiguo ? ' open' : '' ?>>

            <summary>
                <?= $comAvatar($_SESSION['nombre'] ?? '', is_array($comPerfil) ? ($comPerfil['foto_perfil'] ?? null) : null, 'uc-avatar--sm') ?>
                <span>Nueva publicación · ¿Qué necesitas o qué quieres compartir?</span>
                <i class="bi bi-chevron-down" aria-hidden="true"></i>
            </summary>

            <?php
            $formModo = 'crear';
            $formPub = null;
            $formSugerencias = $comDatos['sugerencias'] ?? [];
            include "views/partials/comunidad_form.php";
            ?>

        </details>

    <?php } ?>


    <!-- ===================== CATEGORÍAS ===================== -->

    <nav class="uc-cats ub-panel" aria-label="Filtrar por categoría">

        <a class="uc-cat<?= $filtros['categoria'] === '' ? ' is-on' : '' ?>"
           href="<?= $comE($comLinkFeed(['categoria' => '', 'pagina' => ''])) ?>"
           <?= $filtros['categoria'] === '' ? 'aria-current="true"' : '' ?>>
            <span class="uc-cat__icon"><i class="bi bi-grid" aria-hidden="true"></i></span>
            <span class="uc-cat__label">Todas</span>
        </a>

        <?php foreach ($comCategorias as $clave => $cat) { ?>
            <a class="uc-cat<?= $filtros['categoria'] === $clave ? ' is-on' : '' ?>"
               href="<?= $comE($comLinkFeed(['categoria' => $clave, 'pagina' => ''])) ?>"
               <?= $filtros['categoria'] === $clave ? 'aria-current="true"' : '' ?>>
                <span class="uc-cat__icon"><i class="bi <?= $comE($cat['icono']) ?>" aria-hidden="true"></i></span>
                <span class="uc-cat__label"><?= $comE($comCatCorta[$clave] ?? $cat['etiqueta']) ?></span>
            </a>
        <?php } ?>

    </nav>


    <!-- ===================== BÚSQUEDA Y FILTROS ===================== -->

    <?php $nFiltros = ($filtros['categoria'] !== '' ? 1 : 0) + ($filtros['abiertas'] ? 1 : 0); ?>

    <div class="uc-searchbox ub-panel">

        <div class="uc-toolbar">

            <form class="uc-search" method="get" action="index.php" role="search">
                <input type="hidden" name="accion" value="comunidad">
                <?php if ($filtros['categoria'] !== '') { ?><input type="hidden" name="categoria" value="<?= $comE($filtros['categoria']) ?>"><?php } ?>
                <?php if ($filtros['abiertas']) { ?><input type="hidden" name="abiertas" value="1"><?php } ?>
                <?php if ($filtros['mias']) { ?><input type="hidden" name="mias" value="1"><?php } ?>
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" name="q" value="<?= $comE($filtros['q']) ?>" maxlength="80"
                       placeholder="Buscar por título, libro o asignatura" aria-label="Buscar en la Comunidad">
                <button type="submit" class="uc-btn uc-btn--sm uc-search__go">Buscar</button>
            </form>

            <details class="uc-pop" data-uc-pop>
                <summary class="uc-btn uc-btn--glass">
                    <i class="bi bi-sliders" aria-hidden="true"></i>Filtros
                    <?php if ($nFiltros > 0) { ?><span class="uc-pill" aria-label="<?= $nFiltros ?> filtros activos"><?= $nFiltros ?></span><?php } ?>
                </summary>

                <form class="uc-pop__panel ub-panel" method="get" action="index.php">
                    <input type="hidden" name="accion" value="comunidad">
                    <?php if ($filtros['q'] !== '') { ?><input type="hidden" name="q" value="<?= $comE($filtros['q']) ?>"><?php } ?>
                    <?php if ($filtros['mias']) { ?><input type="hidden" name="mias" value="1"><?php } ?>

                    <label for="uc-f-cat">Categoría</label>
                    <select class="form-select" id="uc-f-cat" name="categoria">
                        <option value="">Todas las categorías</option>
                        <?php foreach ($comCategorias as $clave => $cat) { ?>
                            <option value="<?= $comE($clave) ?>"<?= $filtros['categoria'] === $clave ? ' selected' : '' ?>><?= $comE($comCatCorta[$clave] ?? $cat['etiqueta']) ?></option>
                        <?php } ?>
                    </select>

                    <label class="uc-check">
                        <input type="checkbox" name="abiertas" value="1"<?= $filtros['abiertas'] ? ' checked' : '' ?>>
                        <span>Mostrar solo publicaciones abiertas</span>
                    </label>

                    <div class="uc-pop__actions">
                        <a class="uc-btn uc-btn--ghost uc-btn--sm" href="index.php?accion=comunidad">Quitar filtros</a>
                        <button type="submit" class="uc-btn uc-btn--primary uc-btn--sm">Aplicar</button>
                    </div>
                </form>
            </details>

            <a class="uc-switch<?= $filtros['abiertas'] ? ' is-on' : '' ?>"
               href="<?= $comE($comLinkFeed(['abiertas' => $filtros['abiertas'] ? '' : 1, 'pagina' => ''])) ?>"
               <?= $filtros['abiertas'] ? 'aria-current="true"' : '' ?>>
                <span class="uc-switch__track" aria-hidden="true"></span>
                <span>Solo abiertas</span>
            </a>

        </div>

        <?php if ($hayFiltros) { ?>
            <div class="uc-chips" role="group" aria-label="Filtros activos">

                <?php if ($filtros['mias']) { ?>
                    <a class="uc-fchip" href="<?= $comE($comLinkFeed(['mias' => '', 'pagina' => ''])) ?>" aria-label="Quitar filtro: Mis publicaciones">Mis publicaciones<i class="bi bi-x" aria-hidden="true"></i></a>
                <?php } ?>

                <?php if ($filtros['categoria'] !== '') { ?>
                    <a class="uc-fchip" href="<?= $comE($comLinkFeed(['categoria' => '', 'pagina' => ''])) ?>" aria-label="Quitar filtro de categoría">Categoría: <?= $comE($comCatCorta[$filtros['categoria']] ?? '') ?><i class="bi bi-x" aria-hidden="true"></i></a>
                <?php } ?>

                <?php if ($filtros['abiertas']) { ?>
                    <a class="uc-fchip" href="<?= $comE($comLinkFeed(['abiertas' => '', 'pagina' => ''])) ?>" aria-label="Quitar filtro de estado">Estado: Abiertas<i class="bi bi-x" aria-hidden="true"></i></a>
                <?php } ?>

                <?php if ($filtros['q'] !== '') { ?>
                    <a class="uc-fchip" href="<?= $comE($comLinkFeed(['q' => '', 'pagina' => ''])) ?>" aria-label="Quitar la búsqueda">Búsqueda: «<?= $comE($filtros['q']) ?>»<i class="bi bi-x" aria-hidden="true"></i></a>
                <?php } ?>

                <a class="uc-fchip uc-fchip--clear" href="index.php?accion=comunidad">Quitar filtros</a>

            </div>
        <?php } ?>

    </div>


    <!-- ===================== PUBLICACIONES ===================== -->

    <?php if (!$publicaciones) { ?>

        <div class="uc-empty ub-panel">

            <span class="uc-empty__icon"><i class="bi bi-chat-square-text" aria-hidden="true"></i></span>

            <?php if ($hayFiltros) { ?>

                <h2>No encontramos publicaciones</h2>
                <p>Prueba con otras palabras o quita algún filtro para ver más resultados.</p>
                <a class="uc-btn uc-btn--glass" href="index.php?accion=comunidad"><i class="bi bi-x-circle" aria-hidden="true"></i>Quitar filtros</a>

            <?php } else { ?>

                <h2>Aún no hay publicaciones</h2>
                <p>Sé la primera persona en pedir un libro, preguntar algo de la universidad o recomendar una lectura.</p>

                <?php if ($comUid > 0) { ?>
                    <a class="ub-btn-navy" href="#nueva" data-uc-open="nueva"><i class="bi bi-plus-lg" aria-hidden="true"></i>Crear publicación</a>
                <?php } else { ?>
                    <a class="ub-btn-navy" href="index.php?accion=login">Inicia sesión para publicar</a>
                <?php } ?>

            <?php } ?>

        </div>

    <?php } else { ?>

        <p class="uc-total"><?= $total ?> <?= $total === 1 ? 'publicación' : 'publicaciones' ?></p>

        <div class="uc-list">

            <?php foreach ($publicaciones as $p) {

                $pId = (int)$p['id'];
                $pNombre = trim(($p['autor_nombre'] ?? '') . ' ' . ($p['autor_apellido'] ?? ''));
                $pCat = $comCategorias[$p['categoria']] ?? $comCategorias['otro'];
                $pCatNombre = $comCatCorta[$p['categoria']] ?? $pCat['etiqueta'];
                $pEstado = $p['estado'];
                $pImg = $comunidadController->rutaImagenValida($p['imagen'] ?? null);
                $pMia = $comUid > 0 && (int)$p['usuario_id'] === $comUid;
                $pComentarios = (int)$p['total_comentarios'];
                $pResumen = function_exists('mb_strimwidth')
                    ? mb_strimwidth((string)$p['contenido'], 0, 200, '…', 'UTF-8')
                    : substr((string)$p['contenido'], 0, 200);

                ?>

                <article class="uc-card ub-panel<?= $pEstado !== 'activa' ? ' is-' . $comE($pEstado) : '' ?>">

                    <span class="uc-card__icon" aria-hidden="true"><i class="bi <?= $comE($pCat['icono']) ?>"></i></span>

                    <div class="uc-card__body">

                        <div class="uc-card__top">

                            <span class="uc-card__icon uc-card__icon--inline" aria-hidden="true"><i class="bi <?= $comE($pCat['icono']) ?>"></i></span>

                            <span class="uc-tag"><?= $comE($pCatNombre) ?></span>

                            <span class="uc-status uc-status--<?= $comE($pEstado) ?>"><?= $comE($comEstadoNombre[$pEstado] ?? '') ?></span>

                            <?php if ($pMia) { ?><span class="uc-tag uc-tag--mine">Tu publicación</span><?php } ?>

                            <time class="uc-card__time" datetime="<?= $comE($p['fecha_creacion']) ?>" title="<?= $comE($comFechaCompleta($p['fecha_creacion'])) ?>"><?= $comE($comFecha($p['fecha_creacion'])) ?></time>

                        </div>

                        <div class="uc-card__main<?= $pImg ? ' has-img' : '' ?>">

                            <div class="uc-card__text">

                                <h2><a href="index.php?accion=comunidad_publicacion&amp;id=<?= $pId ?>"><?= $comE($p['titulo']) ?></a></h2>

                                <p class="uc-card__excerpt"><?= $comE($pResumen) ?></p>

                                <?php if (!empty($p['asignatura']) || !empty($p['carrera'])) { ?>
                                    <p class="uc-card__meta">
                                        <?php if (!empty($p['asignatura'])) { ?><span><i class="bi bi-journal-text" aria-hidden="true"></i><?= $comE($p['asignatura']) ?></span><?php } ?>
                                        <?php if (!empty($p['carrera'])) { ?><span><i class="bi bi-mortarboard" aria-hidden="true"></i><?= $comE($p['carrera']) ?></span><?php } ?>
                                    </p>
                                <?php } ?>

                            </div>

                            <?php if ($pImg) { ?>
                                <a class="uc-card__img" href="index.php?accion=comunidad_publicacion&amp;id=<?= $pId ?>" tabindex="-1" aria-hidden="true">
                                    <img src="<?= $comE($pImg) ?>" alt="" loading="lazy">
                                </a>
                            <?php } ?>

                        </div>

                        <footer class="uc-card__foot">

                            <div class="uc-card__author">
                                <?= $comAvatar($pNombre, $p['autor_foto'] ?? null, 'uc-avatar--md') ?>
                                <span>
                                    <strong><?= $comE($pNombre) ?></strong>
                                    <?php if (!empty($p['autor_universidad'])) { ?><small><?= $comE($p['autor_universidad']) ?></small><?php } ?>
                                </span>
                                <time class="uc-card__time uc-card__time--foot" datetime="<?= $comE($p['fecha_creacion']) ?>"><?= $comE($comFecha($p['fecha_creacion'])) ?></time>
                            </div>

                            <span class="uc-card__date"><i class="bi bi-calendar3" aria-hidden="true"></i><?= $comE($comFechaLarga($p['fecha_creacion'])) ?></span>

                            <div class="uc-card__actions">

                                <a class="uc-btn uc-btn--glass uc-btn--sm" href="index.php?accion=comunidad_publicacion&amp;id=<?= $pId ?>#comentarios"
                                   aria-label="Comentar: <?= $pComentarios ?> <?= $pComentarios === 1 ? 'comentario' : 'comentarios' ?>">
                                    <i class="bi bi-chat-left-text" aria-hidden="true"></i>Comentar (<?= $pComentarios ?>)
                                </a>

                                <?php if ($pMia) { ?>
                                    <a class="uc-btn uc-btn--primary uc-btn--sm" href="index.php?accion=comunidad_publicacion&amp;id=<?= $pId ?>">
                                        <i class="bi bi-gear" aria-hidden="true"></i>Gestionar
                                    </a>
                                <?php } elseif ($comUid > 0 && $pEstado === 'activa') { ?>
                                    <a class="uc-btn uc-btn--primary uc-btn--sm" href="index.php?accion=comunidad_chat&amp;publicacion=<?= $pId ?>">
                                        <i class="bi bi-send" aria-hidden="true"></i>Enviar mensaje
                                    </a>
                                <?php } ?>

                            </div>

                        </footer>

                    </div>

                </article>

            <?php } ?>

        </div>

        <?php if ($paginas > 1) { ?>
            <nav class="uc-pager" aria-label="Paginación">

                <?php if ($pagina > 1) { ?>
                    <a class="uc-btn uc-btn--glass" href="<?= $comE($comLinkFeed(['pagina' => $pagina - 1])) ?>"><i class="bi bi-chevron-left" aria-hidden="true"></i>Anterior</a>
                <?php } else { ?>
                    <span></span>
                <?php } ?>

                <span class="uc-pager__info">Página <?= $pagina ?> de <?= $paginas ?></span>

                <?php if ($pagina < $paginas) { ?>
                    <a class="uc-btn uc-btn--glass" href="<?= $comE($comLinkFeed(['pagina' => $pagina + 1])) ?>">Siguiente<i class="bi bi-chevron-right" aria-hidden="true"></i></a>
                <?php } else { ?>
                    <span></span>
                <?php } ?>

            </nav>
        <?php } ?>

    <?php } ?>

</section>

<?php include "views/partials/footer.php"; ?>
