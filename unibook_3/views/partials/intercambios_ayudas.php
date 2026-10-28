<?php

/*
|--------------------------------------------------------------------------
| Mis intercambios — utilidades de PRESENTACIÓN (solo lectura)
|--------------------------------------------------------------------------
| Las usan intercambios_recibidos.php e intercambios_enviados.php.
| No consultan la base de datos por su cuenta: solo reutilizan funciones que
| ya existen (IntercambioController::recibidos/enviados,
| LibroController::detalle, UsuarioController::obtenerPorId) y no cambian
| ninguna consulta, ruta ni permiso.
|--------------------------------------------------------------------------
*/

$ixLibroCtl   = $libroController ?? null;
$ixUsuarioCtl = $usuarioController ?? null;

$ixH = function ($texto) {
    return htmlspecialchars((string)$texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};

$ixMeses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

/* "23 sep 2026, 2:27 p. m." (vacío si no hay fecha) */
$ixFecha = function ($valor) use ($ixMeses) {

    $ts = !empty($valor) ? strtotime($valor) : false;

    if (!$ts) {
        return '';
    }

    return (int)date('j', $ts) . ' '
        . $ixMeses[(int)date('n', $ts) - 1] . ' '
        . date('Y', $ts) . ', '
        . date('g:i', $ts) . ' '
        . (date('a', $ts) === 'am' ? 'a. m.' : 'p. m.');
};

/*
 * Portada: solo devuelve una ruta si el archivo existe de verdad (o es una
 * URL absoluta); si no, '' y la tarjeta muestra "Sin portada".
 */
$ixPortada = function ($ruta) {

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

    return is_file(dirname(__DIR__, 2) . '/' . $ruta) ? $ruta : '';
};

/* Portada de un libro a partir de su id (LibroController::detalle). */
$ixPortadaDe = function ($libroId) use ($ixLibroCtl, $ixPortada) {

    if (!$ixLibroCtl || (int)$libroId <= 0) {
        return '';
    }

    $libro = $ixLibroCtl->detalle((int)$libroId);

    return is_array($libro) ? $ixPortada($libro['imagen'] ?? '') : '';
};

/* Nombre completo de un usuario a partir de su id (UsuarioController::obtenerPorId). */
$ixNombreDe = function ($usuarioId) use ($ixUsuarioCtl) {

    if (!$ixUsuarioCtl || (int)$usuarioId <= 0) {
        return '';
    }

    $u = $ixUsuarioCtl->obtenerPorId((int)$usuarioId);

    return is_array($u)
        ? trim(($u['nombre'] ?? '') . ' ' . ($u['apellido'] ?? ''))
        : '';
};

$ixInicial = function ($nombre) {

    $nombre = trim((string)$nombre);

    if ($nombre === '') {
        return '?';
    }

    return function_exists('mb_substr')
        ? mb_strtoupper(mb_substr($nombre, 0, 1, 'UTF-8'), 'UTF-8')
        : strtoupper(substr($nombre, 0, 1));
};

/*
 * Estados que existen hoy en la tabla `intercambios`: pendiente, aceptado y
 * rechazado. Cualquier otro valor se muestra tal cual, en estilo neutro.
 */
$ixEstado = function ($estado) {

    $estado = strtolower(trim((string)$estado));

    switch ($estado) {

        case 'pendiente':
            return ['Pendiente', 'warning', 'bi-clock'];

        case 'aceptado':
            return ['Aceptado', 'success', 'bi-check-lg'];

        case 'rechazado':
            return ['Rechazado', 'danger', 'bi-x-lg'];

        default:
            return [ucfirst($estado), 'neutral', 'bi-dot'];
    }
};

/* Contadores reales de cada pestaña (mismas funciones del controlador). */
$ixListaRecibidos = (isset($intercambiosRecibidos) && is_array($intercambiosRecibidos))
    ? $intercambiosRecibidos
    : (isset($intercambioController) ? $intercambioController->recibidos() : []);

$ixListaEnviados = (isset($intercambiosEnviados) && is_array($intercambiosEnviados))
    ? $intercambiosEnviados
    : (isset($intercambioController) ? $intercambioController->enviados() : []);

$ixTotalRecibidos = count($ixListaRecibidos);
$ixTotalEnviados  = count($ixListaEnviados);
