<?php

if(session_status() === PHP_SESSION_NONE){
    session_start();
}

if(!isset($_SESSION['id_usuario'])){
    header("Location: index.php?accion=login");
    exit();
}

/*
 * FASE 4 — Rediseño de "Mi perfil".
 * Estilos: assets/css/ub-perfil.css (solo se carga en esta vista).
 * Comportamiento: assets/js/ub-perfil.js.
 * Los formularios se envían a index.php?accion=perfil y los procesa
 * UsuarioController::procesarPerfil() (CSRF + sesión + validación).
 */

$uidPerfil = (int)$_SESSION['id_usuario'];

/* Escapa cualquier dato antes de imprimirlo en HTML. */
$e = function ($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
};

/* Publicaciones: misma consulta que ya usaba esta vista (solo del usuario). */
$misLibros = $libroController->misPublicaciones($uidPerfil);
$publicaciones = [];

if ($misLibros) {
    while ($fila = $misLibros->fetch_assoc()) {
        $publicaciones[] = $fila;
    }
}

$totalLibros = count($publicaciones);

/* Datos del propio usuario y columnas disponibles (ver database/fase4_perfil.sql). */
$perfil = $usuarioController->perfilPropio();
$cols   = $usuarioController->columnasPerfil();

$nombrePerfil   = trim((string)($perfil['nombre'] ?? ($_SESSION['nombre'] ?? '')));
$apellidoPerfil = trim((string)($perfil['apellido'] ?? ''));
$nombreCompleto = trim($nombrePerfil . ' ' . $apellidoPerfil);
$correoPerfil   = (string)($perfil['correo'] ?? '');

$universidadPerfil = trim((string)($perfil['universidad'] ?? ''));
$carreraPerfil     = trim((string)($perfil['carrera'] ?? ''));
$semestrePerfil    = trim((string)($perfil['semestre'] ?? ''));
$biografiaPerfil   = trim((string)($perfil['biografia'] ?? ''));

$fotoPerfil = $usuarioController->rutaFotoValida($perfil['foto_perfil'] ?? null);

$inicialPerfil = function_exists('mb_substr')
    ? mb_strtoupper(mb_substr($nombrePerfil, 0, 1, 'UTF-8'), 'UTF-8')
    : strtoupper(substr($nombrePerfil, 0, 1));

$hayColumnasNuevas = !empty($cols['foto_perfil'])
    || !empty($cols['biografia'])
    || !empty($cols['universidad'])
    || !empty($cols['carrera'])
    || !empty($cols['semestre']);

$faltanColumnas = empty($cols['foto_perfil'])
    || empty($cols['biografia'])
    || empty($cols['universidad'])
    || empty($cols['carrera'])
    || empty($cols['semestre']);

/* Mensaje de la última acción (se muestra una sola vez). */
$flashPerfil = $_SESSION['perfil_flash'] ?? null;
unset($_SESSION['perfil_flash']);

$abrirEdicion = !empty($flashPerfil['abrir']);
$valores = (is_array($flashPerfil) && isset($flashPerfil['valores']) && is_array($flashPerfil['valores']))
    ? $flashPerfil['valores']
    : [
        'nombre'      => $nombrePerfil,
        'apellido'    => $apellidoPerfil,
        'universidad' => $universidadPerfil,
        'carrera'     => $carreraPerfil,
        'semestre'    => $semestrePerfil,
        'biografia'   => $biografiaPerfil,
    ];

$csrfPerfil = UsuarioController::csrfToken();

/*
 * FASE 3.1 — Disponibilidad de libros (mismos badges que catálogo y detalle).
 */
$dispoClasesPerfil = [
    'disponible'    => 'badge-disp-disponible',
    'reservado'     => 'badge-disp-reservado',
    'vendido'       => 'badge-disp-vendido',
    'intercambiado' => 'badge-disp-intercambiado',
];

?>

<?php include "views/partials/navbar.php"; ?>

<div class="ubp">

    <!-- Mensajes de estado -->
    <div class="ubp-alerts" aria-live="polite">

        <?php if (!empty($flashPerfil['mensaje'])) { ?>
            <div class="ub-alert <?php echo $flashPerfil['tipo'] === 'ok' ? 'ub-alert-success' : 'ub-alert-danger'; ?> ubp-alert"
                 role="<?php echo $flashPerfil['tipo'] === 'ok' ? 'status' : 'alert'; ?>">
                <i class="bi <?php echo $flashPerfil['tipo'] === 'ok' ? 'bi-check-circle-fill' : 'bi-x-circle-fill'; ?> ub-icon" aria-hidden="true"></i>
                <?php echo $e($flashPerfil['mensaje']); ?>
            </div>
        <?php } ?>

        <?php if (isset($_GET['eliminado'])) { ?>
            <div class="ub-alert ub-alert-success ubp-alert" role="status">
                <i class="bi bi-check-circle-fill ub-icon" aria-hidden="true"></i> La publicación fue eliminada correctamente.
            </div>
        <?php } ?>

        <?php if (isset($_GET['error'])) { ?>
            <div class="ub-alert ub-alert-danger ubp-alert" role="alert">
                <i class="bi bi-x-circle-fill ub-icon" aria-hidden="true"></i> No se pudo eliminar la publicación.
            </div>
        <?php } ?>

    </div>


    <!-- ===================== CABECERA ===================== -->

    <section class="ubp-hero" aria-labelledby="ubpNombre">

        <span class="ubp-orb ubp-orb--a" aria-hidden="true"></span>
        <span class="ubp-orb ubp-orb--b" aria-hidden="true"></span>
        <span class="ubp-orb ubp-orb--c" aria-hidden="true"></span>

        <div class="ubp-hero__glass">

            <div class="ubp-photo">

                <div class="ubp-avatar<?php echo $fotoPerfil ? '' : ' is-empty'; ?>" id="ubpAvatar">
                    <?php if ($fotoPerfil) { ?>
                        <img src="<?php echo $e($fotoPerfil); ?>"
                             alt="Foto de perfil de <?php echo $e($nombreCompleto); ?>"
                             width="152" height="152" id="ubpAvatarImg">
                    <?php } else { ?>
                        <span class="ubp-avatar__initial" id="ubpAvatarInitial" role="img"
                              aria-label="Avatar predeterminado de <?php echo $e($nombreCompleto); ?>">
                            <?php if ($inicialPerfil !== '') { echo $e($inicialPerfil); } else { ?>
                                <i class="bi bi-person" aria-hidden="true"></i>
                            <?php } ?>
                        </span>
                    <?php } ?>
                </div>

                <?php if (!empty($cols['foto_perfil'])) { ?>
                    <button type="button" class="ubp-camera" data-ubp-pick
                            aria-label="<?php echo $fotoPerfil ? 'Cambiar foto de perfil' : 'Añadir foto de perfil'; ?>"
                            title="<?php echo $fotoPerfil ? 'Cambiar foto' : 'Añadir foto'; ?>">
                        <i class="bi bi-camera" aria-hidden="true"></i>
                    </button>
                <?php } ?>

            </div>

            <div class="ubp-id">

                <p class="ubp-eyebrow">Mi perfil</p>

                <h1 class="ubp-name" id="ubpNombre"><?php echo $e($nombreCompleto); ?></h1>

                <?php if ($universidadPerfil !== '' || $carreraPerfil !== '' || $semestrePerfil !== '') { ?>
                    <ul class="ubp-chips" aria-label="Información académica">
                        <?php if ($universidadPerfil !== '') { ?>
                            <li><i class="bi bi-bank2" aria-hidden="true"></i> <?php echo $e($universidadPerfil); ?></li>
                        <?php } ?>
                        <?php if ($carreraPerfil !== '') { ?>
                            <li><i class="bi bi-mortarboard" aria-hidden="true"></i> <?php echo $e($carreraPerfil); ?></li>
                        <?php } ?>
                        <?php if ($semestrePerfil !== '') { ?>
                            <li><i class="bi bi-layers" aria-hidden="true"></i> Semestre <?php echo $e($semestrePerfil); ?></li>
                        <?php } ?>
                    </ul>
                <?php } ?>

                <?php if ($biografiaPerfil !== '') { ?>
                    <p class="ubp-bio"><?php echo nl2br($e($biografiaPerfil)); ?></p>
                <?php } elseif (!empty($cols['biografia'])) { ?>
                    <p class="ubp-bio is-placeholder">
                        Cuéntale a la comunidad quién eres, qué estudias y qué libros buscas.
                        <button type="button" class="ubp-link" data-ubp-open>Escribir mi biografía</button>
                    </p>
                <?php } ?>

                <div class="ubp-actions">

                    <button type="button" class="btn-ub btn-ub-dark" data-ubp-open aria-haspopup="dialog">
                        <i class="bi bi-pencil-square ub-icon" aria-hidden="true"></i> Editar perfil
                    </button>

                    <?php if ($fotoPerfil) { ?>
                        <form method="POST" action="index.php?accion=perfil" class="ubp-inline"
                              onsubmit="return confirm('¿Quitar tu foto de perfil?');">
                            <input type="hidden" name="csrf" value="<?php echo $e($csrfPerfil); ?>">
                            <button type="submit" name="perfil_quitar_foto" value="1" class="btn-ub btn-ub-outline">
                                <i class="bi bi-image ub-icon" aria-hidden="true"></i> Quitar foto
                            </button>
                        </form>
                    <?php } ?>

                </div>

                <?php if (!empty($cols['foto_perfil'])) { ?>
                    <!-- Foto: selección, vista previa y confirmación -->
                    <form method="POST" action="index.php?accion=perfil" enctype="multipart/form-data"
                          class="ubp-photoform" id="ubpFotoForm">

                        <input type="hidden" name="csrf" value="<?php echo $e($csrfPerfil); ?>">
                        <input type="hidden" name="perfil_foto" value="1">

                        <input type="file" name="foto" id="ubpFotoInput" class="ubp-sr"
                               accept="image/jpeg,image/png,image/webp"
                               aria-label="Seleccionar foto de perfil" tabindex="-1">

                        <div class="ubp-photobar" id="ubpPhotoBar" hidden>
                            <div class="ubp-photobar__text">
                                <strong>Vista previa de tu nueva foto</strong>
                                <span id="ubpPhotoMsg">JPG, PNG o WebP, hasta 4 MB. Se recorta en círculo al centro.</span>
                            </div>
                            <div class="ubp-photobar__btns">
                                <button type="submit" class="btn-ub btn-ub-dark btn-ub-sm" id="ubpPhotoSave">
                                    <span class="ubp-spin" aria-hidden="true"></span>
                                    <span class="ubp-btn-label">Guardar foto</span>
                                </button>
                                <button type="button" class="btn-ub btn-ub-outline btn-ub-sm" id="ubpPhotoCancel">Cancelar</button>
                            </div>
                        </div>

                    </form>
                <?php } ?>

            </div>

        </div>

    </section>


    <?php if ($faltanColumnas) { ?>
        <!-- Aviso: la migración de base de datos aún no se ha ejecutado -->
        <div class="ub-alert ubp-setup" role="note">
            <i class="bi bi-database-gear ub-icon" aria-hidden="true"></i>
            <div>
                <strong>Faltan datos por activar en la base de datos.</strong>
                <?php echo $hayColumnasNuevas
                    ? 'Algunas funciones del perfil aún no están disponibles.'
                    : 'La foto de perfil, la biografía y los datos académicos todavía no están disponibles.'; ?>
                Ejecuta una sola vez el archivo <code>database/fase4_perfil.sql</code> en phpMyAdmin para activarlos.
            </div>
        </div>
    <?php } ?>


    <!-- ===================== INFORMACIÓN ===================== -->

    <div class="ubp-grid">

        <section class="ubp-card" style="--i:1" aria-labelledby="ubpAcadTitulo">

            <header class="ubp-card__head">
                <h2 id="ubpAcadTitulo"><i class="bi bi-mortarboard" aria-hidden="true"></i> Información académica</h2>
            </header>

            <dl class="ubp-list">

                <div class="ubp-row">
                    <dt><i class="bi bi-bank2" aria-hidden="true"></i> Universidad</dt>
                    <dd>
                        <?php if ($universidadPerfil !== '') { echo $e($universidadPerfil); }
                        elseif (!empty($cols['universidad'])) { ?>
                            <button type="button" class="ubp-link" data-ubp-open>Añadir universidad</button>
                        <?php } else { ?><span class="ubp-empty">No disponible</span><?php } ?>
                    </dd>
                </div>

                <div class="ubp-row">
                    <dt><i class="bi bi-journal-bookmark" aria-hidden="true"></i> Carrera</dt>
                    <dd>
                        <?php if ($carreraPerfil !== '') { echo $e($carreraPerfil); }
                        elseif (!empty($cols['carrera'])) { ?>
                            <button type="button" class="ubp-link" data-ubp-open>Añadir carrera</button>
                        <?php } else { ?><span class="ubp-empty">No disponible</span><?php } ?>
                    </dd>
                </div>

                <div class="ubp-row">
                    <dt><i class="bi bi-layers" aria-hidden="true"></i> Semestre</dt>
                    <dd>
                        <?php if ($semestrePerfil !== '') { echo $e($semestrePerfil); }
                        elseif (!empty($cols['semestre'])) { ?>
                            <button type="button" class="ubp-link" data-ubp-open>Añadir semestre</button>
                        <?php } else { ?><span class="ubp-empty">No disponible</span><?php } ?>
                    </dd>
                </div>

            </dl>

        </section>

        <section class="ubp-card" style="--i:2" aria-labelledby="ubpCuentaTitulo">

            <header class="ubp-card__head">
                <h2 id="ubpCuentaTitulo"><i class="bi bi-person-lines-fill" aria-hidden="true"></i> Información personal</h2>
            </header>

            <dl class="ubp-list">

                <div class="ubp-row">
                    <dt><i class="bi bi-person" aria-hidden="true"></i> Nombre</dt>
                    <dd><?php echo $e($nombreCompleto); ?></dd>
                </div>

                <?php if ($correoPerfil !== '') { ?>
                    <div class="ubp-row">
                        <dt><i class="bi bi-envelope" aria-hidden="true"></i> Correo</dt>
                        <dd class="ubp-break"><?php echo $e($correoPerfil); ?></dd>
                    </div>
                <?php } ?>

                <div class="ubp-row">
                    <dt><i class="bi bi-journals" aria-hidden="true"></i> Publicaciones</dt>
                    <dd><?php echo (int)$totalLibros; ?></dd>
                </div>

            </dl>

        </section>

    </div>


    <!-- ===================== MIS PUBLICACIONES ===================== -->

    <section class="ubp-card ubp-card--wide" style="--i:3" aria-labelledby="ubpPubTitulo">

        <header class="ubp-card__head ubp-card__head--split">

            <h2 id="ubpPubTitulo">
                <i class="bi bi-journals" aria-hidden="true"></i> Mis publicaciones
                <span class="ubp-count" aria-label="<?php echo (int)$totalLibros; ?> publicaciones"><?php echo (int)$totalLibros; ?></span>
            </h2>

            <?php if ($totalLibros > 0) { ?>
                <a href="index.php?accion=publicar_libro" class="btn-ub btn-ub-outline btn-ub-sm">
                    <i class="bi bi-plus-lg ub-icon" aria-hidden="true"></i> Publicar libro
                </a>
            <?php } ?>

        </header>

        <?php if ($totalLibros > 0) { ?>

            <div class="ubp-books">

                <?php foreach ($publicaciones as $indiceLibro => $libro) {

                    /*
                     * "?? 'disponible'" evita romper esta vista si el
                     * script SQL de la Fase 3.1 todavía no se ha ejecutado.
                     */
                    $disponibilidadLibro = $libro['estado_disponibilidad'] ?? 'disponible';
                    $dispoClaseLibro = $dispoClasesPerfil[$disponibilidadLibro] ?? 'badge-disp-disponible';
                    $dispoLabelLibro = ucfirst($disponibilidadLibro);

                    $tipoLibro = strtolower(trim((string)($libro['tipo_publicacion'] ?? '')));
                    $esVenta = ($tipoLibro === 'venta');
                    $esIntercambio = ($tipoLibro === 'intercambio');

                    $urlLibro = 'index.php?accion=detalle_libro&id=' . (int)$libro['id'];

                    $metaLibro = array_filter([
                        trim((string)($libro['universidad'] ?? '')),
                        trim((string)($libro['carrera'] ?? '')),
                        trim((string)($libro['materia'] ?? '')),
                    ], function ($v) { return $v !== ''; });
                ?>

                    <article class="ubp-book" style="--i:<?php echo min((int)$indiceLibro, 7); ?>">

                        <a class="ubp-book__cover" href="<?php echo $e($urlLibro); ?>" tabindex="-1" aria-hidden="true">
                            <?php if (!empty($libro['imagen'])) { ?>
                                <img class="ubp-book__bg" src="<?php echo $e($libro['imagen']); ?>" alt="" loading="lazy">
                                <img class="ubp-book__img" src="<?php echo $e($libro['imagen']); ?>"
                                     alt="Portada de <?php echo $e($libro['titulo']); ?>" loading="lazy">
                            <?php } else { ?>
                                <span class="ubp-book__none"><i class="bi bi-book" aria-hidden="true"></i> Sin portada</span>
                            <?php } ?>
                        </a>

                        <div class="ubp-book__body">

                            <div class="ubp-book__tags">
                                <?php if ($esVenta) { ?>
                                    <span class="stamp stamp-venta">Venta</span>
                                <?php } elseif ($esIntercambio) { ?>
                                    <span class="stamp stamp-intercambio">Intercambio</span>
                                <?php } ?>
                                <span class="stamp <?php echo $e($dispoClaseLibro); ?>"><?php echo $e($dispoLabelLibro); ?></span>
                            </div>

                            <h3 class="ubp-book__title">
                                <a href="<?php echo $e($urlLibro); ?>"><?php echo $e($libro['titulo']); ?></a>
                            </h3>

                            <?php if (!empty($libro['autor'])) { ?>
                                <p class="ubp-book__author"><?php echo $e($libro['autor']); ?></p>
                            <?php } ?>

                            <?php if ($metaLibro) { ?>
                                <p class="ubp-book__meta"><?php echo $e(implode(' · ', $metaLibro)); ?></p>
                            <?php } ?>

                            <div class="ubp-book__foot">

                                <?php if ($esVenta || (!$esIntercambio && (float)$libro['precio'] > 0)) { ?>
                                    <span class="ubp-book__price">$<?php echo number_format((float)$libro['precio'], 0, ',', '.'); ?></span>
                                <?php } else { ?>
                                    <span class="ubp-book__price is-swap"><i class="bi bi-arrow-left-right" aria-hidden="true"></i> Intercambio</span>
                                <?php } ?>

                                <div class="ubp-book__actions">

                                    <a href="<?php echo $e($urlLibro); ?>" class="btn-ub btn-ub-dark btn-ub-sm"
                                       aria-label="Ver <?php echo $e($libro['titulo']); ?>">
                                        <i class="bi bi-eye ub-icon" aria-hidden="true"></i> Ver
                                    </a>

                                    <form method="POST" action="index.php" class="ubp-inline" onsubmit="return confirmarEliminacion();">
                                        <input type="hidden" name="id" value="<?php echo (int)$libro['id']; ?>">
                                        <button type="submit" name="eliminar_libro" class="btn-ub btn-ub-danger btn-ub-sm"
                                                aria-label="Eliminar <?php echo $e($libro['titulo']); ?>">
                                            <i class="bi bi-trash3 ub-icon" aria-hidden="true"></i> Eliminar
                                        </button>
                                    </form>

                                </div>

                            </div>

                        </div>

                    </article>

                <?php } ?>

            </div>

        <?php } else { ?>

            <div class="ubp-empty-state">
                <span class="ubp-empty-state__icon" aria-hidden="true"><i class="bi bi-journal-plus"></i></span>
                <h3>Todavía no has publicado libros</h3>
                <p>Cuando publiques un libro en venta o intercambio, aparecerá aquí.</p>
                <a href="index.php?accion=publicar_libro" class="btn-ub btn-ub-dark">
                    <i class="bi bi-plus-circle ub-icon" aria-hidden="true"></i> Publicar mi primer libro
                </a>
            </div>

        <?php } ?>

    </section>


    <!-- ===================== EDICIÓN (modal) ===================== -->

    <dialog class="ubp-dialog" id="ubpDialog" aria-labelledby="ubpDialogTitulo"
            data-open-on-load="<?php echo $abrirEdicion ? '1' : '0'; ?>">

        <form method="POST" action="index.php?accion=perfil" class="ubp-form" id="ubpForm">

            <input type="hidden" name="csrf" value="<?php echo $e($csrfPerfil); ?>">
            <input type="hidden" name="perfil_guardar" value="1">

            <header class="ubp-dialog__head">
                <div>
                    <p class="ubp-eyebrow">Mi perfil</p>
                    <h2 id="ubpDialogTitulo">Editar perfil</h2>
                </div>
                <button type="button" class="ubp-close" data-ubp-close aria-label="Cerrar">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </header>

            <div class="ubp-dialog__body">

                <?php if ($abrirEdicion && !empty($flashPerfil['mensaje']) && $flashPerfil['tipo'] === 'error') { ?>
                    <div class="ub-alert ub-alert-danger" role="alert">
                        <i class="bi bi-x-circle-fill ub-icon" aria-hidden="true"></i>
                        <?php echo $e($flashPerfil['mensaje']); ?>
                    </div>
                <?php } ?>

                <fieldset class="ubp-fieldset">
                    <legend>Información personal</legend>

                    <div class="ubp-two">
                        <div class="ub-field">
                            <label for="ubpNombreInput">Nombre</label>
                            <input class="form-control" type="text" id="ubpNombreInput" name="nombre"
                                   value="<?php echo $e($valores['nombre'] ?? ''); ?>"
                                   maxlength="100" required autocomplete="given-name">
                        </div>
                        <div class="ub-field">
                            <label for="ubpApellidoInput">Apellido</label>
                            <input class="form-control" type="text" id="ubpApellidoInput" name="apellido"
                                   value="<?php echo $e($valores['apellido'] ?? ''); ?>"
                                   maxlength="100" required autocomplete="family-name">
                        </div>
                    </div>

                    <?php if ($correoPerfil !== '') { ?>
                        <div class="ub-field">
                            <label for="ubpCorreoInput">Correo</label>
                            <input class="form-control" type="email" id="ubpCorreoInput"
                                   value="<?php echo $e($correoPerfil); ?>" readonly aria-describedby="ubpCorreoAyuda">
                            <small class="ubp-hint" id="ubpCorreoAyuda">El correo es tu acceso a UniBook y no se puede cambiar aquí.</small>
                        </div>
                    <?php } ?>
                </fieldset>

                <?php if (!empty($cols['universidad']) || !empty($cols['carrera']) || !empty($cols['semestre'])) { ?>
                    <fieldset class="ubp-fieldset">
                        <legend>Información académica</legend>

                        <?php if (!empty($cols['universidad'])) { ?>
                            <div class="ub-field">
                                <label for="ubpUniversidadInput">Universidad</label>
                                <input class="form-control" type="text" id="ubpUniversidadInput" name="universidad"
                                       value="<?php echo $e($valores['universidad'] ?? ''); ?>"
                                       maxlength="150" placeholder="Ej.: Universidad de Antioquia">
                            </div>
                        <?php } ?>

                        <div class="ubp-two">
                            <?php if (!empty($cols['carrera'])) { ?>
                                <div class="ub-field">
                                    <label for="ubpCarreraInput">Carrera o programa</label>
                                    <input class="form-control" type="text" id="ubpCarreraInput" name="carrera"
                                           value="<?php echo $e($valores['carrera'] ?? ''); ?>"
                                           maxlength="150" placeholder="Ej.: Ingeniería de Sistemas">
                                </div>
                            <?php } ?>

                            <?php if (!empty($cols['semestre'])) { ?>
                                <div class="ub-field">
                                    <label for="ubpSemestreInput">Semestre</label>
                                    <select class="form-select" id="ubpSemestreInput" name="semestre">
                                        <option value="">Sin indicar</option>
                                        <?php for ($s = 1; $s <= 12; $s++) { ?>
                                            <option value="<?php echo $s; ?>"<?php echo ((string)($valores['semestre'] ?? '') === (string)$s) ? ' selected' : ''; ?>>
                                                Semestre <?php echo $s; ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                            <?php } ?>
                        </div>
                    </fieldset>
                <?php } ?>

                <?php if (!empty($cols['biografia'])) { ?>
                    <fieldset class="ubp-fieldset">
                        <legend>Biografía</legend>

                        <div class="ub-field">
                            <label for="ubpBioInput">Sobre ti</label>
                            <textarea class="form-control" id="ubpBioInput" name="biografia" rows="4"
                                      maxlength="300" aria-describedby="ubpBioCount"
                                      placeholder="Cuéntale a la comunidad quién eres y qué estudias."><?php echo $e($valores['biografia'] ?? ''); ?></textarea>
                            <small class="ubp-hint ubp-hint--right" id="ubpBioCount" aria-live="polite">
                                <span data-ubp-count>0</span>/300
                            </small>
                        </div>
                    </fieldset>
                <?php } ?>

            </div>

            <footer class="ubp-dialog__foot">
                <button type="button" class="btn-ub btn-ub-outline" data-ubp-close>Cancelar</button>
                <button type="submit" class="btn-ub btn-ub-dark" id="ubpSave">
                    <span class="ubp-spin" aria-hidden="true"></span>
                    <span class="ubp-btn-label">Guardar cambios</span>
                </button>
            </footer>

        </form>

    </dialog>

</div>

<script src="assets/js/ub-perfil.js"></script>

<?php include "views/partials/footer.php"; ?>
