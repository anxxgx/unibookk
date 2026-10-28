/* ==========================================================================
   UNIBOOK — FASE 5: Comunidad (solo comportamiento de presentación)
   --------------------------------------------------------------------------
   Contadores de caracteres, vista previa y validación básica de imagen,
   campos del libro según la categoría, autoajuste de los cuadros de texto,
   envío con Ctrl/Cmd + Enter, bloqueo del botón al enviar (evita dobles
   clics) y desplazamiento al final de la conversación.
   El servidor vuelve a validar TODO: nada de esto sustituye esas reglas.
   ========================================================================== */
(function () {
  "use strict";

  var MAX_IMG = 4 * 1024 * 1024;
  var TIPOS = ["image/jpeg", "image/png", "image/webp"];

  function qsa(sel, ctx) {
    return Array.prototype.slice.call((ctx || document).querySelectorAll(sel));
  }

  /* ---------- Cuadros de texto que crecen ---------- */
  function autosize(el) {
    el.style.height = "auto";
    el.style.height = Math.min(el.scrollHeight + 2, 320) + "px";
  }

  qsa("[data-uc-autosize]").forEach(function (el) {
    el.addEventListener("input", function () { autosize(el); });
    if (el.offsetParent !== null) autosize(el);
  });

  /* Al abrir un <details> sus textareas ya son visibles: se reajustan. */
  qsa("details").forEach(function (d) {
    d.addEventListener("toggle", function () {
      if (d.open) qsa("[data-uc-autosize]", d).forEach(autosize);
    });
  });

  /* ---------- Contador de caracteres ---------- */
  qsa("[data-uc-count]").forEach(function (el) {
    var max = parseInt(el.getAttribute("data-uc-count"), 10) || 0;
    var cont = el.parentNode.querySelector("[data-uc-counter]");
    if (!cont || !max) return;

    function pintar() {
      var n = el.value.length;
      cont.textContent = n + " / " + max;
      cont.classList.toggle("is-near", n > max * 0.9);
    }

    el.addEventListener("input", pintar);
    pintar();
  });

  /* ---------- Ctrl/Cmd + Enter para enviar ---------- */
  qsa("[data-uc-submit-key]").forEach(function (el) {
    el.addEventListener("keydown", function (e) {
      if (e.key === "Enter" && (e.ctrlKey || e.metaKey) && el.form) {
        e.preventDefault();
        if (el.form.checkValidity()) {
          if (el.form.requestSubmit) el.form.requestSubmit();
          else el.form.submit();
        } else if (el.form.reportValidity) {
          el.form.reportValidity();
        }
      }
    });
  });

  /* ---------- Campos del libro según la categoría (opciones de radio) ---------- */
  qsa("[data-uc-categoria]").forEach(function (grupo) {
    var form = grupo.closest("form");
    if (!form) return;

    function aplicar() {
      var marcada = grupo.querySelector("input[type=radio]:checked");
      var v = marcada ? marcada.value : "";
      var conLibro = v === "busco_libro" || v === "recomendacion";
      qsa("[data-uc-libro]", form).forEach(function (n) { n.hidden = !conLibro; });
      qsa("[data-uc-modalidad]", form).forEach(function (n) { n.hidden = v !== "busco_libro"; });
    }

    grupo.addEventListener("change", aplicar);
    aplicar();
  });

  /* ---------- Imagen: validación y vista previa ---------- */
  qsa("[data-uc-image]").forEach(function (input) {
    var campo = input.closest(".uc-field");
    var prev = campo && campo.querySelector("[data-uc-preview]");
    var img = prev && prev.querySelector("img");
    var err = campo && campo.querySelector("[data-uc-image-error]");

    input.addEventListener("change", function () {
      if (err) err.textContent = "";
      if (prev) prev.hidden = true;

      var f = input.files && input.files[0];
      if (!f) return;

      if (TIPOS.indexOf(f.type) === -1) {
        if (err) err.textContent = "Formato no permitido. Usa JPG, PNG o WebP.";
        input.value = "";
        return;
      }

      if (f.size > MAX_IMG) {
        if (err) err.textContent = "La imagen supera los 4 MB.";
        input.value = "";
        return;
      }

      if (img && window.URL && URL.createObjectURL) {
        if (img.dataset.url) URL.revokeObjectURL(img.dataset.url);
        img.dataset.url = URL.createObjectURL(f);
        img.src = img.dataset.url;
        prev.hidden = false;
      }
    });
  });

  /* ---------- Evitar envíos duplicados ---------- */
  qsa("form[data-uc-submit]").forEach(function (form) {
    form.addEventListener("submit", function (e) {
      if (form.dataset.enviando === "1") {
        e.preventDefault();
        return;
      }

      /* Si el navegador bloquea el envío por validación, no hacemos nada. */
      if (!form.checkValidity()) return;

      form.dataset.enviando = "1";

      /* Se aplaza para no quitar datos del envío en curso. */
      setTimeout(function () {
        qsa("button[type=submit]", form).forEach(function (b) {
          b.disabled = true;
          b.setAttribute("aria-busy", "true");
          var txt = b.getAttribute("data-uc-busy");
          if (txt) {
            b.setAttribute("data-uc-label", b.innerHTML);
            b.textContent = txt;
          }
        });
      }, 0);
    });
  });

  /* Si el usuario vuelve con "atrás", se restablecen los botones. */
  window.addEventListener("pageshow", function (e) {
    if (!e.persisted) return;
    qsa("form[data-uc-submit]").forEach(function (form) {
      delete form.dataset.enviando;
      qsa("button[type=submit]", form).forEach(function (b) {
        b.disabled = false;
        b.removeAttribute("aria-busy");
        var txt = b.getAttribute("data-uc-label");
        if (txt !== null) b.innerHTML = txt;
      });
    });
  });

  /* ---------- Compositor: abrir si llegan con #nueva ---------- */
  var comp = document.querySelector("[data-uc-composer]");
  if (comp && location.hash === "#nueva") comp.open = true;

  /* ---------- «Crear publicación»: abre y enfoca el formulario ---------- */
  function abrirCompositor(foco) {
    var d = document.querySelector("[data-uc-composer]");
    if (!d) return;
    d.open = true;
    qsa("[data-uc-autosize]", d).forEach(autosize);
    d.scrollIntoView({ behavior: "smooth", block: "start" });
    if (foco) {
      var primero = d.querySelector("select, input, textarea");
      if (primero) setTimeout(function () { primero.focus({ preventScroll: true }); }, 250);
    }
  }

  qsa("[data-uc-open]").forEach(function (a) {
    a.addEventListener("click", function (e) {
      if (!document.querySelector("[data-uc-composer]")) return;
      e.preventDefault();
      abrirCompositor(true);
      if (history.replaceState) history.replaceState(null, "", "#nueva");
    });
  });

  window.addEventListener("hashchange", function () {
    if (location.hash === "#nueva") abrirCompositor(false);
  });

  /* ---------- «Comentar»: lleva al cuadro de comentario y lo enfoca ---------- */
  qsa("[data-uc-focus]").forEach(function (a) {
    a.addEventListener("click", function (e) {
      var campo = document.getElementById(a.getAttribute("data-uc-focus"));
      if (!campo) return;
      e.preventDefault();
      campo.scrollIntoView({ behavior: "smooth", block: "center" });
      setTimeout(function () { campo.focus({ preventScroll: true }); }, 300);
    });
  });

  /* ---------- Menú «Filtros»: se cierra al pulsar fuera o con Esc ---------- */
  var pops = qsa("[data-uc-pop]");
  if (pops.length) {
    document.addEventListener("click", function (e) {
      pops.forEach(function (d) { if (d.open && !d.contains(e.target)) d.open = false; });
    });
    document.addEventListener("keydown", function (e) {
      if (e.key !== "Escape") return;
      pops.forEach(function (d) {
        if (d.open) { d.open = false; var s = d.querySelector("summary"); if (s) s.focus(); }
      });
    });
  }

  /* ---------- Avisos: se pueden cerrar y quedan a la vista ---------- */
  qsa(".uc-alert").forEach(function (al) {
    var b = document.createElement("button");
    b.type = "button";
    b.className = "uc-alert__close";
    b.setAttribute("aria-label", "Cerrar aviso");
    b.innerHTML = '<i class="bi bi-x-lg" aria-hidden="true"></i>';
    b.addEventListener("click", function () { al.remove(); });
    al.appendChild(b);

    if (al.className.indexOf("uc-alert--ok") !== -1) {
      setTimeout(function () {
        if (al.parentNode) {
          al.style.transition = "opacity .4s ease";
          al.style.opacity = "0";
          setTimeout(function () { if (al.parentNode) al.remove(); }, 450);
        }
      }, 7000);
    }
  });

  /* ---------- Conversación: ir al último mensaje ---------- */
  var chat = document.querySelector("[data-uc-chat]");
  if (chat) {
    chat.scrollTop = chat.scrollHeight;
    var caja = document.querySelector(".uc-chat__form textarea");
    if (caja && window.matchMedia && window.matchMedia("(min-width: 761px)").matches) {
      caja.focus({ preventScroll: true });
    }
  }
})();
