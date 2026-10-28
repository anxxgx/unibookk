<?php if (session_status() === PHP_SESSION_NONE) {
    session_start();
} ?>
<?php
$datos = $datos ?? null;

if (!$datos) {
    return;
}
?>

<?php if (isset($_SESSION['id_usuario'])) { ?>

    <?php include "views/partials/navbar.php"; ?>

<?php } else { ?>

    <nav class="navbar ub-nav">
        <div class="container justify-content-between">
            <a class="navbar-brand" href="index.php">
                <span class="mark"><i class="bi bi-bookshelf ub-icon" aria-hidden="true"></i></span> UniBook
            </a>

            <a href="index.php?accion=login" class="btn-ub btn-ub-outline on-dark btn-ub-sm">
                Iniciar sesión
            </a>
        </div>
    </nav>

<?php } ?>


<?php
/*
|--------------------------------------------------------------------------
| CATÁLOGO
|--------------------------------------------------------------------------
| Conservamos el diseño original.
|
| Disponible:
|   aparece en el catálogo principal.
|
| Reservado:
|   aparece en una sección aparte debajo de los disponibles.
|
| Vendido / Intercambiado:
|   no aparece en el catálogo.
|   Estos libros quedarán para la sección Historia.
|--------------------------------------------------------------------------
*/

$librosDisponibles = [];
$librosReservados = [];

while ($libro = $datos->fetch_assoc()) {

    $disponibilidad = $libro['estado_disponibilidad'] ?? 'disponible';

    if ($disponibilidad === 'disponible') {
        $librosDisponibles[] = $libro;
    } elseif ($disponibilidad === 'reservado') {
        $librosReservados[] = $libro;
    }
}

$totalLibrosCatalogo = count($librosDisponibles);
$totalReservados = count($librosReservados);

/* Búsqueda activa (si viene en la URL): se reenvía al detalle para que
   "Volver al catálogo" pueda regresar con la misma búsqueda. */
$ubBusquedaCatalogo = trim((string)($_GET['q'] ?? ''));
$ubParamBusqueda = $ubBusquedaCatalogo !== ''
    ? '&q=' . rawurlencode($ubBusquedaCatalogo)
    : '';
?>


<!-- =========================================================
     HERO DEL CATÁLOGO
     ========================================================= -->

<section class="catalog-hero">
    <div class="container">

        <div class="catalog-hero__inner reveal">

            <span class="ub-eyebrow on-dark">
                381.46 — Catálogo general
            </span>

            <h1 class="catalog-hero__title">
                Encuentra tu próximo <em>libro de texto.</em>
            </h1>

            <p class="catalog-hero__lead">
                <?php echo $totalLibrosCatalogo; ?> publicaciones activas de estudiantes
                de distintas universidades y carreras de Medellín.
            </p>

            <div class="catalog-search">

                <span class="catalog-search__icon" aria-hidden="true">
                    <i class="bi bi-search ub-icon" aria-hidden="true"></i>
                </span>

                <input
                    type="text"
                    id="buscador"
                    class="catalog-search__input"
                    placeholder="Buscar por título, autor o materia...">

            </div>

        </div>

    </div>
</section>


<div class="ub-section">

    <div class="container">

        <div class="catalog-toolbar reveal">

            <p class="catalog-count" id="catalogoContador">
                Mostrando <b><?php echo $totalLibrosCatalogo; ?></b>
                <?php echo $totalLibrosCatalogo == 1 ? 'libro' : 'libros'; ?>
            </p>

        </div>


        <!-- =====================================================
             LIBROS DISPONIBLES
             ===================================================== -->

        <div class="row catalog-grid" id="contenedorLibros">

            <?php foreach ($librosDisponibles as $libro) { ?>

                <div
                    class="col-md-4 mb-4 libro-card reveal"
                    data-titulo="<?php echo strtolower(htmlspecialchars($libro['titulo'])); ?>"
                    data-autor="<?php echo strtolower(htmlspecialchars($libro['autor'])); ?>"
                    data-materia="<?php echo strtolower(htmlspecialchars($libro['materia'])); ?>">

                    <div class="book-card">

                        <?php

                        $tipo = strtolower($libro['tipo_publicacion']);

                        if ($tipo === 'venta') {
                            $stampClass = 'stamp-venta';
                        } else {
                            $stampClass = 'stamp-intercambio';
                        }

                        $tagClass = str_replace('stamp-', 'tag-', $stampClass);

                        ?>


                        <div class="cover">

                            <span class="tag <?php echo $tagClass; ?>">
                                <?php echo htmlspecialchars($libro['tipo_publicacion']); ?>
                            </span>


                            <?php if (!empty($libro['imagen'])) { ?>

                                <img
                                    class="cover__bg"
                                    src="<?php echo htmlspecialchars($libro['imagen']); ?>"
                                    alt=""
                                    aria-hidden="true"
                                    loading="lazy">

                                <img
                                    class="cover__img"
                                    src="<?php echo htmlspecialchars($libro['imagen']); ?>"
                                    alt="<?php echo htmlspecialchars($libro['titulo']); ?>"
                                    loading="lazy">

                            <?php } else { ?>

                                <div class="no-image">
                                    <i class="bi bi-book" aria-hidden="true"></i>
                                    <span>Sin portada</span>
                                </div>

                            <?php } ?>

                        </div>


                        <div class="body">

                            <h5>
                                <?php echo htmlspecialchars($libro['titulo']); ?>
                            </h5>

                            <div class="meta-row">

                                <span>
                                    <i class="bi bi-person ub-icon" aria-hidden="true"></i> <b>
                                        <?php echo htmlspecialchars($libro['autor']); ?>
                                    </b>
                                </span>

                                <span>
                                    <i class="bi bi-building ub-icon" aria-hidden="true"></i> <?php echo htmlspecialchars($libro['universidad']); ?>
                                </span>

                                <span>
                                    <i class="bi bi-mortarboard ub-icon" aria-hidden="true"></i>
                                    <?php echo htmlspecialchars($libro['carrera']); ?>
                                    · Sem.
                                    <?php echo htmlspecialchars($libro['semestre']); ?>
                                </span>

                                <span>
                                    <i class="bi bi-journal-bookmark ub-icon" aria-hidden="true"></i> <?php echo htmlspecialchars($libro['materia']); ?>
                                </span>

                            </div>


                            <div class="price">
                                $<?php echo number_format($libro['precio']); ?>
                            </div>

                        </div>


                        <div class="footer">

                            <a
                                href="index.php?accion=detalle_libro&id=<?php echo (int)$libro['id']; ?><?php echo htmlspecialchars($ubParamBusqueda); ?>"
                                data-ub-detalle
                                aria-label="Ver detalles de <?php echo htmlspecialchars($libro['titulo']); ?>"
                                class="btn-ub btn-ub-dark btn-ub-block">
                                Ver detalles
                            </a>

                        </div>

                    </div>

                </div>

            <?php } ?>

        </div>


        <!-- =====================================================
             SIN RESULTADOS DE BÚSQUEDA
             ===================================================== -->

        <div
            class="catalog-empty"
            id="catalogoVacio"
            style="display:none;">

            <div class="catalog-empty__icon">
                <i class="bi bi-inbox ub-icon" aria-hidden="true"></i>
            </div>

            <h5 class="fw-bold mb-1">
                No encontramos libros con esa búsqueda
            </h5>

            <p class="text-muted mb-0">
                Intenta con otro título, autor o materia.
            </p>

        </div>


        <!-- =====================================================
             LIBROS RESERVADOS
             ===================================================== -->

        <?php if ($totalReservados > 0) { ?>

            <div
                class="catalog-toolbar reveal"
                style="margin-top: 55px;">

                <p class="catalog-count">
                    <b><?php echo $totalReservados; ?></b>
                    <?php echo $totalReservados == 1 ? 'libro reservado' : 'libros reservados'; ?>
                </p>

            </div>


            <div class="row catalog-grid">

                <?php foreach ($librosReservados as $libro) { ?>

                    <div
                        class="col-md-4 mb-4 libro-card reveal"
                        data-titulo="<?php echo strtolower(htmlspecialchars($libro['titulo'])); ?>"
                        data-autor="<?php echo strtolower(htmlspecialchars($libro['autor'])); ?>"
                        data-materia="<?php echo strtolower(htmlspecialchars($libro['materia'])); ?>">

                        <div class="book-card">

                            <?php

                            $tipo = strtolower($libro['tipo_publicacion']);

                            $stampClass =
                                $tipo === 'venta'
                                ? 'stamp-venta'
                                : 'stamp-intercambio';

                            $tagClass = str_replace('stamp-', 'tag-', $stampClass);

                            ?>


                            <div class="cover cover--unavailable">

                                <span class="tag <?php echo $tagClass; ?>">
                                    <?php echo htmlspecialchars($libro['tipo_publicacion']); ?>
                                </span>


                                <?php if (!empty($libro['imagen'])) { ?>

                                    <img
                                        class="cover__bg"
                                        src="<?php echo htmlspecialchars($libro['imagen']); ?>"
                                        alt=""
                                        aria-hidden="true"
                                        loading="lazy">

                                    <img
                                        class="cover__img"
                                        src="<?php echo htmlspecialchars($libro['imagen']); ?>"
                                        alt="<?php echo htmlspecialchars($libro['titulo']); ?>"
                                        loading="lazy">

                                <?php } else { ?>

                                    <div class="no-image">
                                        <i class="bi bi-book" aria-hidden="true"></i>
                                        <span>Sin portada</span>
                                    </div>

                                <?php } ?>


                                <div class="cover__overlay">

                                    <span class="stamp badge-disp-reservado">
                                        Reservado
                                    </span>

                                </div>

                            </div>


                            <div class="body">

                                <h5>
                                    <?php echo htmlspecialchars($libro['titulo']); ?>
                                </h5>

                                <div class="meta-row">

                                    <span>
                                        <i class="bi bi-person ub-icon" aria-hidden="true"></i> <b>
                                            <?php echo htmlspecialchars($libro['autor']); ?>
                                        </b>
                                    </span>

                                    <span>
                                        <i class="bi bi-building ub-icon" aria-hidden="true"></i>
                                        <?php echo htmlspecialchars($libro['universidad']); ?>
                                    </span>

                                    <span>
                                        <i class="bi bi-mortarboard ub-icon" aria-hidden="true"></i>
                                        <?php echo htmlspecialchars($libro['carrera']); ?>
                                        · Sem.
                                        <?php echo htmlspecialchars($libro['semestre']); ?>
                                    </span>

                                    <span>
                                        <i class="bi bi-journal-bookmark ub-icon" aria-hidden="true"></i>
                                        <?php echo htmlspecialchars($libro['materia']); ?>
                                    </span>

                                </div>


                                <div class="price">
                                    $<?php echo number_format($libro['precio']); ?>
                                </div>

                            </div>


                            <div class="footer">

                                <a
                                    href="index.php?accion=detalle_libro&id=<?php echo (int)$libro['id']; ?><?php echo htmlspecialchars($ubParamBusqueda); ?>"
                                    data-ub-detalle
                                    aria-label="Ver detalles de <?php echo htmlspecialchars($libro['titulo']); ?>"
                                    class="btn-ub btn-ub-dark btn-ub-block">
                                    Ver detalles
                                </a>

                            </div>

                        </div>

                    </div>

                <?php } ?>

            </div>

        <?php } ?>


    </div>

</div>


<?php include "views/partials/footer.php"; ?>


<script>
    /* Conserva la búsqueda escrita en el catálogo: al abrir un libro se añade
       ?q= al enlace, de modo que "Volver al catálogo" regresa con ella. */
    (function () {
        var buscador = document.getElementById("buscador");
        if (!buscador) return;

        document.querySelectorAll("a[data-ub-detalle]").forEach(function (a) {
            a.addEventListener("click", function () {
                var q = buscador.value.trim();
                var url;
                try {
                    url = new URL(a.getAttribute("href"), window.location.href);
                } catch (e) { return; }
                if (q) url.searchParams.set("q", q);
                else url.searchParams.delete("q");
                a.setAttribute("href", url.pathname.split("/").pop() + url.search);
            });
        });
    })();
</script>
