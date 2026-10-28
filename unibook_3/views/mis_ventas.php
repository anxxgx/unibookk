<?php

if(session_status() === PHP_SESSION_NONE){
    session_start();
}

/*
 * $panelVentas lo prepara CompraController::panelVentas() desde index.php
 * (solo lectura, siempre con el vendedor de la sesión). Las reglas de
 * cálculo de cada indicador están documentadas en ese método.
 */
$panelVentas = (isset($panelVentas) && is_array($panelVentas)) ? $panelVentas : null;

$uvK       = $panelVentas['kpi']         ?? [];
$uvOps     = $panelVentas['operaciones'] ?? [];
$uvEstados = $panelVentas['estados']     ?? [];
$uvMetodos = $panelVentas['metodos']     ?? [];
$uvMayores = $panelVentas['mayores']     ?? [];
$uvSerie   = $panelVentas['serie']       ?? ['agrupacion' => 'dia', 'puntos' => []];
$uvDeltas  = $panelVentas['deltas']      ?? [];

$uvPeriodo    = $panelVentas['periodo']   ?? '30';
$uvTotal      = (int)($uvK['total'] ?? 0);
$uvHistorial  = !empty($panelVentas['tiene_historial']);
$uvComparable = !empty($panelVentas['comparable']);

/*
|--------------------------------------------------------------------------
| Utilidades de presentación
|--------------------------------------------------------------------------
*/

$uvH = function ($texto) {
    return htmlspecialchars((string)$texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};

$uvPrecio = function ($valor) {
    return '$ ' . number_format((float)$valor, 0, ',', '.');
};

/* 58,3 % — sin decimales sobrantes (60 %, no 60,0 %) */
$uvPct = function ($valor) {
    $s = number_format((float)$valor, 1, ',', '.');
    return preg_replace('/,0$/', '', $s) . ' %';
};

$uvMesesCortos = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

$uvHora = function ($ts) {
    return date('g:i', $ts) . ' ' . (date('a', $ts) === 'am' ? 'a. m.' : 'p. m.');
};

/* "23 sep 2026" */
$uvDia = function ($valor) use ($uvMesesCortos) {

    $ts = !empty($valor) ? strtotime($valor) : false;

    if (!$ts) {
        return '';
    }

    return (int)date('j', $ts) . ' '
        . $uvMesesCortos[(int)date('n', $ts) - 1] . ' '
        . date('Y', $ts);
};

$uvHoraDe = function ($valor) use ($uvHora) {

    $ts = !empty($valor) ? strtotime($valor) : false;

    return $ts ? $uvHora($ts) : '';
};

/*
 * Portada: solo se devuelve una ruta si el archivo existe de verdad
 * (o es una URL absoluta). Si no, la fila muestra "Sin portada".
 */
$uvPortada = function ($ruta) {

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

$uvNombresMetodo = [
    'pse'         => 'PSE',
    'nequi'       => 'Nequi',
    'bancolombia' => 'Bancolombia',
    'daviplata'   => 'Daviplata',
];

/*
 * Variación frente al periodo anterior.
 *   $valor = null  -> no calculable (sin periodo anterior o base cero):
 *                     se muestra un indicador neutral, nunca un % inventado.
 */
$uvDelta = function ($valor, $unidad = '%') use ($uvComparable) {

    if ($valor === null) {

        $texto = $uvComparable
            ? 'Sin base previa'
            : 'Sin comparación';

        return '<span class="uv-delta uv-delta--none"><i class="bi bi-dash-lg" aria-hidden="true"></i>'
            . $texto . '</span>';
    }

    $valor = (float)$valor;

    if ($valor > 0) {
        $clase = 'up';   $icono = 'arrow-up-right';   $signo = '+';
    } elseif ($valor < 0) {
        $clase = 'down'; $icono = 'arrow-down-right'; $signo = '−';
    } else {
        $clase = 'flat'; $icono = 'dash-lg';          $signo = '';
    }

    $num = number_format(abs($valor), 1, ',', '.');
    $num = preg_replace('/,0$/', '', $num);

    return '<span class="uv-delta uv-delta--' . $clase . '"><i class="bi bi-' . $icono . '" aria-hidden="true"></i>'
        . $signo . $num . ($unidad === 'pp' ? ' pp' : ' %') . '</span>'
        . '<span class="uv-delta__ref">vs. periodo anterior</span>';
};

$uvUrlPeriodo = function ($clave) {
    return 'index.php?accion=mis_ventas&periodo=' . $clave;
};

/* Recuento de ventas completadas y suma, calculados sobre las MISMAS filas de la tabla */
$uvFilasCompletadas = 0;
$uvFilasImporte     = 0.0;

foreach ($uvOps as $uvF) {
    if (strtolower((string)($uvF['estado'] ?? '')) === 'confirmada') {
        $uvFilasCompletadas++;
        $uvFilasImporte += (float)$uvF['precio'];
    }
}

$uvAgrup = $uvSerie['agrupacion'] ?? 'dia';

$uvAgrupTexto = ['dia' => 'por día', 'semana' => 'por semana', 'mes' => 'por mes'][$uvAgrup] ?? 'por día';

$uvSerieTieneDatos = false;

foreach (($uvSerie['puntos'] ?? []) as $uvPt) {
    if ((float)$uvPt['valor'] > 0) {
        $uvSerieTieneDatos = true;
        break;
    }
}

$uvDatosJs = [
    'agrupacion' => $uvAgrup,
    'puntos'     => $uvSerie['puntos'] ?? [],
];

?>

<?php include "views/partials/navbar.php"; ?>

<link rel="stylesheet" href="assets/css/ub-mis-ventas.css">

<section class="uv" id="uv">

    <!-- Luces de fondo: dan profundidad para que el vidrio se aprecie -->
    <div class="uv-aurora" aria-hidden="true">
        <span class="uv-aurora__a"></span>
        <span class="uv-aurora__b"></span>
        <span class="uv-aurora__c"></span>
        <span class="uv-aurora__d"></span>
    </div>


    <!-- ===================== CABECERA ===================== -->

    <header class="uv-hero uv-rise" style="--i:0">

        <div class="uv-hero__copy">

            <span class="uv-chip">
                <i class="bi bi-graph-up-arrow" aria-hidden="true"></i>
                Mi actividad
            </span>

            <h1 class="uv-title">Mis ventas</h1>

            <p class="uv-lead">
                Sigue tus operaciones, tus ingresos y el estado de cada
                venta en un solo lugar.
            </p>

        </div>

        <?php if ($uvHistorial && $panelVentas): ?>

            <div class="uv-hero__tools">

                <nav class="uv-periods" aria-label="Periodo de análisis">

                    <?php
                    $uvCortos = ['7' => '7 días', '30' => '30 días', '90' => '90 días', 'todo' => 'Todo'];
                    foreach ($uvCortos as $uvClave => $uvTexto):
                        $uvActivo = ((string)$uvClave === (string)$uvPeriodo);
                    ?>
                        <a href="<?= $uvH($uvUrlPeriodo($uvClave)) ?>"
                           class="uv-periods__item<?= $uvActivo ? ' is-active' : '' ?>"
                           title="<?= $uvH($panelVentas['periodos'][$uvClave] ?? $uvTexto) ?>"
                           <?= $uvActivo ? 'aria-current="true"' : '' ?>>
                            <?= $uvH($uvTexto) ?>
                        </a>
                    <?php endforeach; ?>

                </nav>

                <p class="uv-range">
                    <i class="bi bi-calendar3" aria-hidden="true"></i>
                    <span><?= $uvH($panelVentas['rango'] ?? '') ?></span>
                    <span class="uv-range__sep" aria-hidden="true"></span>
                    <strong><?= $uvTotal ?></strong>
                    <?= $uvTotal === 1 ? 'operación' : 'operaciones' ?>
                </p>

                <p class="uv-updated">
                    <i class="bi bi-clock-history" aria-hidden="true"></i>
                    Datos al día · <?= $uvH($uvHoraDe($panelVentas['ahora'] ?? '')) ?>
                    <a href="<?= $uvH($uvUrlPeriodo($uvPeriodo)) ?>"
                       class="uv-refresh"
                       title="Volver a cargar los datos"
                       aria-label="Actualizar los datos">
                        <i class="bi bi-arrow-clockwise" aria-hidden="true"></i>
                    </a>
                </p>

            </div>

        <?php endif; ?>

    </header>


    <?php if (!$uvHistorial || !$panelVentas): ?>


        <!-- ===================== ESTADO VACÍO (sin ventas en la cuenta) ===================== -->

        <div class="uv-empty uv-rise" style="--i:2">

            <div class="uv-empty__art" aria-hidden="true">
                <span class="uv-empty__ring uv-empty__ring--outer"></span>
                <span class="uv-empty__ring uv-empty__ring--inner"></span>
                <span class="uv-empty__icon"><i class="bi bi-journal-bookmark"></i></span>
            </div>

            <h2>Todavía no tienes ventas</h2>

            <p>
                Publica los libros que ya no necesitas y dales una
                segunda oportunidad dentro de UniBook. Cuando alguien
                compre uno, la operación y tus estadísticas aparecerán aquí.
            </p>

            <a href="index.php?accion=publicar_libro" class="uv-btn">
                Publicar un libro
            </a>

        </div>


    <?php else: ?>


        <!-- ===================== INDICADORES ===================== -->

        <div class="uv-kpis">

            <article class="uv-kpi uv-kpi--featured uv-rise" style="--i:1">
                <div class="uv-kpi__head">
                    <span class="uv-kpi__icon" aria-hidden="true"><i class="bi bi-cash-stack"></i></span>
                    <p class="uv-kpi__label">Ingresos por ventas completadas</p>
                </div>
                <p class="uv-kpi__value"><?= $uvH($uvPrecio($uvK['ingresos'] ?? 0)) ?></p>
                <p class="uv-kpi__foot"><?= $uvDelta($uvDeltas['ingresos'] ?? null) ?></p>
            </article>

            <article class="uv-kpi uv-rise" style="--i:2">
                <div class="uv-kpi__head">
                    <span class="uv-kpi__icon" aria-hidden="true"><i class="bi bi-patch-check"></i></span>
                    <p class="uv-kpi__label">Operaciones completadas</p>
                </div>
                <p class="uv-kpi__value"><?= (int)($uvK['completadas'] ?? 0) ?></p>
                <p class="uv-kpi__foot"><?= $uvDelta($uvDeltas['completadas'] ?? null) ?></p>
            </article>

            <article class="uv-kpi uv-rise" style="--i:3">
                <div class="uv-kpi__head">
                    <span class="uv-kpi__icon uv-kpi__icon--amber" aria-hidden="true"><i class="bi bi-hourglass-split"></i></span>
                    <p class="uv-kpi__label">Operaciones pendientes</p>
                </div>
                <p class="uv-kpi__value"><?= (int)($uvK['pendientes'] ?? 0) ?></p>
                <p class="uv-kpi__foot">
                    <span class="uv-delta__ref">
                        <?= (int)($uvK['pendientes'] ?? 0) > 0 ? 'Esperan tu respuesta' : 'Nada por responder' ?>
                    </span>
                </p>
            </article>

            <article class="uv-kpi uv-rise" style="--i:4">
                <div class="uv-kpi__head">
                    <span class="uv-kpi__icon" aria-hidden="true"><i class="bi bi-bullseye"></i></span>
                    <p class="uv-kpi__label">Tasa de finalización</p>
                </div>
                <p class="uv-kpi__value">
                    <?= ($uvK['tasa'] ?? null) !== null ? $uvH($uvPct($uvK['tasa'])) : '—' ?>
                </p>
                <p class="uv-kpi__foot"><?= $uvDelta($uvDeltas['tasa_pp'] ?? null, 'pp') ?></p>
            </article>

            <article class="uv-kpi uv-rise" style="--i:5">
                <div class="uv-kpi__head">
                    <span class="uv-kpi__icon" aria-hidden="true"><i class="bi bi-receipt-cutoff"></i></span>
                    <p class="uv-kpi__label">Promedio por venta</p>
                </div>
                <p class="uv-kpi__value">
                    <?= ($uvK['promedio'] ?? null) !== null ? $uvH($uvPrecio($uvK['promedio'])) : '—' ?>
                </p>
                <p class="uv-kpi__foot"><?= $uvDelta($uvDeltas['promedio'] ?? null) ?></p>
            </article>

        </div>


        <!-- ===================== GRÁFICAS PRINCIPALES ===================== -->

        <div class="uv-grid uv-grid--main">

            <section class="uv-panel uv-rise" style="--i:6" aria-labelledby="uv-h-ing">

                <header class="uv-panel__head">
                    <div>
                        <h2 id="uv-h-ing">Ingresos en el tiempo</h2>
                        <p>Ventas completadas <?= $uvH($uvAgrupTexto) ?> · pesos colombianos (COP)</p>
                    </div>
                    <span class="uv-panel__figure"><?= $uvH($uvPrecio($uvK['ingresos'] ?? 0)) ?></span>
                </header>

                <?php if ($uvSerieTieneDatos): ?>

                    <div class="uv-chart" id="uv-chart" role="group"
                         aria-label="Gráfica de barras de ingresos por ventas completadas, <?= $uvH($uvAgrupTexto) ?>">
                    </div>

                    <noscript>
                        <p class="uv-note">Activa JavaScript para ver la gráfica de ingresos.</p>
                    </noscript>

                <?php else: ?>

                    <div class="uv-chart-empty">
                        <i class="bi bi-bar-chart" aria-hidden="true"></i>
                        <p>Sin ventas completadas en este periodo.</p>
                        <span>Cuando una venta se complete, verás aquí sus ingresos.</span>
                    </div>

                <?php endif; ?>

            </section>


            <section class="uv-panel uv-rise" style="--i:7" aria-labelledby="uv-h-est">

                <header class="uv-panel__head">
                    <div>
                        <h2 id="uv-h-est">Operaciones por estado</h2>
                        <p>Sobre las <?= $uvTotal ?> del periodo</p>
                    </div>
                </header>

                <?php if ($uvTotal > 0): ?>

                    <?php
                    $uvR = 54;
                    $uvC = 2 * M_PI * $uvR;
                    $uvConDatos = 0;
                    foreach ($uvEstados as $uvE) { if ((int)$uvE['n'] > 0) { $uvConDatos++; } }
                    $uvGap  = $uvConDatos > 1 ? 3 : 0;
                    $uvAcum = 0.0;
                    ?>

                    <div class="uv-donut">

                        <svg viewBox="0 0 140 140" role="img"
                             aria-label="Distribución de las <?= $uvTotal ?> operaciones por estado">

                            <circle class="uv-donut__track" cx="70" cy="70" r="<?= $uvR ?>"></circle>

                            <?php foreach ($uvEstados as $uvI => $uvE):

                                $uvN = (int)$uvE['n'];

                                if ($uvN <= 0) { continue; }

                                $uvLen = ($uvN / $uvTotal) * $uvC;
                                $uvSeg = max($uvLen - $uvGap, 0.8);
                            ?>
                                <circle class="uv-donut__seg uv-seg--<?= $uvH($uvE['clave']) ?>"
                                        cx="70" cy="70" r="<?= $uvR ?>"
                                        transform="rotate(-90 70 70)"
                                        style="stroke-dasharray:<?= round($uvSeg, 2) ?> <?= round($uvC, 2) ?>;
                                               stroke-dashoffset:<?= round(-$uvAcum, 2) ?>;
                                               --d:<?= (int)$uvI * 120 ?>ms"></circle>
                            <?php
                                $uvAcum += $uvLen;
                            endforeach; ?>

                        </svg>

                        <div class="uv-donut__center">
                            <strong><?= $uvTotal ?></strong>
                            <span><?= $uvTotal === 1 ? 'operación' : 'operaciones' ?></span>
                        </div>

                    </div>

                    <ul class="uv-legend">
                        <?php foreach ($uvEstados as $uvE): ?>
                            <li>
                                <span class="uv-dot uv-seg--<?= $uvH($uvE['clave']) ?>" aria-hidden="true"></span>
                                <span class="uv-legend__name"><?= $uvH($uvE['etiqueta']) ?></span>
                                <span class="uv-legend__n"><?= (int)$uvE['n'] ?></span>
                                <span class="uv-legend__pct"><?= $uvH($uvPct($uvE['pct'])) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                <?php else: ?>

                    <div class="uv-chart-empty">
                        <i class="bi bi-pie-chart" aria-hidden="true"></i>
                        <p>No hay operaciones en este periodo.</p>
                        <a href="<?= $uvH($uvUrlPeriodo('todo')) ?>">Ver todo el tiempo</a>
                    </div>

                <?php endif; ?>

            </section>

        </div>


        <!-- ===================== ANÁLISIS ===================== -->

        <?php if (!empty($uvMetodos) || !empty($uvMayores)): ?>

            <div class="uv-grid uv-grid--pair">

                <?php if (!empty($uvMetodos)): ?>

                    <section class="uv-panel uv-rise" style="--i:8" aria-labelledby="uv-h-met">

                        <header class="uv-panel__head">
                            <div>
                                <h2 id="uv-h-met">Ingresos por método de pago</h2>
                                <p>Solo ventas completadas</p>
                            </div>
                        </header>

                        <ul class="uv-methods">
                            <?php foreach ($uvMetodos as $uvM):
                                $uvNombre = $uvNombresMetodo[$uvM['clave']] ?? ucfirst($uvM['clave']);
                            ?>
                                <li>
                                    <div class="uv-methods__row">
                                        <span class="uv-methods__name"><?= $uvH($uvNombre) ?></span>
                                        <span class="uv-methods__amount"><?= $uvH($uvPrecio($uvM['importe'])) ?></span>
                                        <span class="uv-methods__pct"><?= $uvH($uvPct($uvM['pct'])) ?></span>
                                    </div>
                                    <svg class="uv-hbar" height="8" aria-hidden="true">
                                        <rect class="uv-hbar__track" width="100%" height="8" rx="4"></rect>
                                        <rect class="uv-hbar__fill" width="<?= round($uvM['pct'], 2) ?>%" height="8" rx="4"></rect>
                                    </svg>
                                    <span class="uv-methods__sub">
                                        <?= (int)$uvM['n'] ?> <?= (int)$uvM['n'] === 1 ? 'venta' : 'ventas' ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                    </section>

                <?php endif; ?>


                <?php if (!empty($uvMayores)): ?>

                    <section class="uv-panel uv-rise" style="--i:9" aria-labelledby="uv-h-may">

                        <header class="uv-panel__head">
                            <div>
                                <h2 id="uv-h-may">Mayores ventas</h2>
                                <p>Las ventas completadas de más valor</p>
                            </div>
                        </header>

                        <ol class="uv-top">
                            <?php foreach ($uvMayores as $uvPos => $uvMy):
                                $uvComp = trim(($uvMy['nombre_comprador'] ?? '') . ' ' . ($uvMy['apellido_comprador'] ?? ''));
                            ?>
                                <li>
                                    <span class="uv-top__rank"><?= $uvPos + 1 ?></span>
                                    <div class="uv-top__info">
                                        <strong title="<?= $uvH($uvMy['titulo'] ?? '') ?>"><?= $uvH($uvMy['titulo'] ?? '') ?></strong>
                                        <span>
                                            <?= $uvH($uvDia($uvMy['fecha_compra'] ?? '')) ?>
                                            <?= $uvComp !== '' ? ' · ' . $uvH($uvComp) : '' ?>
                                        </span>
                                    </div>
                                    <span class="uv-top__amount"><?= $uvH($uvPrecio($uvMy['precio'] ?? 0)) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ol>

                    </section>

                <?php endif; ?>

            </div>

        <?php endif; ?>


        <!-- ===================== TABLA DE OPERACIONES ===================== -->

        <section class="uv-panel uv-ops uv-rise" style="--i:10" aria-labelledby="uv-h-ops">

            <header class="uv-panel__head">
                <div>
                    <h2 id="uv-h-ops">Operaciones</h2>
                    <p>
                        <?= $uvTotal ?> <?= $uvTotal === 1 ? 'operación' : 'operaciones' ?>
                        · <?= $uvFilasCompletadas ?> <?= $uvFilasCompletadas === 1 ? 'completada' : 'completadas' ?>
                        por <?= $uvH($uvPrecio($uvFilasImporte)) ?>
                    </p>
                </div>
            </header>

            <?php if (empty($uvOps)): ?>

                <div class="uv-chart-empty">
                    <i class="bi bi-inboxes" aria-hidden="true"></i>
                    <p>No hay operaciones en este periodo.</p>
                    <a href="<?= $uvH($uvUrlPeriodo('todo')) ?>">Ver todo el tiempo</a>
                </div>

            <?php else: ?>

                <div class="uv-tablewrap">

                    <table class="uv-table">

                        <thead>
                            <tr>
                                <th scope="col">Libro</th>
                                <th scope="col">Comprador</th>
                                <th scope="col">Fecha</th>
                                <th scope="col">Tipo</th>
                                <th scope="col" class="is-num">Valor</th>
                                <th scope="col">Estado</th>
                                <th scope="col"><span class="uv-sr">Detalle</span></th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($uvOps as $uvPos => $venta):

                                $titulo  = (string)($venta['titulo'] ?? '');
                                $portada = $uvPortada($venta['imagen'] ?? '');
                                $estado  = strtolower((string)($venta['estado'] ?? 'pendiente'));

                                switch ($estado) {
                                    case 'pendiente':
                                        $estadoTexto = 'Pendiente';  $estadoClase = 'warning'; break;
                                    case 'confirmada':
                                        $estadoTexto = 'Completada'; $estadoClase = 'success'; break;
                                    case 'cancelada':
                                        $estadoTexto = 'Cancelada';  $estadoClase = 'danger';  break;
                                    case 'rechazada':
                                        $estadoTexto = 'Rechazada';  $estadoClase = 'neutral'; break;
                                    default:
                                        $estadoTexto = ucfirst($estado); $estadoClase = 'neutral'; break;
                                }

                                /* Alias reales de Compra::obtenerOperacionesVendedor() */
                                $comprador = trim(
                                    ($venta['nombre_comprador'] ?? '') . ' ' . ($venta['apellido_comprador'] ?? '')
                                );

                                $fechaDia  = $uvDia($venta['fecha_compra'] ?? '');
                                $fechaHora = $uvHoraDe($venta['fecha_compra'] ?? '');
                                $fechaIso  = !empty($venta['fecha_compra'])
                                    ? date('c', (int)strtotime($venta['fecha_compra'])) : '';

                                $metodoClave = strtolower((string)($venta['metodo_pago'] ?? ''));
                                $metodo = $uvNombresMetodo[$metodoClave] ?? ucfirst($metodoClave);

                                $oculta = $uvPos >= 8;
                            ?>

                                <tr class="uv-row<?= $estado === 'confirmada' ? ' is-income' : '' ?>"<?= $oculta ? ' hidden data-uv-extra' : '' ?>>

                                    <td data-label="Libro" class="uv-cell-book">

                                        <div class="uv-cover<?= $portada === '' ? ' is-empty' : '' ?>">

                                            <?php if ($portada !== ''): ?>
                                                <img src="<?= $uvH($portada) ?>"
                                                     alt="Portada de <?= $uvH($titulo) ?>"
                                                     loading="lazy" decoding="async">
                                            <?php endif; ?>

                                            <span class="uv-cover__none"<?= $portada !== '' ? ' hidden' : '' ?>>
                                                <i class="bi bi-book" aria-hidden="true"></i>
                                                <span class="uv-sr">Sin portada</span>
                                            </span>

                                        </div>

                                        <div class="uv-book">
                                            <strong title="<?= $uvH($titulo) ?>"><?= $uvH($titulo) ?></strong>
                                            <?php if (!empty($venta['autor'])): ?>
                                                <span><?= $uvH($venta['autor']) ?></span>
                                            <?php endif; ?>
                                        </div>

                                    </td>

                                    <td data-label="Comprador">
                                        <?= $comprador !== '' ? $uvH($comprador) : '<span class="uv-muted">—</span>' ?>
                                    </td>

                                    <td data-label="Fecha">
                                        <time datetime="<?= $uvH($fechaIso) ?>">
                                            <span class="uv-date"><?= $uvH($fechaDia) ?></span>
                                            <span class="uv-time"><?= $uvH($fechaHora) ?></span>
                                        </time>
                                    </td>

                                    <td data-label="Tipo">
                                        <span class="uv-type"><i class="bi bi-tag" aria-hidden="true"></i>Venta</span>
                                        <?php if ($metodo !== ''): ?>
                                            <span class="uv-time uv-method"><?= $uvH($metodo) ?></span>
                                        <?php endif; ?>
                                    </td>

                                    <td data-label="Valor" class="is-num">
                                        <span class="uv-amount"><?= $uvH($uvPrecio($venta['precio'] ?? 0)) ?></span>
                                    </td>

                                    <td data-label="Estado">
                                        <span class="uv-status uv-status--<?= $estadoClase ?>"><?= $uvH($estadoTexto) ?></span>
                                    </td>

                                    <td class="uv-cell-action">
                                        <a href="index.php?accion=detalle_compra&id=<?= (int)$venta['id'] ?>"
                                           class="uv-btn uv-btn--sm"
                                           aria-label="Ver operación #<?= (int)$venta['id'] ?>: <?= $uvH($titulo) ?>">
                                            Ver operación
                                            <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                                        </a>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

                <?php if (count($uvOps) > 8): ?>
                    <button type="button" class="uv-more" data-uv-more aria-expanded="false">
                        Mostrar las <?= count($uvOps) - 8 ?> restantes
                    </button>
                <?php endif; ?>

            <?php endif; ?>

        </section>


        <p class="uv-foot">
            Ingresos = valor de las ventas completadas, no ganancia neta.
            Las operaciones se ubican en la fecha en que se registraron.
        </p>

        <script type="application/json" id="uv-data"><?= json_encode(
            $uvDatosJs,
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE
        ) ?></script>


    <?php endif; ?>

</section>

<script src="assets/js/ub-mis-ventas.js" defer></script>

<?php include "views/partials/footer.php"; ?>
