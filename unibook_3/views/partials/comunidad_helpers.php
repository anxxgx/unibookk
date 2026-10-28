<?php

/*
 * FASE 5 — Comunidad: utilidades de presentación compartidas por las vistas.
 * Se incluye al inicio de cada vista de Comunidad (no imprime nada).
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$comDatos = (isset($comunidadDatos) && is_array($comunidadDatos)) ? $comunidadDatos : [];

$comUid = (int)($comDatos['uid'] ?? 0);

/* Escapa cualquier dato antes de imprimirlo en HTML. */
$comE = function ($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
};

/* Texto de usuario: escapado y con saltos de línea. */
$comTexto = function ($valor) use ($comE) {
    return nl2br($comE($valor));
};

$comInicial = function ($nombre) {

    $nombre = trim((string)$nombre);

    if ($nombre === '') {
        return '?';
    }

    return function_exists('mb_substr')
        ? mb_strtoupper(mb_substr($nombre, 0, 1, 'UTF-8'), 'UTF-8')
        : strtoupper(substr($nombre, 0, 1));
};

/* "Hace 5 min", "Hace 2 h", "Hace 3 d" o la fecha. */
$comFecha = function ($valor) {

    $ts = !empty($valor) ? strtotime($valor) : false;

    if (!$ts) {
        return '';
    }

    $d = time() - $ts;

    if ($d < 60) {
        return 'Hace un momento';
    }

    if ($d < 3600) {
        return 'Hace ' . (int)floor($d / 60) . ' min';
    }

    if ($d < 86400) {
        return 'Hace ' . (int)floor($d / 3600) . ' h';
    }

    if ($d < 7 * 86400) {
        return 'Hace ' . (int)floor($d / 86400) . ' d';
    }

    return date('d/m/Y', $ts);
};

$comFechaCompleta = function ($valor) {

    $ts = !empty($valor) ? strtotime($valor) : false;

    return $ts ? date('d/m/Y H:i', $ts) : '';
};

/* Avatar: foto de perfil si existe y es válida; si no, la inicial. */
$comAvatar = function ($nombre, $foto, $clase = '') use ($comE, $comInicial, $usuarioController) {

    $ruta = $usuarioController->rutaFotoValida($foto);

    if ($ruta) {
        return '<span class="ub-avatar uc-avatar ' . $comE($clase) . '">'
            . '<img src="' . $comE($ruta) . '" alt="" loading="lazy">'
            . '</span>';
    }

    return '<span class="ub-avatar uc-avatar ' . $comE($clase) . '" aria-hidden="true">'
        . $comE($comInicial($nombre))
        . '</span>';
};

/* Fecha larga «22 oct 2025 · 10:24 a. m.» y hora «10:24 a. m.» (solo presentación). */
$comMeses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

$comHora = function ($valor) {

    $ts = !empty($valor) ? strtotime($valor) : false;

    if (!$ts) {
        return '';
    }

    return str_replace(['am', 'pm'], ['a. m.', 'p. m.'], date('h:i a', $ts));
};

$comFechaLarga = function ($valor) use ($comMeses, $comHora) {

    $ts = !empty($valor) ? strtotime($valor) : false;

    if (!$ts) {
        return '';
    }

    return (int)date('j', $ts) . ' ' . $comMeses[(int)date('n', $ts) - 1]
        . ' ' . date('Y', $ts) . ' · ' . $comHora($valor);
};

/* Estados con el nombre que ve el estudiante (el valor guardado no cambia). */
$comEstadoNombre = ['activa' => 'Abierta', 'resuelta' => 'Resuelta', 'cerrada' => 'Cerrada'];
$comEstadoIcono = ['activa' => 'bi-record-circle', 'resuelta' => 'bi-check-circle-fill', 'cerrada' => 'bi-lock-fill'];

$comCategorias = ComunidadController::categorias();
$comEstados = ComunidadController::estados();
$comModalidades = ComunidadController::modalidades();

/*
 * Nombres cortos de categoría SOLO para mostrar en filtros y etiquetas.
 * Las opciones del formulario siguen usando los nombres completos del
 * controlador (los valores enviados no cambian).
 */
$comCatCorta = [
    'busco_libro'            => 'Busco un libro',
    'recurso_academico'      => 'Recursos académicos',
    'pregunta_universitaria' => 'Preguntas universitarias',
    'recomendacion'          => 'Recomendaciones',
    'otro'                   => 'Otros temas',
];

/* Mensajes privados sin leer (lo calcula la barra lateral; sin consulta extra). */
$comSinLeer = isset($comunidadNoLeidosNavbar) ? (int)$comunidadNoLeidosNavbar : 0;

$comCsrf = ComunidadController::csrfToken();

/* Un token de un solo uso por página, compartido por sus formularios. */
$comNonce = $comUid > 0 ? ComunidadController::nonce() : '';

$comFlash = $comDatos['flash'] ?? null;

/* Aviso de la última acción. */
$comImprimirFlash = function () use ($comFlash, $comE) {

    if (!is_array($comFlash) || empty($comFlash['mensaje'])) {
        return;
    }

    $ok = ($comFlash['tipo'] ?? '') === 'ok';

    echo '<div class="uc-alert ' . ($ok ? 'uc-alert--ok' : 'uc-alert--error') . '" role="' . ($ok ? 'status' : 'alert') . '">'
        . '<i class="bi ' . ($ok ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill') . '" aria-hidden="true"></i>'
        . '<span>' . $comE($comFlash['mensaje']) . '</span>'
        . '</div>';
};
