/* ==========================================================================
   UNIBOOK — Mis compras: buscador y filtros por estado (solo presentación)
   --------------------------------------------------------------------------
   Trabaja sobre las tarjetas que ya imprimió el servidor (data-estado y
   data-texto): no pide nada al servidor ni cambia ningún dato. Si el
   JavaScript no carga, simplemente se ven todas las compras.
   ========================================================================== */
(function () {
  "use strict";

  var root = document.querySelector("[data-uc]");
  if (!root) return;

  var items   = [].slice.call(root.querySelectorAll(".uc-item"));
  var input   = root.querySelector("[data-uc-search]");
  var clear   = root.querySelector("[data-uc-clear]");
  var filters = [].slice.call(root.querySelectorAll("[data-uc-filter]"));
  var counter = document.getElementById("ucContador");
  var none    = document.getElementById("ucSinResultados");
  var list    = document.getElementById("ucLista");
  var reset   = root.querySelector("[data-uc-reset]");

  if (!items.length || !input) return;

  var total  = items.length;
  var estado = "todas";

  /* Minúsculas y sin tildes, para que "calculo" encuentre "Cálculo". */
  function norm(s) {
    s = String(s || "").toLowerCase();
    try { s = s.normalize("NFD").replace(/[̀-ͯ]/g, ""); } catch (e) {}
    return s.trim();
  }

  var textos = items.map(function (li) { return norm(li.getAttribute("data-texto")); });

  function pintarContador(visibles, filtrando) {
    if (!counter) return;
    if (filtrando) {
      counter.innerHTML = "Mostrando <strong>" + visibles + "</strong> de " + total +
        (total === 1 ? " compra" : " compras");
    } else {
      counter.innerHTML = "<strong>" + total + "</strong> " +
        (total === 1 ? "compra realizada" : "compras realizadas");
    }
  }

  function aplicar(animar) {
    var q = norm(input.value);
    var visibles = 0;

    items.forEach(function (li, i) {
      var okEstado = estado === "todas" || li.getAttribute("data-estado") === estado;
      var okTexto  = q === "" || textos[i].indexOf(q) !== -1;
      var ok = okEstado && okTexto;

      li.hidden = !ok;

      if (ok) {
        visibles++;
        if (animar) {
          /* Entrada breve al cambiar de filtro (reinicia la animación). */
          li.classList.remove("uc-pop");
          void li.offsetWidth;
          li.classList.add("uc-pop");
          li.style.setProperty("--p", Math.min(visibles - 1, 8));
        }
      }
    });

    if (list) list.hidden = visibles === 0;
    if (none) none.hidden = visibles !== 0;
    if (clear) clear.hidden = input.value === "";

    pintarContador(visibles, estado !== "todas" || q !== "");
  }

  filters.forEach(function (btn) {
    btn.addEventListener("click", function () {
      estado = btn.getAttribute("data-uc-filter") || "todas";
      filters.forEach(function (b) {
        var on = b === btn;
        b.classList.toggle("is-active", on);
        b.setAttribute("aria-pressed", on ? "true" : "false");
      });
      aplicar(true);
    });
  });

  input.addEventListener("input", function () { aplicar(false); });

  input.addEventListener("keydown", function (e) {
    if (e.key === "Escape" && input.value !== "") {
      input.value = "";
      aplicar(false);
    }
  });

  if (clear) {
    clear.addEventListener("click", function () {
      input.value = "";
      aplicar(false);
      input.focus();
    });
  }

  if (reset) {
    reset.addEventListener("click", function () {
      input.value = "";
      var todas = root.querySelector('[data-uc-filter="todas"]');
      if (todas) todas.click(); else { estado = "todas"; aplicar(true); }
    });
  }

  /* Al salir de la animación se limpia la clase para poder repetirla. */
  items.forEach(function (li) {
    li.addEventListener("animationend", function (e) {
      if (e.animationName === "ucPop") li.classList.remove("uc-pop");
    });
  });

  aplicar(false);
})();
