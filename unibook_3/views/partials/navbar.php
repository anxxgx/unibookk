<?php

if (session_status() === PHP_SESSION_NONE) {

    session_start();

}

/*
|--------------------------------------------------------------------------
| Contador de notificaciones (lógica original, sin cambios)
|--------------------------------------------------------------------------
*/

$notificacionesNoLeidasNavbar = 0;

if (isset($notificacionController) && isset($_SESSION['id_usuario'])) {

    $notificacionesNoLeidasNavbar =
        $notificacionController->contarNoLeidas();

}

/*
|--------------------------------------------------------------------------
| Mensajes sin leer (solo lectura, método ya existente del controlador)
|--------------------------------------------------------------------------
*/

$mensajesNoLeidosNavbar = 0;

if (isset($mensajeController) && isset($_SESSION['id_usuario'])) {

    $mensajesNoLeidosNavbar =
        (int)$mensajeController->contarNoLeidos();

}

/*
|--------------------------------------------------------------------------
| Mensajes privados de la Comunidad sin leer (FASE 5, solo lectura)
|--------------------------------------------------------------------------
*/

$comunidadNoLeidosNavbar = 0;

if (isset($comunidadController) && isset($_SESSION['id_usuario'])) {

    $comunidadNoLeidosNavbar =
        (int)$comunidadController->contarNoLeidos();

}

/*
|--------------------------------------------------------------------------
| Datos de presentación del armazón (barra lateral + barra superior)
|--------------------------------------------------------------------------
*/

$ubAccionActual = $_GET['accion'] ?? 'dashboard';

$ubNombreNavbar = $_SESSION['nombre'] ?? '';

$ubInicialNavbar = function_exists('mb_substr')
    ? mb_strtoupper(mb_substr($ubNombreNavbar, 0, 1, 'UTF-8'), 'UTF-8')
    : strtoupper(substr($ubNombreNavbar, 0, 1));

$ubTextoBuscar = isset($_GET['q']) ? (string)$_GET['q'] : '';

/* Marca como activa la opción que corresponde a la página actual. */
$ubActivo = function ($acciones) use ($ubAccionActual) {

    return in_array($ubAccionActual, (array)$acciones, true)
        ? ' is-active'
        : '';

};

$ubGrupoIntercambiosAbierto = in_array(
    $ubAccionActual,
    ['intercambios_enviados', 'intercambios_recibidos', 'solicitar_intercambio'],
    true
);

$ubFormatoContador = function ($n) {

    return ((int)$n > 99) ? '99+' : (string)(int)$n;

};

/* Indica al footer que debe cerrar el armazón. */
$ubShellAbierto = true;

?>

<div class="ub-app" id="ubApp">

    <!-- ===================== BARRA LATERAL ===================== -->

    <aside class="ub-sidebar" id="ubSidebar" aria-label="Navegación principal">

        <a class="ub-brand" href="index.php?accion=dashboard">

            <img class="ub-brand__mark" src="assets/img/unibook-logo.svg" alt="" width="47" height="44">

            <span class="ub-brand__text">
                <span class="ub-brand__name">UniBook</span>
                <span class="ub-brand__city">Medellín</span>
            </span>

        </a>

        <p class="ub-tagline">Estudia más, paga menos.</p>

        <nav class="ub-sidenav">

            <a class="ub-sidenav__item<?php echo $ubActivo('dashboard'); ?>"
               href="index.php?accion=dashboard">
                <i class="bi bi-house-door-fill" aria-hidden="true"></i>
                <span>Inicio</span>
            </a>

            <a class="ub-sidenav__item<?php echo $ubActivo(['catalogo', 'detalle_libro', 'checkout']); ?>"
               href="index.php?accion=catalogo">
                <i class="bi bi-search" aria-hidden="true"></i>
                <span>Catálogo</span>
            </a>

            <a class="ub-sidenav__item<?php echo $ubActivo(['comunidad', 'comunidad_publicacion', 'comunidad_editar', 'comunidad_mensajes', 'comunidad_chat']); ?>"
               href="index.php?accion=comunidad">
                <i class="bi bi-people" aria-hidden="true"></i>
                <span>Comunidad</span>
                <?php if ($comunidadNoLeidosNavbar > 0): ?>
                    <span class="ub-count" aria-label="<?php echo (int)$comunidadNoLeidosNavbar; ?> mensajes de la Comunidad sin leer"><?php echo $ubFormatoContador($comunidadNoLeidosNavbar); ?></span>
                <?php endif; ?>
            </a>

            <a class="ub-sidenav__item<?php echo $ubActivo('publicar_libro'); ?>"
               href="index.php?accion=publicar_libro">
                <i class="bi bi-plus-circle" aria-hidden="true"></i>
                <span>Publicar libro</span>
            </a>

            <a class="ub-sidenav__item<?php echo $ubActivo(['mis_compras', 'detalle_compra', 'compra_confirmada']); ?>"
               href="index.php?accion=mis_compras">
                <i class="bi bi-bag" aria-hidden="true"></i>
                <span>Mis compras</span>
            </a>

            <a class="ub-sidenav__item<?php echo $ubActivo('mis_ventas'); ?>"
               href="index.php?accion=mis_ventas">
                <i class="bi bi-cash-stack" aria-hidden="true"></i>
                <span>Mis ventas</span>
            </a>

            <details class="ub-sidenav__group"<?php echo $ubGrupoIntercambiosAbierto ? ' open' : ''; ?>>

                <summary class="ub-sidenav__item<?php echo $ubGrupoIntercambiosAbierto ? ' is-active' : ''; ?>">
                    <span class="ub-sidenav__row">
                        <i class="bi bi-arrow-left-right" aria-hidden="true"></i>
                        <span>Mis intercambios</span>
                        <i class="bi bi-chevron-down ub-sidenav__caret" aria-hidden="true"></i>
                    </span>
                </summary>

                <div class="ub-sidenav__sub">

                    <a class="<?php echo ($ubAccionActual === 'intercambios_enviados') ? 'is-active' : ''; ?>"
                       href="index.php?accion=intercambios_enviados">
                        Solicitudes enviadas
                    </a>

                    <a class="<?php echo ($ubAccionActual === 'intercambios_recibidos') ? 'is-active' : ''; ?>"
                       href="index.php?accion=intercambios_recibidos">
                        Solicitudes recibidas
                    </a>

                </div>

            </details>

            <a class="ub-sidenav__item<?php echo $ubActivo(['mensajes', 'chat']); ?>"
               href="index.php?accion=mensajes">
                <i class="bi bi-chat" aria-hidden="true"></i>
                <span>Chat</span>
                <?php if ($mensajesNoLeidosNavbar > 0): ?>
                    <span class="ub-count" aria-label="<?php echo (int)$mensajesNoLeidosNavbar; ?> mensajes sin leer"><?php echo $ubFormatoContador($mensajesNoLeidosNavbar); ?></span>
                <?php endif; ?>
            </a>

            <a class="ub-sidenav__item<?php echo $ubActivo('notificaciones'); ?>"
               href="index.php?accion=notificaciones">
                <i class="bi bi-bell" aria-hidden="true"></i>
                <span>Notificaciones</span>
                <?php if ($notificacionesNoLeidasNavbar > 0): ?>
                    <span class="ub-count" aria-label="<?php echo (int)$notificacionesNoLeidasNavbar; ?> notificaciones sin leer"><?php echo $ubFormatoContador($notificacionesNoLeidasNavbar); ?></span>
                <?php endif; ?>
            </a>

            <a class="ub-sidenav__item<?php echo $ubActivo('historia'); ?>"
               href="index.php?accion=historia">
                <i class="bi bi-clock-history" aria-hidden="true"></i>
                <span>Historia</span>
            </a>

            <a class="ub-sidenav__item<?php echo $ubActivo('perfil'); ?>"
               href="index.php?accion=perfil">
                <i class="bi bi-person" aria-hidden="true"></i>
                <span>Mi perfil</span>
            </a>

        </nav>

        <a class="ub-campus" href="index.php?accion=catalogo">

            <span class="ub-campus__photo">
                <img src="assets/img/campus-medellin.jpg" alt="" loading="lazy">
            </span>

            <span class="ub-campus__row">

                <span class="ub-campus__text">
                    <strong>UniBook Medellín</strong>
                    <small><i class="bi bi-geo-alt" aria-hidden="true"></i> Comunidad universitaria</small>
                </span>

                <i class="bi bi-chevron-right" aria-hidden="true"></i>

            </span>

        </a>

    </aside>


    <!-- ===================== ÁREA PRINCIPAL ===================== -->

    <div class="ub-shell-main">

        <header class="ub-topbar">

            <button type="button" class="ub-iconbtn ub-menu-btn" data-ub-menu aria-label="Abrir menú" aria-controls="ubSidebar">
                <i class="bi bi-list" aria-hidden="true"></i>
            </button>

            <form class="ub-search" action="index.php" method="get" role="search">

                <input type="hidden" name="accion" value="catalogo">

                <i class="bi bi-search" aria-hidden="true"></i>

                <input type="search"
                       name="q"
                       value="<?php echo htmlspecialchars($ubTextoBuscar, ENT_QUOTES, 'UTF-8'); ?>"
                       placeholder="Buscar libros, autores, materias, universidades..."
                       aria-label="Buscar libros, autores, materias o universidades"
                       autocomplete="off"
                       data-ub-search>

                <kbd aria-hidden="true">⌘ K</kbd>

            </form>

            <div class="ub-topbar__actions">

                <a class="ub-iconbtn ub-iconbtn--bell"
                   href="index.php?accion=notificaciones"
                   aria-label="Notificaciones<?php echo $notificacionesNoLeidasNavbar > 0 ? ' (' . (int)$notificacionesNoLeidasNavbar . ' sin leer)' : ''; ?>">
                    <i class="bi bi-bell" aria-hidden="true"></i>
                    <?php if ($notificacionesNoLeidasNavbar > 0): ?>
                        <span class="ub-count ub-count--sm"><?php echo $ubFormatoContador($notificacionesNoLeidasNavbar); ?></span>
                    <?php endif; ?>
                </a>

                <button type="button" class="ub-iconbtn ub-iconbtn--glass" data-ub-theme aria-label="Cambiar entre modo claro y oscuro" aria-pressed="false">
                    <i class="bi bi-moon" aria-hidden="true"></i>
                </button>

                <details class="ub-usermenu">

                    <summary>

                        <span class="ub-avatar"><?php echo htmlspecialchars($ubInicialNavbar, ENT_QUOTES, 'UTF-8'); ?></span>

                        <span class="ub-usermenu__text">
                            <strong><?php echo htmlspecialchars($ubNombreNavbar, ENT_QUOTES, 'UTF-8'); ?></strong>
                            <small>Estudiante</small>
                        </span>

                        <i class="bi bi-chevron-down" aria-hidden="true"></i>

                    </summary>

                    <div class="ub-usermenu__panel">

                        <a href="index.php?accion=perfil"><i class="bi bi-person" aria-hidden="true"></i> Mi perfil</a>

                        <a href="index.php?accion=historia"><i class="bi bi-clock-history" aria-hidden="true"></i> Historia</a>

                        <a href="logout.php" class="is-danger"><i class="bi bi-box-arrow-right" aria-hidden="true"></i> Cerrar sesión</a>

                    </div>

                </details>

            </div>

        </header>

        <main class="ub-main" id="contenido">
