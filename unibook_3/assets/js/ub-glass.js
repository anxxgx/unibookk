/* ==========================================================================
   UNIBOOK — comportamiento de presentación del rediseño "Glass"
   --------------------------------------------------------------------------
   Solo toca la interfaz: menú móvil, modo claro/oscuro, atajo de búsqueda,
   sincronía de la búsqueda superior con el buscador del catálogo y favoritos
   guardados en este navegador. No envía ni lee datos del servidor.
   ========================================================================== */
(function () {
  "use strict";

  var root = document.documentElement;
  var body = document.body;

  function store(op, key, value) {
    try {
      if (op === "get") return window.localStorage.getItem(key);
      window.localStorage.setItem(key, value);
    } catch (e) { return null; }
    return null;
  }

  /* ---------- Modo claro / oscuro ---------- */
  function paintThemeButtons(dark) {
    document.querySelectorAll("[data-ub-theme]").forEach(function (btn) {
      btn.setAttribute("aria-pressed", dark ? "true" : "false");
      var icon = btn.querySelector("i");
      if (icon) {
        icon.classList.toggle("bi-moon", !dark);
        icon.classList.toggle("bi-sun", dark);
      }
    });
  }

  function setTheme(dark) {
    if (dark) root.setAttribute("data-theme", "dark");
    else root.removeAttribute("data-theme");
    paintThemeButtons(dark);
  }

  /* ---------- Favoritos (solo en este navegador) ---------- */
  function readFavs() {
    try { return JSON.parse(store("get", "ub-favoritos") || "[]"); } catch (e) { return []; }
  }

  document.addEventListener("DOMContentLoaded", function () {

    /* Tema guardado */
    paintThemeButtons(root.getAttribute("data-theme") === "dark");
    document.querySelectorAll("[data-ub-theme]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        var dark = root.getAttribute("data-theme") !== "dark";
        setTheme(dark);
        store("set", "ub-theme", dark ? "dark" : "light");
      });
    });

    /* Menú móvil */
    function closeMenu() { body.classList.remove("ub-nav-open"); }
    document.querySelectorAll("[data-ub-menu]").forEach(function (btn) {
      btn.addEventListener("click", function () { body.classList.toggle("ub-nav-open"); });
    });
    document.querySelectorAll("[data-ub-scrim]").forEach(function (el) {
      el.addEventListener("click", closeMenu);
    });
    document.querySelectorAll(".ub-sidenav a").forEach(function (a) {
      a.addEventListener("click", closeMenu);
    });

    /* Menú de usuario: cerrar al hacer clic fuera o con Escape */
    var userMenu = document.querySelector(".ub-usermenu");
    document.addEventListener("click", function (e) {
      if (userMenu && userMenu.open && !userMenu.contains(e.target)) userMenu.open = false;
    });
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape") {
        if (userMenu) userMenu.open = false;
        closeMenu();
      }
    });

    /* Búsqueda superior: atajo ⌘K / Ctrl+K y sincronía con el catálogo */
    var topSearch = document.querySelector("[data-ub-search]");
    var catalogSearch = document.getElementById("buscador");

    document.addEventListener("keydown", function (e) {
      if ((e.metaKey || e.ctrlKey) && String(e.key).toLowerCase() === "k" && topSearch) {
        e.preventDefault();
        topSearch.focus();
        topSearch.select();
      }
    });

    function fire(el) {
      /* main.js filtra el catálogo al escuchar "keyup". */
      el.dispatchEvent(new Event("keyup", { bubbles: true }));
    }

    if (topSearch && catalogSearch) {
      var form = topSearch.form;
      if (form) form.addEventListener("submit", function (e) { e.preventDefault(); });

      topSearch.addEventListener("input", function () {
        catalogSearch.value = topSearch.value;
        fire(catalogSearch);
      });
      catalogSearch.addEventListener("input", function () {
        topSearch.value = catalogSearch.value;
      });

      var inicial = topSearch.value || new URLSearchParams(window.location.search).get("q") || "";
      if (inicial && !catalogSearch.value) {
        catalogSearch.value = inicial;
        topSearch.value = inicial;
        fire(catalogSearch);
      }
    }

    /* Favoritos */
    var favs = readFavs();
    document.querySelectorAll("[data-ub-fav]").forEach(function (btn) {
      var id = String(btn.getAttribute("data-ub-fav"));
      var icon = btn.querySelector("i");
      function paint() {
        var on = favs.indexOf(id) !== -1;
        btn.setAttribute("aria-pressed", on ? "true" : "false");
        if (icon) {
          icon.classList.toggle("bi-heart", !on);
          icon.classList.toggle("bi-heart-fill", on);
        }
      }
      paint();
      btn.addEventListener("click", function () {
        var i = favs.indexOf(id);
        if (i === -1) favs.push(id); else favs.splice(i, 1);
        store("set", "ub-favoritos", JSON.stringify(favs));
        paint();
      });
    });
  });
})();
