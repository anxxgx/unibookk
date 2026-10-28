// ==========================================================================
// UNIBOOK — interacciones globales
// ==========================================================================

document.addEventListener("DOMContentLoaded", function () {

  // --- Navbar: sombra al hacer scroll ---
  const nav = document.querySelector(".ub-nav");
  if (nav) {
    const onScroll = function () {
      if (window.scrollY > 8) nav.classList.add("is-scrolled");
      else nav.classList.remove("is-scrolled");
    };
    onScroll();
    window.addEventListener("scroll", onScroll, { passive: true });
  }

  // --- Reveal on scroll ---
  const revealEls = document.querySelectorAll(".reveal");
  if (revealEls.length) {
    const io = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add("is-visible");
            io.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.15 }
    );
    revealEls.forEach(function (el) { io.observe(el); });
  }

  // --- Estante: pequeña inclinación siguiendo el mouse (efecto de profundidad) ---
  const shelf = document.querySelector(".shelf");
  if (shelf) {
    const spines = shelf.querySelectorAll(".spine");
    shelf.addEventListener("mousemove", function (e) {
      const rect = shelf.getBoundingClientRect();
      const x = (e.clientX - rect.left) / rect.width; // 0..1
      spines.forEach(function (spine, i) {
        const center = (i + 0.5) / spines.length;
        const dist = x - center;
        spine.style.transform = "translateY(" + (dist * -10) + "px) rotateZ(" + (dist * 4) + "deg)";
      });
    });
    shelf.addEventListener("mouseleave", function () {
      spines.forEach(function (spine) { spine.style.transform = ""; });
    });
  }

  // --- Buscador de catálogo (si existe) ---
  const buscador = document.getElementById("buscador");
  if (buscador) {

    const contador = document.getElementById("catalogoContador");
    const vacio = document.getElementById("catalogoVacio");
    const totalLibrosCatalogo = document.querySelectorAll(".libro-card").length;

    buscador.addEventListener("keyup", function () {
      const texto = this.value.toLowerCase();
      let visibles = 0;

      document.querySelectorAll(".libro-card").forEach(function (libro) {
        const t = libro.dataset.titulo || "";
        const a = libro.dataset.autor || "";
        const m = libro.dataset.materia || "";
        const match = t.includes(texto) || a.includes(texto) || m.includes(texto);
        libro.style.display = match ? "" : "none";
        if (match) visibles++;
      });

      // --- Contador de resultados (solo presentación, no toca el filtro) ---
      if (contador) {
        contador.innerHTML = texto
          ? "Mostrando <b>" + visibles + "</b> de " + totalLibrosCatalogo + " libros"
          : "Mostrando <b>" + totalLibrosCatalogo + "</b> " + (totalLibrosCatalogo === 1 ? "libro" : "libros");
      }

      // --- Estado vacío (solo presentación, no toca el filtro) ---
      if (vacio) {
        vacio.style.display = visibles === 0 ? "" : "none";
      }
    });
  }

});

function confirmarEliminacion() {
  return confirm(
    "¿Estás segura de que deseas eliminar esta publicación?\n\nEsta acción no se puede deshacer."
  );
}
