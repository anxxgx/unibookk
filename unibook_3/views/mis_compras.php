<?php

if(session_status() === PHP_SESSION_NONE){
    session_start();
}

$misCompras = $misCompras ?? [];

if(!is_array($misCompras)){
    $misCompras = [];
}

/*
|--------------------------------------------------------------------------
| Utilidades de presentación (solo lectura de los datos que ya llegan)
|--------------------------------------------------------------------------
*/

$ucH = function ($texto) {
    return htmlspecialchars((string)$texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};

$ucPrecio = function ($valor) {
    return '$ ' . number_format((float)$valor, 0, ',', '.');
};

$ucMeses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

/* "23 sep 2026, 2:27 p. m." */
$ucFecha = function ($valor) use ($ucMeses) {

    $ts = !empty($valor) ? strtotime($valor) : false;

    if (!$ts) {
        return '';
    }

    return (int)date('j', $ts) . ' '
        . $ucMeses[(int)date('n', $ts) - 1] . ' '
        . date('Y', $ts) . ', '
        . date('g:i', $ts) . ' '
        . (date('a', $ts) === 'am' ? 'a. m.' : 'p. m.');
};

/*
 * Portada: solo se devuelve una ruta si el archivo existe de verdad (o es una
 * URL absoluta). Antes se usaba assets/img/libro-placeholder.png, que no
 * existe en el proyecto y dejaba una imagen rota; ahora se muestra "Sin portada".
 */
$ucPortada = function ($ruta) {

    $ruta = trim((string)$ruta);

    if ($ruta === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $ruta)) {
        return $ruta;
    }

    $ruta = ltrim(str_replace('\\', '/', $ruta), '/');

    if ($ruta === '' || strpos($ruta, '..') !== false) {
        return '';
    }

    return is_file(dirname(__DIR__) . '/' . $ruta) ? $ruta : '';
};

/* Métodos de pago: mismo mapa que ya usaba la vista. */
$ucMetodos = [
    'pse'         => 'PSE',
    'nequi'       => 'Nequi',
    'bancolombia' => 'Bancolombia',
    'daviplata'   => 'Daviplata',
];

/*
 * Estados reales de la tabla `compras`: pendiente, confirmada, rechazada y
 * cancelada. Se agrupan igual que antes (confirmada/completada = Completada;
 * cancelada/rechazada = Cancelada). Devuelve [texto, clase, clave de filtro].
 */
$ucEstado = function ($estado) {

    $estado = strtolower(trim((string)$estado));

    switch ($estado) {

        case 'pendiente':
            return ['Pendiente', 'warning', 'pendiente'];

        case 'confirmada':
        case 'completada':
            return ['Completada', 'success', 'completada'];

        case 'cancelada':
        case 'rechazada':
            return ['Cancelada', 'danger', 'cancelada'];

        default:
            return [ucfirst($estado), 'neutral', 'otro'];
    }
};

/* Contadores reales por estado (para los filtros). */
$ucTotal = count($misCompras);

$ucConteo = ['pendiente' => 0, 'completada' => 0, 'cancelada' => 0, 'otro' => 0];

foreach ($misCompras as $ucFila) {

    $ucClave = $ucEstado($ucFila['estado'] ?? 'pendiente')[2];

    $ucConteo[$ucClave]++;
}

?>

<?php include "views/partials/navbar.php"; ?>

<link rel="stylesheet" href="assets/css/ub-mis-compras.css">

<section class="uc" data-uc>

    <!-- ===================== CABECERA ===================== -->

    <header class="uc-hero uc-rise" style="--i:0">

        <span class="uc-chip">
            <i class="bi bi-bag-check" aria-hidden="true"></i>
            Mi actividad
        </span>

        <h1 class="uc-title">Mis compras</h1>

        <p class="uc-lead">
            Aquí encontrarás los libros que has comprado en UniBook,
            con el estado y los datos de cada operación.
        </p>

    </header>


    <?php if (empty($misCompras)): ?>


        <!-- ===================== ESTADO VACÍO ===================== -->

        <div class="uc-empty uc-rise" style="--i:2">

            <div class="uc-empty__art" aria-hidden="true">
                <span class="uc-empty__ring uc-empty__ring--outer"></span>
                <span class="uc-empty__ring uc-empty__ring--inner"></span>
                <span class="uc-empty__icon"><i class="bi bi-bag"></i></span>
            </div>

            <h2>Todavía no tienes compras</h2>

            <p>
                Explora el catálogo y encuentra los libros que necesitas
                para tu carrera. Cuando compres uno, aparecerá aquí.
            </p>

            <a href="index.php?accion=catalogo" class="uc-btn">
                Explorar catálogo
            </a>

        </div>


    <?php else: ?>


        <!-- ===================== BUSCADOR Y FILTROS ===================== -->

        <div class="uc-toolbar uc-rise" style="--i:1">

            <div class="uc-search">

                <i class="bi bi-search" aria-hidden="true"></i>

                <input
                    type="search"
                    id="ucBuscar"
                    class="uc-search__input"
                    placeholder="Buscar por título o autor..."
                    aria-label="Buscar mis compras por título o autor"
                    autocomplete="off"
                    data-uc-search
                >

                <button type="button" class="uc-search__clear" data-uc-clear aria-label="Borrar búsqueda" hidden>
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>

            </div>

            <div class="uc-filters" role="group" aria-label="Filtrar por estado">

                <button type="button" class="uc-filter is-active" data-uc-filter="todas" aria-pressed="true">
                    Todas
                    <span class="uc-filter__count"><?= (int)$ucTotal ?></span>
                </button>

                <?php if ($ucConteo['pendiente'] > 0): ?>
                    <button type="button" class="uc-filter" data-uc-filter="pendiente" aria-pressed="false">
                        Pendientes
                        <span class="uc-filter__count"><?= (int)$ucConteo['pendiente'] ?></span>
                    </button>
                <?php endif; ?>

                <?php if ($ucConteo['completada'] > 0): ?>
                    <button type="button" class="uc-filter" data-uc-filter="completada" aria-pressed="false">
                        Completadas
                        <span class="uc-filter__count"><?= (int)$ucConteo['completada'] ?></span>
                    </button>
                <?php endif; ?>

                <?php if ($ucConteo['cancelada'] > 0): ?>
                    <button type="button" class="uc-filter" data-uc-filter="cancelada" aria-pressed="false">
                        Canceladas
                        <span class="uc-filter__count"><?= (int)$ucConteo['cancelada'] ?></span>
                    </button>
                <?php endif; ?>

            </div>

        </div>


        <!-- ===================== COMPRAS ===================== -->

        <div class="uc-listhead uc-rise" style="--i:2">

            <h2>Compras</h2>

            <p id="ucContador" aria-live="polite" data-total="<?= (int)$ucTotal ?>">
                <strong><?= (int)$ucTotal ?></strong>
                <?= $ucTotal === 1 ? 'compra realizada' : 'compras realizadas' ?>
            </p>

        </div>


        <ul class="uc-list" id="ucLista">

            <?php $ucPos = 0; foreach ($misCompras as $compra): ?>

                <?php

                $ucPos++;

                $titulo = (string)($compra['titulo'] ?? '');

                $portada = $ucPortada($compra['imagen'] ?? '');

                [$estadoTexto, $estadoClase, $estadoClave] = $ucEstado($compra['estado'] ?? 'pendiente');

                $metodoClave = strtolower((string)($compra['metodo_pago'] ?? ''));

                $metodo = $ucMetodos[$metodoClave] ?? ucfirst($metodoClave);

                /*
                 * Compra::obtenerPorComprador devuelve los alias
                 * nombre_vendedor y apellido_vendedor (la vista anterior leía
                 * vendedor_nombre/vendedor_apellido, que no existen, y por eso
                 * el vendedor salía vacío). Se aceptan ambos por compatibilidad.
                 */
                $vendedor = trim(
                    ($compra['nombre_vendedor'] ?? $compra['vendedor_nombre'] ?? '')
                    . ' '
                    . ($compra['apellido_vendedor'] ?? $compra['vendedor_apellido'] ?? '')
                );

                $fechaTexto = $ucFecha($compra['fecha_compra'] ?? '');

                $fechaIso = !empty($compra['fecha_compra'])
                    ? date('c', (int)strtotime($compra['fecha_compra']))
                    : '';

                $textoBusqueda = $titulo . ' ' . ($compra['autor'] ?? '');

                ?>

                <li
                    class="uc-item uc-rise"
                    style="--i:<?= min($ucPos + 2, 9) ?>"
                    data-estado="<?= $ucH($estadoClave) ?>"
                    data-texto="<?= $ucH($textoBusqueda) ?>"
                >

                    <article class="uc-card" aria-label="Compra de <?= $ucH($titulo) ?>">

                        <div class="uc-cover<?= $portada === '' ? ' is-empty' : '' ?>">

                            <?php if ($portada !== ''): ?>
                                <img class="uc-cover__bg" src="<?= $ucH($portada) ?>" alt="" aria-hidden="true" loading="lazy" decoding="async">
                                <img class="uc-cover__img" src="<?= $ucH($portada) ?>"
                                     alt="Portada de <?= $ucH($titulo) ?>"
                                     loading="lazy"
                                     decoding="async">
                            <?php else: ?>
                                <span class="uc-cover__none">
                                    <i class="bi bi-book" aria-hidden="true"></i>
                                    <span>Sin portada</span>
                                </span>
                            <?php endif; ?>

                        </div>


                        <div class="uc-main">

                            <div class="uc-main__top">

                                <span class="uc-status uc-status--<?= $ucH($estadoClase) ?>">
                                    <?= $ucH($estadoTexto) ?>
                                </span>

                                <span class="uc-opid" title="Número de operación">#<?= (int)$compra['id'] ?></span>

                            </div>

                            <h3 class="uc-book" title="<?= $ucH($titulo) ?>"><?= $ucH($titulo) ?></h3>

                            <?php if (!empty($compra['autor'])): ?>
                                <p class="uc-author"><?= $ucH($compra['autor']) ?></p>
                            <?php endif; ?>

                            <dl class="uc-meta">

                                <?php if ($vendedor !== ''): ?>
                                    <div>
                                        <i class="bi bi-person-circle" aria-hidden="true"></i>
                                        <dt>Vendedor</dt>
                                        <dd><?= $ucH($vendedor) ?></dd>
                                    </div>
                                <?php endif; ?>

                                <?php if ($metodo !== ''): ?>
                                    <div>
                                        <i class="bi bi-credit-card" aria-hidden="true"></i>
                                        <dt>Pago</dt>
                                        <dd><?= $ucH($metodo) ?></dd>
                                    </div>
                                <?php endif; ?>

                                <div>
                                    <i class="bi bi-calendar-event" aria-hidden="true"></i>
                                    <dt>Fecha</dt>
                                    <dd>
                                        <?php if ($fechaTexto !== ''): ?>
                                            <time datetime="<?= $ucH($fechaIso) ?>"><?= $ucH($fechaTexto) ?></time>
                                        <?php else: ?>
                                            No disponible
                                        <?php endif; ?>
                                    </dd>
                                </div>

                            </dl>

                        </div>


                        <div class="uc-aside">

                            <div class="uc-price">
                                <span class="uc-price__label">Precio</span>
                                <span class="uc-price__value"><?= $ucH($ucPrecio($compra['precio'] ?? 0)) ?></span>
                            </div>

                            <a
                                href="index.php?accion=detalle_compra&amp;id=<?= (int)$compra['id'] ?>"
                                class="uc-btn"
                                aria-label="Ver operación de <?= $ucH($titulo) ?>"
                            >
                                Ver operación
                                <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                            </a>

                        </div>

                    </article>

                </li>

            <?php endforeach; ?>

        </ul>


        <!-- Sin resultados (la activan los filtros / el buscador) -->

        <div class="uc-noresults" id="ucSinResultados" hidden>

            <span class="uc-noresults__icon" aria-hidden="true"><i class="bi bi-search"></i></span>

            <h2>No encontramos compras con ese criterio</h2>

            <p>Prueba con otro título o cambia el filtro de estado.</p>

            <button type="button" class="uc-btn uc-btn--glass" data-uc-reset>
                Ver todas mis compras
            </button>

        </div>


        <script src="assets/js/ub-mis-compras.js"></script>


    <?php endif; ?>

</section>


<?php include "views/partials/footer.php"; ?>
