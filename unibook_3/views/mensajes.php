<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.php?accion=login");
    exit();
}

/* Conversaciones reales (las entrega index.php, ya ordenadas por actividad). */
$conversaciones = $conversaciones ?? [];

/*
|--------------------------------------------------------------------------
| Datos de presentación (solo lectura de lo que ya llega a la vista)
|--------------------------------------------------------------------------
*/

$ubMsgDias = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];

/* Hora si es de hoy, "Ayer", día de la semana (últimos 6 días) o fecha. */
$ubMsgFecha = function ($valor) use ($ubMsgDias) {

    $ts = !empty($valor) ? strtotime($valor) : false;

    if (!$ts) {
        return '';
    }

    $hoy = strtotime('today');

    if ($ts >= $hoy) {
        return date('H:i', $ts);
    }

    if ($ts >= $hoy - 86400) {
        return 'Ayer';
    }

    if ($ts >= $hoy - 6 * 86400) {
        return $ubMsgDias[(int)date('w', $ts)];
    }

    return date('d/m/Y', $ts);
};

$ubMsgInicial = function ($nombre) {

    $nombre = trim((string)$nombre);

    if ($nombre === '') {
        return '?';
    }

    return function_exists('mb_substr')
        ? mb_strtoupper(mb_substr($nombre, 0, 1, 'UTF-8'), 'UTF-8')
        : strtoupper(substr($nombre, 0, 1));
};

/* Personas destacadas: una por usuario, primero quienes tienen mensajes
   sin leer y después las más recientes. */
$ubMsgPersonas = [];

foreach ($conversaciones as $ubMsgPos => $ubMsgConv) {

    $ubMsgUid = (int)($ubMsgConv['otro_usuario_id'] ?? 0);

    if (!isset($ubMsgPersonas[$ubMsgUid])) {

        $ubMsgPersonas[$ubMsgUid] = [
            'uid'       => $ubMsgUid,
            'nombre'    => $ubMsgConv['otro_usuario'] ?? 'Usuario',
            'libro_id'  => (int)($ubMsgConv['libro_id'] ?? 0),
            'pos'       => $ubMsgPos,
            'no_leidos' => 0,
        ];
    }

    $ubMsgPersonas[$ubMsgUid]['no_leidos'] += (int)($ubMsgConv['no_leidos'] ?? 0);
}

usort($ubMsgPersonas, function ($a, $b) {

    $ua = $a['no_leidos'] > 0 ? 0 : 1;
    $ub = $b['no_leidos'] > 0 ? 0 : 1;

    if ($ua !== $ub) {
        return $ua <=> $ub;
    }

    return $a['pos'] <=> $b['pos'];
});

$ubMsgDestacadas = array_slice($ubMsgPersonas, 0, 10);

$ubMsgTotalNoLeidos = 0;

foreach ($conversaciones as $ubMsgConv) {
    $ubMsgTotalNoLeidos += (int)($ubMsgConv['no_leidos'] ?? 0);
}

?>

<?php include "views/partials/navbar.php"; ?>

<section class="ub-msg" id="ubMsg">

    <div class="ub-msg__panel ub-panel">

        <!-- ===================== ENCABEZADO ===================== -->

        <header class="ub-msg__head">

            <button type="button" class="ub-msg__edit" data-msg-edit aria-pressed="false"<?php echo empty($conversaciones) ? ' disabled' : ''; ?>>
                <span data-msg-edit-label>Editar</span>
            </button>

            <h1>Mensajes</h1>

            <details class="ub-msg__menu">

                <summary class="ub-iconbtn ub-iconbtn--glass" aria-label="Opciones de mensajes">
                    <i class="bi bi-three-dots" aria-hidden="true"></i>
                </summary>

                <div class="ub-msg__menu-panel">

                    <button type="button" data-msg-unread aria-pressed="false"<?php echo empty($conversaciones) ? ' disabled' : ''; ?>>
                        <i class="bi bi-envelope" aria-hidden="true"></i>
                        <span>Solo no leídos<?php echo $ubMsgTotalNoLeidos > 0 ? ' (' . (int)$ubMsgTotalNoLeidos . ')' : ''; ?></span>
                    </button>

                    <button type="button" data-msg-showhidden aria-pressed="false" hidden>
                        <i class="bi bi-eye" aria-hidden="true"></i>
                        <span>Mostrar ocultas (<b data-msg-hidden-count>0</b>)</span>
                    </button>

                    <a href="index.php?accion=catalogo">
                        <i class="bi bi-bookshelf" aria-hidden="true"></i>
                        <span>Buscar un libro</span>
                    </a>

                </div>

            </details>

        </header>

        <?php if (!empty($conversaciones)): ?>

            <!-- ===================== DESTACADAS ===================== -->

            <section class="ub-msg__featured" aria-label="Conversaciones destacadas">

                <?php foreach ($ubMsgDestacadas as $ubMsgP): ?>

                    <a class="ub-msg-fav"
                       href="index.php?accion=chat&id=<?php echo (int)$ubMsgP['libro_id']; ?>&usuario=<?php echo (int)$ubMsgP['uid']; ?>"
                       title="<?php echo htmlspecialchars($ubMsgP['nombre'], ENT_QUOTES, 'UTF-8'); ?>">

                        <span class="ub-msg-avatar ub-msg-avatar--lg ub-av-<?php echo (int)$ubMsgP['uid'] % 6; ?>">
                            <?php echo htmlspecialchars($ubMsgInicial($ubMsgP['nombre']), ENT_QUOTES, 'UTF-8'); ?>
                            <?php if ($ubMsgP['no_leidos'] > 0): ?>
                                <i class="ub-msg-dot" aria-label="Mensajes sin leer"></i>
                            <?php endif; ?>
                        </span>

                        <span class="ub-msg-fav__name"><?php echo htmlspecialchars($ubMsgP['nombre'], ENT_QUOTES, 'UTF-8'); ?></span>

                    </a>

                <?php endforeach; ?>

            </section>

            <!-- ===================== LISTA ===================== -->

            <div class="ub-msg__list" id="ubMsgLista">

                <?php foreach ($conversaciones as $conversacion):

                    $ubMsgNombre   = $conversacion['otro_usuario'] ?? 'Usuario';
                    $ubMsgLibro    = $conversacion['titulo'] ?? 'Libro';
                    $ubMsgPreview  = $conversacion['ultimo_mensaje'] ?? 'Sin mensajes';
                    $ubMsgUsuario  = (int)($conversacion['otro_usuario_id'] ?? 0);
                    $ubMsgNoLeidos = (int)($conversacion['no_leidos'] ?? 0);
                    $ubMsgFechaRaw = $conversacion['fecha_creacion'] ?? '';
                    $ubMsgClave    = (int)$conversacion['libro_id'] . ':' . $ubMsgUsuario;
                    $ubMsgTitleFecha = !empty($ubMsgFechaRaw) && strtotime($ubMsgFechaRaw)
                        ? date('d/m/Y H:i', strtotime($ubMsgFechaRaw))
                        : '';
                ?>

                    <div class="ub-msg-item<?php echo $ubMsgNoLeidos > 0 ? ' is-unread' : ''; ?>"
                         data-msg-item
                         data-unread="<?php echo $ubMsgNoLeidos; ?>"
                         data-key="<?php echo htmlspecialchars($ubMsgClave, ENT_QUOTES, 'UTF-8'); ?>"
                         data-fecha="<?php echo htmlspecialchars($ubMsgFechaRaw, ENT_QUOTES, 'UTF-8'); ?>"
                         data-buscar="<?php echo htmlspecialchars($ubMsgNombre . ' ' . $ubMsgLibro . ' ' . $ubMsgPreview, ENT_QUOTES, 'UTF-8'); ?>">

                        <button type="button" class="ub-msg-hide" data-msg-hide aria-label="Ocultar conversación con <?php echo htmlspecialchars($ubMsgNombre, ENT_QUOTES, 'UTF-8'); ?> en este dispositivo">
                            <i class="bi bi-dash-lg" aria-hidden="true"></i>
                        </button>

                        <a class="ub-msg-row"
                           href="index.php?accion=chat&id=<?php echo (int)$conversacion['libro_id']; ?>&usuario=<?php echo $ubMsgUsuario; ?>">

                            <span class="ub-msg-avatar ub-av-<?php echo $ubMsgUsuario % 6; ?>">
                                <?php echo htmlspecialchars($ubMsgInicial($ubMsgNombre), ENT_QUOTES, 'UTF-8'); ?>
                            </span>

                            <span class="ub-msg-row__body">

                                <span class="ub-msg-row__top">
                                    <strong><?php echo htmlspecialchars($ubMsgNombre, ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <small class="ub-msg-row__book"><i class="bi bi-journal-bookmark" aria-hidden="true"></i> <?php echo htmlspecialchars($ubMsgLibro, ENT_QUOTES, 'UTF-8'); ?></small>
                                    <time class="ub-msg-row__time"<?php echo $ubMsgTitleFecha !== '' ? ' title="' . htmlspecialchars($ubMsgTitleFecha, ENT_QUOTES, 'UTF-8') . '"' : ''; ?>>
                                        <?php echo htmlspecialchars($ubMsgFecha($ubMsgFechaRaw), ENT_QUOTES, 'UTF-8'); ?>
                                    </time>
                                </span>

                                <span class="ub-msg-row__bottom">
                                    <span class="ub-msg-row__preview"><?php echo htmlspecialchars($ubMsgPreview, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php if ($ubMsgNoLeidos > 0): ?>
                                        <span class="ub-count ub-count--sm" aria-label="<?php echo $ubMsgNoLeidos; ?> sin leer"><?php echo $ubMsgNoLeidos > 99 ? '99+' : $ubMsgNoLeidos; ?></span>
                                    <?php endif; ?>
                                </span>

                            </span>

                            <i class="bi bi-chevron-right ub-msg-row__chev" aria-hidden="true"></i>

                        </a>

                    </div>

                <?php endforeach; ?>

                <p class="ub-msg__none" id="ubMsgNinguno" hidden>
                    No hay conversaciones que coincidan.
                </p>

            </div>

            <!-- ===================== BARRA FLOTANTE ===================== -->

            <div class="ub-msg__bar">

                <label class="ub-msg__search">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input type="search"
                           id="ubMsgBuscar"
                           placeholder="Buscar conversaciones"
                           aria-label="Buscar entre tus conversaciones"
                           autocomplete="off">
                </label>

                <a class="ub-msg__new"
                   href="index.php?accion=catalogo"
                   title="Nueva conversación: elige un libro y contacta al vendedor"
                   aria-label="Nueva conversación: elige un libro y contacta al vendedor">
                    <i class="bi bi-pencil-square" aria-hidden="true"></i>
                </a>

            </div>

        <?php else: ?>

            <div class="mensajes-vacio">

                <div class="icono">
                    <i class="bi bi-chat-dots ub-icon" aria-hidden="true"></i>
                </div>

                <h3>Aún no tienes conversaciones</h3>

                <p>
                    Cuando contactes a un vendedor desde un libro,
                    tu conversación aparecerá aquí.
                </p>

                <a href="index.php?accion=catalogo" class="btn-ub btn-ub-primary">
                    <i class="bi bi-bookshelf ub-icon" aria-hidden="true"></i> Ir al catálogo
                </a>

            </div>

        <?php endif; ?>

    </div>

</section>

<script>
(function () {
  "use strict";

  var root = document.getElementById("ubMsg");
  if (!root) return;

  var items = [].slice.call(root.querySelectorAll("[data-msg-item]"));
  if (!items.length) return;

  var search = document.getElementById("ubMsgBuscar");
  var none = document.getElementById("ubMsgNinguno");
  var btnEdit = root.querySelector("[data-msg-edit]");
  var btnUnread = root.querySelector("[data-msg-unread]");
  var btnShowHidden = root.querySelector("[data-msg-showhidden]");
  var hiddenCount = root.querySelector("[data-msg-hidden-count]");
  var menu = root.querySelector(".ub-msg__menu");
  var KEY = "ub-msg-ocultas";

  var state = { onlyUnread: false, showHidden: false, editing: false };

  function norm(t) {
    t = String(t || "").toLowerCase();
    return t.normalize ? t.normalize("NFD").replace(/[\u0300-\u036f]/g, "") : t;
  }

  /* Ocultas en ESTE dispositivo: clave -> fecha del último mensaje.
     Si llega un mensaje nuevo, la fecha cambia y la conversación reaparece. */
  function readHidden() {
    try { return JSON.parse(localStorage.getItem(KEY) || "{}") || {}; } catch (e) { return {}; }
  }
  function writeHidden(o) {
    try { localStorage.setItem(KEY, JSON.stringify(o)); } catch (e) {}
  }

  function apply() {
    var q = norm(search ? search.value : "");
    var hidden = readHidden();
    var visibles = 0, ocultas = 0;

    items.forEach(function (el) {
      var isHidden = hidden[el.dataset.key] === el.dataset.fecha;
      if (isHidden) ocultas++;
      var ok = (!q || norm(el.dataset.buscar).indexOf(q) !== -1) &&
               (!state.onlyUnread || Number(el.dataset.unread) > 0) &&
               (state.showHidden || !isHidden);
      el.hidden = !ok;
      el.classList.toggle("is-hidden-local", isHidden);
      var ic = el.querySelector("[data-msg-hide] i");
      if (ic) { ic.classList.toggle("bi-dash-lg", !isHidden); ic.classList.toggle("bi-plus-lg", isHidden); }
      if (ok) visibles++;
    });

    if (none) none.hidden = visibles !== 0;
    if (btnShowHidden) {
      btnShowHidden.hidden = ocultas === 0;
      if (hiddenCount) hiddenCount.textContent = ocultas;
    }
  }

  if (search) search.addEventListener("input", apply);

  if (btnEdit) btnEdit.addEventListener("click", function () {
    state.editing = !state.editing;
    root.classList.toggle("is-editing", state.editing);
    btnEdit.setAttribute("aria-pressed", state.editing ? "true" : "false");
    btnEdit.querySelector("[data-msg-edit-label]").textContent = state.editing ? "Listo" : "Editar";
  });

  items.forEach(function (el) {
    var b = el.querySelector("[data-msg-hide]");
    if (!b) return;
    b.addEventListener("click", function () {
      var h = readHidden();
      if (el.classList.contains("is-hidden-local")) delete h[el.dataset.key];
      else h[el.dataset.key] = el.dataset.fecha;
      writeHidden(h);
      apply();
    });
  });

  if (btnUnread) btnUnread.addEventListener("click", function () {
    state.onlyUnread = !state.onlyUnread;
    btnUnread.setAttribute("aria-pressed", state.onlyUnread ? "true" : "false");
    apply();
  });

  if (btnShowHidden) btnShowHidden.addEventListener("click", function () {
    state.showHidden = !state.showHidden;
    btnShowHidden.setAttribute("aria-pressed", state.showHidden ? "true" : "false");
    apply();
  });

  document.addEventListener("click", function (e) {
    if (menu && menu.open && !menu.contains(e.target)) menu.open = false;
  });

  apply();
})();
</script>

<?php include "views/partials/footer.php"; ?>
