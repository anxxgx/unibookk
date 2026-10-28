<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.php?accion=login");
    exit();
}

$usuarioActualChat =
    (int)$_SESSION['id_usuario'];

$otroUsuarioChatId =
    (int)($otroUsuarioChatId ?? 0);

$nombreOtroUsuario =
    'Usuario';

if (!empty($vendedorChat)) {
    $nombreOtroUsuario =
        $vendedorChat['nombre'] ?? 'Usuario';
}

/*
 * Si existe información del otro usuario desde el controlador,
 * la usamos para mostrar correctamente la conversación.
 */
if (
    isset($otroUsuarioChat) &&
    is_array($otroUsuarioChat)
) {
    $nombreOtroUsuario =
        $otroUsuarioChat['nombre']
        ?? $nombreOtroUsuario;
}

$libroChatId =
    (int)($libroChat['id'] ?? 0);

$tituloLibro =
    $libroChat['titulo'] ?? 'Libro';

$imagenLibro =
    $libroChat['imagen'] ?? '';

$mensajesChat =
    $mensajesChat ?? [];

/*
|--------------------------------------------------------------------------
| Datos de presentación (solo lectura de lo que ya llega a la vista)
|--------------------------------------------------------------------------
*/

$ubChatH = function ($texto) {
    return htmlspecialchars((string)$texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};

$ubChatInicial = function ($nombre) {

    $nombre = trim((string)$nombre);

    if ($nombre === '') {
        return '?';
    }

    return function_exists('mb_substr')
        ? mb_strtoupper(mb_substr($nombre, 0, 1, 'UTF-8'), 'UTF-8')
        : strtoupper(substr($nombre, 0, 1));
};

$ubChatDias  = ['dom', 'lun', 'mar', 'mié', 'jue', 'vie', 'sáb'];
$ubChatMeses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

/* "6:23 a. m." */
$ubChatHora = function ($ts) {
    return date('g:i', $ts) . ' ' . (date('a', $ts) === 'am' ? 'a. m.' : 'p. m.');
};

/* Texto del separador de fecha: "Hoy, 3:45 p. m." / "lun, 14 de sep., 6:23 a. m." */
$ubChatSeparador = function ($ts) use ($ubChatDias, $ubChatMeses, $ubChatHora) {

    $hoy = strtotime('today');

    if ($ts >= $hoy) {
        return 'Hoy, ' . $ubChatHora($ts);
    }

    if ($ts >= $hoy - 86400) {
        return 'Ayer, ' . $ubChatHora($ts);
    }

    $anio = date('Y', $ts) !== date('Y') ? ' de ' . date('Y', $ts) : '';

    return $ubChatDias[(int)date('w', $ts)] . ', '
        . (int)date('j', $ts) . ' de '
        . $ubChatMeses[(int)date('n', $ts) - 1] . '.' . $anio . ', '
        . $ubChatHora($ts);
};

/* Mensaje compuesto solo por emojis: se muestra grande y sin burbuja. */
$ubChatSoloEmoji = function ($texto) {

    $texto = trim((string)$texto);

    if ($texto === '') {
        return false;
    }

    $largo = function_exists('mb_strlen') ? mb_strlen($texto, 'UTF-8') : strlen($texto);

    if ($largo > 16) {
        return false;
    }

    return (bool)preg_match(
        '/^[\x{1F000}-\x{1FAFF}\x{2190}-\x{21FF}\x{2300}-\x{23FF}\x{2600}-\x{27BF}\x{2B00}-\x{2BFF}\x{200D}\x{FE0F}\x{20E3}\x{1F3FB}-\x{1F3FF}\s]+$/u',
        $texto
    );
};

/* Preparamos cada mensaje: marca de tiempo, separador de fecha y agrupación. */
$ubChatItems = [];
$ubChatTotal = count($mensajesChat);
$ubChatPrevTs = null;
$ubChatPrevMio = null;

foreach (array_values($mensajesChat) as $ubChatPos => $ubChatMsg) {

    $ts  = strtotime($ubChatMsg['fecha_creacion'] ?? '') ?: 0;
    $mio = (int)$ubChatMsg['remitente_id'] === $usuarioActualChat;

    $separador = false;

    if (
        $ubChatPrevTs === null ||
        ($ts - $ubChatPrevTs) > 3600 ||
        date('Y-m-d', $ts) !== date('Y-m-d', $ubChatPrevTs)
    ) {
        $separador = true;
    }

    $ubChatItems[] = [
        'msg'        => $ubChatMsg,
        'ts'         => $ts,
        'mio'        => $mio,
        'separador'  => $separador,
        'nuevo_grupo' => $separador || $ubChatPrevMio !== $mio,
    ];

    $ubChatPrevTs  = $ts;
    $ubChatPrevMio = $mio;
}

/* La burbuja es "final de grupo" si el siguiente cambia de emisor o de bloque. */
foreach ($ubChatItems as $ubChatPos => $ubChatIt) {
    $ubChatItems[$ubChatPos]['fin_grupo'] =
        !isset($ubChatItems[$ubChatPos + 1]) ||
        $ubChatItems[$ubChatPos + 1]['nuevo_grupo'];
}

/* Estado de lectura: solo con el dato real (`leido`), solo en el último mensaje si es mío. */
$ubChatUltimo = $ubChatTotal > 0 ? $ubChatItems[$ubChatTotal - 1] : null;

$ubChatActionBase =
    'index.php?accion=chat&id=' . $libroChatId . '&usuario=' . $otroUsuarioChatId;

$ubChatUidClase = (int)$otroUsuarioChatId % 6;

?>

<?php include "views/partials/navbar.php"; ?>

<section class="ub-chat" id="ubChat">

    <div class="ub-chat__panel ub-panel">

        <!-- ===================== ENCABEZADO DEL CHAT ===================== -->

        <header class="ub-chat__head" id="ubChatHead">

            <a href="index.php?accion=mensajes"
               class="ub-chat__back"
               title="Volver a mensajes"
               aria-label="Volver a mensajes<?= (int)($mensajesNoLeidosNavbar ?? 0) > 0 ? ' (' . (int)$mensajesNoLeidosNavbar . ' sin leer)' : '' ?>">
                <i class="bi bi-chevron-left" aria-hidden="true"></i>
                <?php if ((int)($mensajesNoLeidosNavbar ?? 0) > 0): ?>
                    <span class="ub-chat__back-count"><?= (int)$mensajesNoLeidosNavbar > 99 ? '99+' : (int)$mensajesNoLeidosNavbar ?></span>
                <?php endif; ?>
            </a>

            <div class="ub-chat__who">

                <span class="ub-msg-avatar ub-chat__avatar ub-av-<?= $ubChatUidClase ?>" aria-hidden="true">
                    <?= $ubChatH($ubChatInicial($nombreOtroUsuario)) ?>
                </span>

                <span class="ub-chat__name"><?= $ubChatH($nombreOtroUsuario) ?></span>

                <span class="ub-chat__about">
                    <i class="bi bi-journal-bookmark" aria-hidden="true"></i>
                    <?= $ubChatH($tituloLibro) ?>
                </span>

            </div>

            <a href="index.php?accion=detalle_libro&id=<?= $libroChatId ?>"
               class="ub-chat__book"
               title="Ver el libro: <?= $ubChatH($tituloLibro) ?>"
               aria-label="Ver el libro: <?= $ubChatH($tituloLibro) ?>">

                <?php if (!empty($imagenLibro)): ?>
                    <img src="<?= $ubChatH($imagenLibro) ?>" alt="">
                <?php else: ?>
                    <i class="bi bi-bookshelf" aria-hidden="true"></i>
                <?php endif; ?>

            </a>

        </header>


        <!-- ===================== MENSAJES ===================== -->

        <div class="ub-chat__scroll" id="chatMessages" role="log" aria-live="polite" aria-label="Mensajes con <?= $ubChatH($nombreOtroUsuario) ?>">

            <?php if (empty($ubChatItems)): ?>

                <div class="ub-chat__empty">

                    <div class="ub-chat__empty-icon">
                        <i class="bi bi-chat-dots" aria-hidden="true"></i>
                    </div>

                    <h2>Inicia la conversación</h2>

                    <p>
                        Escribe un mensaje para contactar
                        sobre este libro.
                    </p>

                </div>

            <?php else: ?>

                <?php foreach ($ubChatItems as $ubChatPos => $ubChatIt):

                    $m       = $ubChatIt['msg'];
                    $esMio   = $ubChatIt['mio'];
                    $soloEmo = $ubChatSoloEmoji($m['mensaje']);

                    $clases = 'ub-bubble-row ' . ($esMio ? 'is-mine' : 'is-theirs');

                    if ($ubChatIt['nuevo_grupo'] && $ubChatPos > 0 && !$ubChatIt['separador']) {
                        $clases .= ' is-gap';
                    }

                    $claseBurbuja = 'ub-bubble';
                    if ($ubChatIt['fin_grupo']) { $claseBurbuja .= ' has-tail'; }
                    if ($soloEmo)               { $claseBurbuja .= ' is-emoji'; }

                    $fechaCompleta = $ubChatIt['ts'] ? date('d/m/Y H:i', $ubChatIt['ts']) : '';
                ?>

                    <?php if ($ubChatIt['separador'] && $ubChatIt['ts']): ?>
                        <div class="ub-chat__stamp"><span><?= $ubChatH($ubChatSeparador($ubChatIt['ts'])) ?></span></div>
                    <?php endif; ?>

                    <div class="<?= $clases ?>">

                        <div class="<?= $claseBurbuja ?>"<?= $fechaCompleta !== '' ? ' title="' . $ubChatH($fechaCompleta) . '"' : '' ?>><?= nl2br($ubChatH($m['mensaje'])) ?></div>

                    </div>

                    <?php if (
                        $esMio &&
                        $ubChatPos === $ubChatTotal - 1 &&
                        isset($m['leido'])
                    ): ?>
                        <div class="ub-chat__status<?= (int)$m['leido'] === 1 ? ' is-read' : '' ?>">
                            <?= (int)$m['leido'] === 1 ? 'Leído' : 'Enviado' ?>
                        </div>
                    <?php endif; ?>

                <?php endforeach; ?>

            <?php endif; ?>

        </div>


        <!-- ===================== COMPOSITOR ===================== -->

        <form class="ub-chat__composer"
              id="ubChatForm"
              method="POST"
              action="<?= $ubChatH($ubChatActionBase) ?>">

            <input type="hidden" name="enviar_mensaje" value="1">

            <input type="hidden" name="destinatario_id" value="<?= $otroUsuarioChatId ?>">

            <input type="hidden" name="libro_id" value="<?= $libroChatId ?>">

            <!--
                Guardamos explícitamente con quién estamos hablando.
                Así, después de enviar, seguimos exactamente
                dentro de esta misma conversación.
            -->
            <input type="hidden" name="otro_usuario_id" value="<?= $otroUsuarioChatId ?>">

            <div class="ub-chat__plus">

                <button type="button"
                        class="ub-chat__round"
                        data-chat-plus
                        aria-haspopup="true"
                        aria-expanded="false"
                        aria-label="Más opciones">
                    <i class="bi bi-plus-lg" aria-hidden="true"></i>
                </button>

                <div class="ub-chat__pop" data-chat-pop hidden>

                    <a href="index.php?accion=detalle_libro&id=<?= $libroChatId ?>">
                        <i class="bi bi-journal-bookmark" aria-hidden="true"></i>
                        <span>Ver el libro</span>
                    </a>

                </div>

            </div>

            <div class="ub-chat__field">

                <textarea name="mensaje"
                          class="ub-chat__input"
                          placeholder="Escribe un mensaje..."
                          aria-label="Escribe un mensaje"
                          required
                          rows="1"></textarea>

                <button type="submit"
                        class="ub-chat__send"
                        aria-label="Enviar mensaje"
                        title="Enviar">
                    <i class="bi bi-arrow-up" aria-hidden="true"></i>
                </button>

            </div>

        </form>

    </div>

</section>


<script>
(function () {
  "use strict";

  var root    = document.getElementById("ubChat");
  var scroll  = document.getElementById("chatMessages");
  var head    = document.getElementById("ubChatHead");
  var form    = document.getElementById("ubChatForm");
  if (!root || !scroll || !form) return;

  var input   = form.querySelector(".ub-chat__input");
  var send    = form.querySelector(".ub-chat__send");
  var plus    = form.querySelector("[data-chat-plus]");
  var pop     = form.querySelector("[data-chat-pop]");
  var coarse  = window.matchMedia && window.matchMedia("(pointer: coarse)").matches;
  var sending = false;

  /* ---- Medidas: los mensajes se desplazan por debajo del cristal ---- */
  function medir() {
    root.style.setProperty("--ub-chat-head", head.offsetHeight + "px");
    root.style.setProperty("--ub-chat-comp", form.offsetHeight + "px");
  }

  function alFondo() {
    scroll.scrollTop = scroll.scrollHeight;
  }

  medir();
  alFondo();

  if (window.ResizeObserver) {
    var ro = new ResizeObserver(function () {
      var cerca = scroll.scrollHeight - scroll.scrollTop - scroll.clientHeight < 140;
      medir();
      if (cerca) alFondo();
    });
    ro.observe(head);
    ro.observe(form);
  }

  window.addEventListener("load", alFondo);
  window.addEventListener("resize", medir);

  /* ---- Teclado móvil: ajustamos la altura al viewport visual ---- */
  if (window.visualViewport) {
    var vv = window.visualViewport;
    var ajustar = function () {
      var top = root.getBoundingClientRect().top;
      var libre = Math.round(vv.height - Math.max(top, 0) - 10);
      /* Con teclado abierto el panel ocupa el espacio visible; sin teclado, el CSS. */
      if (vv.height < window.innerHeight - 120) {
        root.style.setProperty("--ub-chat-h", Math.max(libre, 260) + "px");
        root.classList.add("is-keyboard");
      } else {
        root.style.removeProperty("--ub-chat-h");
        root.classList.remove("is-keyboard");
      }
      medir();
      alFondo();
    };
    vv.addEventListener("resize", ajustar);
    vv.addEventListener("scroll", ajustar);
  }

  /* ---- Campo: crece con el contenido ---- */
  function crecer() {
    input.style.height = "auto";
    input.style.height = Math.min(input.scrollHeight, 140) + "px";
    var vacio = input.value.trim() === "";
    send.disabled = vacio || sending;
    root.classList.toggle("has-text", !vacio);
  }

  input.addEventListener("input", crecer);
  crecer();

  /* ---- Enviar: botón y Enter (en táctil, Enter hace salto de línea) ---- */
  input.addEventListener("keydown", function (e) {
    if (e.key !== "Enter" || e.shiftKey || e.isComposing || coarse) return;
    e.preventDefault();
    if (input.value.trim() === "") return;
    if (form.requestSubmit) form.requestSubmit(); else form.submit();
  });

  form.addEventListener("submit", function (e) {
    if (sending || input.value.trim() === "") {
      e.preventDefault();
      return;
    }
    sending = true;
    send.disabled = true;
    root.classList.add("is-sending");
  });

  /* Si se vuelve con el botón "atrás" del navegador, restablecemos el estado. */
  window.addEventListener("pageshow", function (e) {
    if (e.persisted) {
      sending = false;
      root.classList.remove("is-sending");
      crecer();
    }
  });

  /* ---- Menú "+" ---- */
  function cerrar() {
    if (!pop || pop.hidden) return;
    pop.hidden = true;
    plus.setAttribute("aria-expanded", "false");
    plus.classList.remove("is-open");
  }

  if (plus && pop) {
    plus.addEventListener("click", function (e) {
      e.stopPropagation();
      var abrir = pop.hidden;
      pop.hidden = !abrir;
      plus.setAttribute("aria-expanded", abrir ? "true" : "false");
      plus.classList.toggle("is-open", abrir);
    });
    document.addEventListener("click", function (e) {
      if (!pop.contains(e.target)) cerrar();
    });
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape") cerrar();
    });
  }

  /* En escritorio dejamos el cursor listo; en móvil no abrimos el teclado solo. */
  if (window.matchMedia && window.matchMedia("(hover: hover) and (pointer: fine)").matches) {
    input.focus();
  }
})();
</script>


<?php include "views/partials/footer.php"; ?>
