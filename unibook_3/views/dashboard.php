<?php

if(session_status() === PHP_SESSION_NONE){
    session_start();
}

if(!isset($_SESSION['id_usuario'])){

    header("Location: index.php?accion=login");

    exit();
}

/*
|--------------------------------------------------------------------------
| Datos del panel (solo lectura)
|--------------------------------------------------------------------------
| Se reutilizan los controladores que ya existen. No se consulta nada
| nuevo ni se modifica ningún dato: solo se presenta de otra forma.
|--------------------------------------------------------------------------
*/

$ubIdUsuario = (int)$_SESSION['id_usuario'];

$ubNombre = $_SESSION['nombre'] ?? '';

$ubPerfil = $usuarioController->obtenerPorId($ubIdUsuario);

$ubApellido = is_array($ubPerfil) ? ($ubPerfil['apellido'] ?? '') : '';

$ubCorreo = is_array($ubPerfil) ? ($ubPerfil['correo'] ?? '') : '';

$ubInicial = function_exists('mb_substr')
    ? mb_strtoupper(mb_substr($ubNombre, 0, 1, 'UTF-8'), 'UTF-8')
    : strtoupper(substr($ubNombre, 0, 1));

/* Libros disponibles (los mismos que muestra el catálogo). */

$ubDisponibles = [];

$ubUniversidades = [];

$ubTodos = $libroController->listar();

if ($ubTodos) {

    while ($ubFila = $ubTodos->fetch_assoc()) {

        $ubDispo = $ubFila['estado_disponibilidad'] ?? 'disponible';

        if ($ubDispo !== 'disponible') {
            continue;
        }

        $ubDisponibles[] = $ubFila;

        $ubUni = trim($ubFila['universidad'] ?? '');

        if ($ubUni !== '') {
            $ubUniversidades[$ubUni] = ($ubUniversidades[$ubUni] ?? 0) + 1;
        }
    }
}

/* Los más recientes primero. */
usort($ubDisponibles, function ($a, $b) {
    return (int)$b['id'] <=> (int)$a['id'];
});

$ubDestacados = array_slice($ubDisponibles, 0, 5);

arsort($ubUniversidades);

$ubTopUniversidades = array_slice($ubUniversidades, 0, 5, true);

$ubUniversidadLider = count($ubTopUniversidades) > 0
    ? array_keys($ubTopUniversidades)[0]
    : '';

/* Estadísticas personales. */

$ubMisPublicaciones = $libroController->misPublicaciones($ubIdUsuario);

$ubTotalPublicaciones = ($ubMisPublicaciones && isset($ubMisPublicaciones->num_rows))
    ? (int)$ubMisPublicaciones->num_rows
    : 0;

$ubMisCompras = $compraController->misCompras();

$ubTotalCompras = is_array($ubMisCompras) ? count($ubMisCompras) : 0;

$ubEnviados = $intercambioController->enviados();

$ubRecibidos = $intercambioController->recibidos();

$ubTotalIntercambios =
    (is_array($ubEnviados) ? count($ubEnviados) : 0)
    + (is_array($ubRecibidos) ? count($ubRecibidos) : 0);

?>

<?php include "views/partials/navbar.php"; ?>

<div class="ub-dash">

    <!-- ===================== HERO ===================== -->

    <section class="ub-dash__hero ub-panel">

        <div class="ub-dash__hero-copy">

            <span class="ub-pill-greeting">
                Bienvenida, <?php echo htmlspecialchars($ubNombre, ENT_QUOTES, 'UTF-8'); ?> <span aria-hidden="true">👋</span>
            </span>

            <h1>
                Tu espacio para<br>
                comprar, vender e intercambiar<br>
                <span class="ub-gold">libros universitarios.</span>
            </h1>

            <p>
                Conecta con otros estudiantes y consigue los libros
                que necesitas, de forma fácil, segura y económica.
            </p>

            <a href="index.php?accion=catalogo" class="ub-btn-navy">
                Explorar catálogo
                <i class="bi bi-arrow-right" aria-hidden="true"></i>
            </a>

        </div>

        <div class="ub-dash__hero-art" aria-hidden="true">

            <img src="assets/img/hero-libros.jpg" alt="" width="765" height="295">

            <p class="ub-script">Más<br>libros,<br>más<br>posibilidades</p>

        </div>

    </section>


    <!-- ===================== ACCIONES RÁPIDAS ===================== -->

    <div class="ub-dash__quick">

        <a class="ub-quick ub-panel" href="index.php?accion=catalogo">
            <span class="ub-quick__icon"><i class="bi bi-book" aria-hidden="true"></i></span>
            <span>
                <strong>Explorar libros</strong>
                <small>Encuentra lo que necesitas</small>
            </span>
        </a>

        <a class="ub-quick ub-panel" href="index.php?accion=publicar_libro">
            <span class="ub-quick__icon"><i class="bi bi-plus-circle" aria-hidden="true"></i></span>
            <span>
                <strong>Publicar libro</strong>
                <small>Comparte lo que ya no usas</small>
            </span>
        </a>

        <a class="ub-quick ub-panel" href="index.php?accion=intercambios_enviados">
            <span class="ub-quick__icon"><i class="bi bi-arrow-left-right" aria-hidden="true"></i></span>
            <span>
                <strong>Intercambios</strong>
                <small>Encuentra tu próximo libro</small>
            </span>
        </a>

    </div>


    <!-- ===================== LIBROS DESTACADOS ===================== -->

    <section class="ub-dash__featured ub-panel ub-panel--soft">

        <header class="ub-panel__head">

            <h2><i class="bi bi-stars" aria-hidden="true"></i> Libros destacados</h2>

            <a href="index.php?accion=catalogo" class="ub-link">
                Ver todos <i class="bi bi-arrow-right" aria-hidden="true"></i>
            </a>

        </header>

        <?php if (count($ubDestacados) > 0) { ?>

            <div class="ub-featured-grid">

                <?php foreach ($ubDestacados as $ubLibro) {

                    $ubVendedor = $usuarioController->obtenerPorId($ubLibro['usuario_id']);

                    $ubNombreVendedor = is_array($ubVendedor) ? ($ubVendedor['nombre'] ?? '') : '';

                    $ubApellidoVendedor = is_array($ubVendedor) ? ($ubVendedor['apellido'] ?? '') : '';

                    $ubVendedorCorto = trim(
                        $ubNombreVendedor . ' ' .
                        (function_exists('mb_substr')
                            ? mb_substr($ubApellidoVendedor, 0, 1, 'UTF-8')
                            : substr($ubApellidoVendedor, 0, 1))
                    );

                    if ($ubApellidoVendedor !== '') {
                        $ubVendedorCorto .= '.';
                    }

                    $ubInicialVendedor = function_exists('mb_substr')
                        ? mb_strtoupper(mb_substr($ubNombreVendedor, 0, 1, 'UTF-8'), 'UTF-8')
                        : strtoupper(substr($ubNombreVendedor, 0, 1));

                    $ubTipoLibro = strtolower($ubLibro['tipo_publicacion'] ?? '');

                    $ubEstadoLibro = trim($ubLibro['estado'] ?? '');

                    $ubClaseEstado = (strtolower($ubEstadoLibro) === 'nuevo')
                        ? 'ub-chip--new'
                        : 'ub-chip--used';
                ?>

                    <article class="ub-bookcard">

                        <button type="button"
                                class="ub-heart"
                                data-ub-fav="<?php echo (int)$ubLibro['id']; ?>"
                                aria-pressed="false"
                                aria-label="Guardar “<?php echo htmlspecialchars($ubLibro['titulo'], ENT_QUOTES, 'UTF-8'); ?>” en este dispositivo">
                            <i class="bi bi-heart" aria-hidden="true"></i>
                        </button>

                        <a class="ub-bookcard__cover"
                           href="index.php?accion=detalle_libro&id=<?php echo (int)$ubLibro['id']; ?>"
                           tabindex="-1" aria-hidden="true">

                            <?php if (!empty($ubLibro['imagen'])) { ?>

                                <img src="<?php echo htmlspecialchars($ubLibro['imagen'], ENT_QUOTES, 'UTF-8'); ?>"
                                     alt=""
                                     loading="lazy">

                            <?php } else { ?>

                                <span class="ub-bookcard__nocover">
                                    <i class="bi bi-book" aria-hidden="true"></i>
                                </span>

                            <?php } ?>

                        </a>

                        <h3>
                            <a href="index.php?accion=detalle_libro&id=<?php echo (int)$ubLibro['id']; ?>"
                               title="<?php echo htmlspecialchars($ubLibro['titulo'], ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars($ubLibro['titulo'], ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                        </h3>

                        <p class="ub-bookcard__author"><?php echo htmlspecialchars($ubLibro['autor'], ENT_QUOTES, 'UTF-8'); ?></p>

                        <p class="ub-bookcard__price">$<?php echo number_format((float)$ubLibro['precio'], 0, ',', '.'); ?></p>

                        <div class="ub-bookcard__row">

                            <span class="ub-chip <?php echo $ubClaseEstado; ?>">
                                <?php echo htmlspecialchars($ubEstadoLibro !== '' ? $ubEstadoLibro : ucfirst($ubTipoLibro), ENT_QUOTES, 'UTF-8'); ?>
                            </span>

                            <a class="ub-roundbtn"
                               href="index.php?accion=detalle_libro&id=<?php echo (int)$ubLibro['id']; ?>"
                               aria-label="Ver detalles de <?php echo htmlspecialchars($ubLibro['titulo'], ENT_QUOTES, 'UTF-8'); ?>">
                                <i class="bi bi-arrow-right" aria-hidden="true"></i>
                            </a>

                        </div>

                        <div class="ub-bookcard__seller">
                            <span class="ub-avatar ub-avatar--xs"><?php echo htmlspecialchars($ubInicialVendedor, ENT_QUOTES, 'UTF-8'); ?></span>
                            <span><?php echo htmlspecialchars($ubVendedorCorto, ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>

                    </article>

                <?php } ?>

            </div>

        <?php } else { ?>

            <div class="ub-empty">

                <i class="bi bi-book" aria-hidden="true"></i>

                <p>Todavía no hay libros publicados. Sé la primera persona en compartir uno.</p>

                <a href="index.php?accion=publicar_libro" class="ub-btn-navy ub-btn-navy--sm">
                    Publicar libro
                    <i class="bi bi-arrow-right" aria-hidden="true"></i>
                </a>

            </div>

        <?php } ?>

    </section>


    <!-- ===================== FILA INFERIOR ===================== -->

    <div class="ub-dash__bottom">

        <section class="ub-panel ub-unis">

            <h2 class="ub-unis__title">Universidades con más libros</h2>

            <?php if (count($ubTopUniversidades) > 0) { ?>

                <ul class="ub-unis__list">

                    <?php foreach ($ubTopUniversidades as $ubNombreUni => $ubCantidadUni) {

                        $ubSiglas = function_exists('mb_substr')
                            ? mb_strtoupper(mb_substr($ubNombreUni, 0, 2, 'UTF-8'), 'UTF-8')
                            : strtoupper(substr($ubNombreUni, 0, 2));
                    ?>

                        <li>
                            <a href="index.php?accion=catalogo&q=<?php echo urlencode($ubNombreUni); ?>"
                               title="<?php echo (int)$ubCantidadUni; ?> libros">
                                <span class="ub-uni-logo"><?php echo htmlspecialchars($ubSiglas, ENT_QUOTES, 'UTF-8'); ?></span>
                                <span class="ub-uni-name"><?php echo htmlspecialchars($ubNombreUni, ENT_QUOTES, 'UTF-8'); ?></span>
                            </a>
                        </li>

                    <?php } ?>

                </ul>

            <?php } else { ?>

                <p class="ub-unis__empty">Aquí aparecerán las universidades con libros publicados.</p>

            <?php } ?>

        </section>

        <a class="ub-panel ub-eco" href="index.php?accion=catalogo">

            <span class="ub-eco__icon"><i class="bi bi-flower1" aria-hidden="true"></i></span>

            <span class="ub-eco__text">
                <strong>Libros en buen estado</strong>
                <small>Dale una segunda vida a los libros y ayuda al medio ambiente.</small>
            </span>

            <i class="bi bi-chevron-right" aria-hidden="true"></i>

        </a>

    </div>


    <!-- ===================== COLUMNA DERECHA ===================== -->

    <aside class="ub-dash__rail">

        <section class="ub-panel ub-profilecard">

            <div class="ub-profilecard__top">

                <span class="ub-avatar ub-avatar--lg"><?php echo htmlspecialchars($ubInicial, ENT_QUOTES, 'UTF-8'); ?></span>

                <div>
                    <h2><?php echo htmlspecialchars(trim($ubNombre . ' ' . $ubApellido), ENT_QUOTES, 'UTF-8'); ?></h2>
                    <p>Estudiante</p>
                    <?php if ($ubCorreo !== '') { ?>
                        <p class="ub-profilecard__mail"><?php echo htmlspecialchars($ubCorreo, ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php } ?>
                </div>

            </div>

            <dl class="ub-stats">

                <div>
                    <dt>Publicaciones</dt>
                    <dd><?php echo $ubTotalPublicaciones; ?></dd>
                </div>

                <div>
                    <dt>Compras</dt>
                    <dd><?php echo $ubTotalCompras; ?></dd>
                </div>

                <div>
                    <dt>Intercambios</dt>
                    <dd><?php echo $ubTotalIntercambios; ?></dd>
                </div>

            </dl>

            <a class="ub-profilecard__link" href="index.php?accion=perfil">

                <span class="ub-profilecard__shield"><i class="bi bi-shield-check" aria-hidden="true"></i></span>

                <span>
                    <strong>Mi perfil</strong>
                    <small>Revisa y administra tus publicaciones</small>
                </span>

                <i class="bi bi-chevron-right" aria-hidden="true"></i>

            </a>

        </section>

        <section class="ub-panel ub-reco">

            <div class="ub-reco__copy">

                <span class="ub-chip ub-chip--gold"><i class="bi bi-stars" aria-hidden="true"></i> Recomendado para ti</span>

                <h2>¡Encuentra los libros que necesitas para este semestre!</h2>

                <a href="index.php?accion=catalogo" class="ub-btn-navy ub-btn-navy--sm">
                    Ver catálogo
                    <i class="bi bi-arrow-right" aria-hidden="true"></i>
                </a>

            </div>

            <img class="ub-reco__art" src="assets/img/pila-libros.jpg" alt="" width="110" height="100" loading="lazy">

        </section>

    </aside>

</div>

<?php include "views/partials/footer.php"; ?>
