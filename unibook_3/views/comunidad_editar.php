<?php

/* FASE 5 — Comunidad UniBook: editar una publicación propia. */

include "views/partials/comunidad_helpers.php";
include "views/partials/navbar.php";

if (empty($comDatos['listo']) || empty($comDatos['pub'])) {
    include "views/partials/comunidad_sin_instalar.php";
    include "views/partials/footer.php";
    return;
}

$formPub = $comDatos['pub'];
$formModo = 'editar';
$formSugerencias = $comDatos['sugerencias'] ?? [];

/* Si el guardado falló se conserva lo escrito; si no, los datos actuales. */
$formValores = $comunidadController->tomarAntiguo('editar_' . (int)$formPub['id']) ?? [
    'categoria'    => $formPub['categoria'],
    'titulo'       => $formPub['titulo'],
    'contenido'    => $formPub['contenido'],
    'asignatura'   => $formPub['asignatura'],
    'carrera'      => $formPub['carrera'],
    'libro_titulo' => $formPub['libro_titulo'],
    'libro_autor'  => $formPub['libro_autor'],
    'modalidad'    => $formPub['modalidad'],
];

?>

<section class="uc" id="uc">

    <header class="uc-pagehead">

        <div class="uc-pagehead__text">
            <h1>Editar publicación</h1>
            <p>Actualiza la información. Tus comentarios y mensajes se conservan.</p>
        </div>

        <a class="uc-btn uc-btn--glass uc-pagehead__back" href="index.php?accion=comunidad_publicacion&amp;id=<?= (int)$formPub['id'] ?>">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>Volver a la publicación
        </a>

    </header>

    <?php $comImprimirFlash(); ?>

    <div class="uc-panel ub-panel uc-panel--form">
        <?php include "views/partials/comunidad_form.php"; ?>
    </div>

</section>

<?php include "views/partials/footer.php"; ?>
