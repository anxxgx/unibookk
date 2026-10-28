/* ==========================================================================
   UNIBOOK — MIS VENTAS (tablero)
   --------------------------------------------------------------------------
   Sin dependencias ni recursos externos. Hace tres cosas:
     1. Dibuja la gráfica de ingresos como un SVG real (barras con alturas
        proporcionales a los datos, ejes, información emergente).
     2. Muestra las filas restantes de la tabla de operaciones.
     3. Sustituye una portada que no cargue por el estado "Sin portada".
   Los datos llegan desde PHP en <script type="application/json" id="uv-data">.
   ========================================================================== */

(function () {
  "use strict";

  var SVG_NS = "http://www.w3.org/2000/svg";

  /* ------------------------------------------------------------------
     Formato de moneda (COP, sin decimales)
     ------------------------------------------------------------------ */

  var fmtCOP;

  try {
    fmtCOP = new Intl.NumberFormat("es-CO", {
      style: "currency", currency: "COP", maximumFractionDigits: 0
    });
  } catch (e) {
    fmtCOP = null;
  }

  function cop(v) {
    if (fmtCOP) return fmtCOP.format(v).replace(/\u00a0/g, " ");
    return "$ " + Math.round(v).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
  }

  /* Etiquetas cortas del eje Y: $ 50 mil, $ 1,2 M */
  function copCorto(v) {
    if (v === 0) return "$ 0";
    if (v >= 1e6) {
      var m = v / 1e6;
      return "$ " + (m % 1 === 0 ? m : m.toFixed(1).replace(".", ",")) + " M";
    }
    if (v >= 1e3) {
      var k = v / 1e3;
      return "$ " + (k % 1 === 0 ? k : k.toFixed(1).replace(".", ",")) + " mil";
    }
    return "$ " + Math.round(v);
  }

  /* Paso "redondo" para el eje Y (1, 2, 2.5, 5 × 10^n) */
  function pasoBonito(max, divisiones) {
    var bruto = max / divisiones;
    var pot = Math.pow(10, Math.floor(Math.log(bruto) / Math.LN10));
    var f = bruto / pot;
    var n = f <= 1 ? 1 : f <= 2 ? 2 : f <= 2.5 ? 2.5 : f <= 5 ? 5 : 10;
    return n * pot;
  }

  function el(nombre, attrs, padre) {
    var nodo = document.createElementNS(SVG_NS, nombre);
    if (attrs) {
      for (var k in attrs) {
        if (Object.prototype.hasOwnProperty.call(attrs, k)) nodo.setAttribute(k, attrs[k]);
      }
    }
    if (padre) padre.appendChild(nodo);
    return nodo;
  }

  var reducirMovimiento = window.matchMedia &&
    window.matchMedia("(prefers-reduced-motion: reduce)").matches;


  /* ==================================================================
     1. GRÁFICA DE INGRESOS
     ================================================================== */

  var datosEl = document.getElementById("uv-data");
  var host = document.getElementById("uv-chart");
  var datos = null;

  if (datosEl) {
    try { datos = JSON.parse(datosEl.textContent); } catch (e) { datos = null; }
  }

  if (host && datos && datos.puntos && datos.puntos.length) {

    var animado = false;
    var tip = document.createElement("div");
    tip.className = "uv-tip";
    tip.setAttribute("role", "status");
    tip.hidden = true;
    host.appendChild(tip);

    var activo = -1;
    var anchoPrevio = 0;
    var tactil = false;   /* el último puntero fue un toque (no un ratón) */

    function ocultarTip() {
      tip.hidden = true;
      if (activo >= 0) {
        var prev = host.querySelector('[data-i="' + activo + '"]');
        if (prev) prev.classList.remove("is-hot");
      }
      activo = -1;
    }

    function mostrarTip(i, geom) {

      if (activo >= 0 && activo !== i) {
        var prev = host.querySelector('[data-i="' + activo + '"]');
        if (prev) prev.classList.remove("is-hot");
      }

      activo = i;

      var barra = host.querySelector('[data-i="' + i + '"]');
      if (barra) barra.classList.add("is-hot");

      var p = datos.puntos[i];

      var ops = p.ops === 1 ? "1 venta completada"
              : p.ops > 1 ? p.ops + " ventas completadas"
              : "Sin ventas completadas";

      tip.innerHTML = "";

      var t1 = document.createElement("strong");
      t1.textContent = p.detalle;
      var t2 = document.createElement("span");
      t2.className = "uv-tip__amount";
      t2.textContent = cop(p.valor);
      var t3 = document.createElement("span");
      t3.className = "uv-tip__ops";
      t3.textContent = ops;

      tip.appendChild(t1);
      tip.appendChild(t2);
      tip.appendChild(t3);
      tip.hidden = false;

      var tw = tip.offsetWidth;
      var th = tip.offsetHeight;
      var x = geom.cx - tw / 2;
      var y = geom.top - th - 10;

      if (y < 0) y = geom.top + 14;
      x = Math.max(4, Math.min(x, host.clientWidth - tw - 4));

      tip.style.left = Math.round(x) + "px";
      tip.style.top = Math.round(y) + "px";
    }

    function dibujar() {

      var w = host.clientWidth;
      if (w < 120) return;

      ocultarTip();

      var puntos = datos.puntos;
      var n = puntos.length;
      var h = w < 520 ? 250 : 300;

      var m = { t: 16, r: 14, b: 38, l: w < 520 ? 52 : 62 };
      var pw = w - m.l - m.r;
      var ph = h - m.t - m.b;

      var max = 0;
      puntos.forEach(function (p) { if (p.valor > max) max = p.valor; });

      var paso = pasoBonito(max || 1, 4);
      var techo = Math.ceil(max / paso) * paso;
      if (techo <= 0) techo = paso;
      var marcas = Math.round(techo / paso);

      function y(v) { return m.t + ph * (1 - v / techo); }

      var viejo = host.querySelector("svg");
      if (viejo) host.removeChild(viejo);

      var svg = el("svg", {
        "class": "uv-svg", width: w, height: h,
        viewBox: "0 0 " + w + " " + h, role: "img",
        "aria-label": "Barras de ingresos. Total del periodo " +
          cop(puntos.reduce(function (a, p) { return a + p.valor; }, 0))
      });

      /* Degradado de las barras */
      var defs = el("defs", null, svg);
      var grad = el("linearGradient", { id: "uvBarGrad", x1: "0", y1: "0", x2: "0", y2: "1" }, defs);
      el("stop", { offset: "0%", "stop-color": "var(--uv-bar-top)" }, grad);
      el("stop", { offset: "100%", "stop-color": "var(--uv-bar-bottom)" }, grad);

      /* Rejilla y eje Y */
      for (var t = 0; t <= marcas; t++) {
        var val = t * paso;
        var yy = Math.round(y(val)) + 0.5;
        el("line", { x1: m.l, x2: w - m.r, y1: yy, y2: yy,
          "class": t === 0 ? "uv-axis" : "uv-grid-line" }, svg);
        var lab = el("text", { x: m.l - 10, y: yy + 4, "text-anchor": "end", "class": "uv-tick" }, svg);
        lab.textContent = copCorto(val);
      }

      /* Barras */
      var slot = pw / n;
      var bw = Math.max(3, Math.min(slot * 0.62, 46));
      var rx = Math.min(7, bw / 2);

      /* Etiquetas del eje X: sin solaparse, anclando siempre la última */
      var maxLetras = 0;
      puntos.forEach(function (p) { if (p.etiqueta.length > maxLetras) maxLetras = p.etiqueta.length; });
      var ancho = maxLetras * 6.4 + 16;
      var cada = Math.max(1, Math.ceil(ancho / slot));

      var barras = el("g", null, svg);

      puntos.forEach(function (p, i) {

        var cx = m.l + slot * i + slot / 2;
        var alto = (p.valor / techo) * ph;
        var top = y(p.valor);

        /* Zona sensible a hover / toque: toda la columna */
        var hit = el("rect", {
          x: m.l + slot * i, y: m.t, width: slot, height: ph + 2,
          "class": "uv-hit", "data-hit": i
        }, svg);

        if (p.valor > 0) {

          var r = el("rect", {
            x: cx - bw / 2, y: top, width: bw, height: Math.max(alto, 2),
            rx: rx, ry: rx, fill: "url(#uvBarGrad)",
            "class": "uv-bar" + (animado ? "" : " is-grow"),
            "data-i": i, tabindex: "0", role: "img",
            "aria-label": p.detalle + ": " + cop(p.valor) + ", " + p.ops + (p.ops === 1 ? " venta" : " ventas"),
            style: "--k:" + Math.min(i, 40)
          }, barras);

          r.addEventListener("focus", function () { mostrarTip(i, { cx: cx, top: top }); });
          r.addEventListener("blur", ocultarTip);

        } else {
          /* Sin ventas: marca mínima sobre el eje, no una barra inventada */
          el("rect", {
            x: cx - Math.min(bw, 10) / 2, y: y(0) - 2, width: Math.min(bw, 10), height: 2,
            rx: 1, "class": "uv-bar-zero", "data-i": i
          }, barras);
        }

        var mostrar = ((n - 1 - i) % cada === 0);

        if (mostrar) {
          var xl = el("text", { x: cx, y: h - 14, "text-anchor": "middle", "class": "uv-tick uv-tick--x" }, svg);
          xl.textContent = p.etiqueta;
        }

        var geom = { cx: cx, top: p.valor > 0 ? top : y(0) - 4 };

        hit.addEventListener("mouseenter", function () { mostrarTip(i, geom); });
        hit.addEventListener("mouseleave", function () { if (!tactil) ocultarTip(); });
        hit.addEventListener("pointerdown", function (ev) {
          if (ev.pointerType && ev.pointerType !== "mouse") mostrarTip(i, geom);
        });
      });

      host.insertBefore(svg, tip);
      animado = true;
      anchoPrevio = w;
    }

    /* Un toque fuera de la gráfica cierra la información emergente */
    document.addEventListener("pointerdown", function (ev) {
      tactil = !!ev.pointerType && ev.pointerType !== "mouse";
      if (!host.contains(ev.target)) ocultarTip();
    }, true);

    dibujar();

    var pendiente = null;

    function redibujar() {
      if (pendiente) window.clearTimeout(pendiente);
      pendiente = window.setTimeout(function () {
        if (Math.abs(host.clientWidth - anchoPrevio) > 1) dibujar();
      }, 120);
    }

    if (window.ResizeObserver) {
      new ResizeObserver(redibujar).observe(host);
    } else {
      window.addEventListener("resize", redibujar);
    }
  }


  /* ==================================================================
     2. TABLA: MOSTRAR FILAS RESTANTES
     ================================================================== */

  var mas = document.querySelector("[data-uv-more]");

  if (mas) {
    mas.addEventListener("click", function () {
      document.querySelectorAll("[data-uv-extra]").forEach(function (fila) {
        fila.hidden = false;
      });
      mas.setAttribute("aria-expanded", "true");
      mas.hidden = true;
    });
  }


  /* ==================================================================
     3. PORTADAS: respaldo "Sin portada"
     ================================================================== */

  function sinPortada(img) {
    var caja = img.closest(".uv-cover");
    if (!caja) return;
    caja.classList.add("is-empty");
    var aviso = caja.querySelector(".uv-cover__none");
    if (aviso) aviso.hidden = false;
    img.remove();
  }

  document.querySelectorAll(".uv-cover img").forEach(function (img) {
    if (img.complete && img.naturalWidth === 0) {
      sinPortada(img);
    } else {
      img.addEventListener("error", function () { sinPortada(img); }, { once: true });
    }
  });

})();
