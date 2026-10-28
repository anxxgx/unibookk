/**
 * UNIBOOK — LANDING PAGE INTERACTIONS
 * Ubicación sugerida: /public/js/landing.js  (o /assets/js/landing.js)
 * No depende de tu lógica PHP. Solo interactúa con el DOM de landing.php.
 */
document.addEventListener('DOMContentLoaded', () => {
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ------------------------------------------------------------
     1. AOS — scroll reveal
  ------------------------------------------------------------ */
  if (window.AOS) {
    AOS.init({
      duration: 700,
      easing: 'ease-out-cubic',
      once: true,
      offset: 60,
      disable: prefersReducedMotion,
    });
  }

  /* ------------------------------------------------------------
     2. Navbar: fondo con blur al hacer scroll
  ------------------------------------------------------------ */
  const nav = document.getElementById('ubNav');
  const onScrollNav = () => {
    if (window.scrollY > 24) nav.classList.add('is-scrolled');
    else nav.classList.remove('is-scrolled');
  };
  onScrollNav();
  window.addEventListener('scroll', onScrollNav, { passive: true });

  /* ------------------------------------------------------------
     3. Hero: entrada con GSAP
  ------------------------------------------------------------ */
  if (window.gsap && !prefersReducedMotion) {
    gsap.timeline({ defaults: { ease: 'power3.out' } })
      .from('.ub-eyebrow', { opacity: 0, y: 16, duration: 0.6 })
      .from('.ub-hero__title', { opacity: 0, y: 28, duration: 0.8 }, '-=0.35')
      .from('.ub-hero__subtitle', { opacity: 0, y: 20, duration: 0.7 }, '-=0.5')
      .from('.ub-hero__cta .ub-btn', { opacity: 0, y: 16, duration: 0.6, stagger: 0.1 }, '-=0.45')
      .from('.ub-hero__trust', { opacity: 0, y: 12, duration: 0.6 }, '-=0.4')
      .from('.ub-book-card', { opacity: 0, y: 40, scale: 0.9, duration: 0.9, stagger: 0.12 }, '-=0.9');
  }

  /* ------------------------------------------------------------
     4. Hero: parallax 3D de la pila de libros según el mouse
  ------------------------------------------------------------ */
  const stack = document.querySelector('[data-hero-stack]');
  if (stack && !prefersReducedMotion && window.matchMedia('(pointer:fine)').matches) {
    const cards = stack.querySelectorAll('.ub-book-card');

    stack.addEventListener('mousemove', (e) => {
      const rect = stack.getBoundingClientRect();
      const px = (e.clientX - rect.left) / rect.width - 0.5;  // -0.5 .. 0.5
      const py = (e.clientY - rect.top) / rect.height - 0.5;

      cards.forEach((card) => {
        const depth = parseFloat(card.dataset.depth) || 20;
        const baseRotation = getComputedStyle(card).getPropertyValue('--base-rot') || '0deg';
        const rotateY = px * depth;
        const rotateX = -py * depth * 0.6;
        const translateZ = 20;
        card.style.transform =
          `translate3d(${px * depth * 0.5}px, ${py * depth * 0.5}px, ${translateZ}px) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
      });
    });

    stack.addEventListener('mouseleave', () => {
      cards.forEach((card) => { card.style.transform = ''; });
    });
  }

  /* ------------------------------------------------------------
     5. Contadores animados (Intersection Observer)
  ------------------------------------------------------------ */
  const counters = document.querySelectorAll('[data-counter]');
  const animateCounter = (el) => {
    const target = parseInt(el.dataset.target, 10) || 0;
    const duration = 1400;
    const start = performance.now();

    const step = (now) => {
      const progress = Math.min((now - start) / duration, 1);
      const eased = 1 - Math.pow(1 - progress, 3); // ease-out-cubic
      el.textContent = Math.floor(eased * target).toLocaleString('es-CO');
      if (progress < 1) requestAnimationFrame(step);
      else el.textContent = target.toLocaleString('es-CO');
    };
    requestAnimationFrame(step);
  };

  if (counters.length) {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          animateCounter(entry.target);
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.4 });

    counters.forEach((counter) => observer.observe(counter));
  }

  /* ------------------------------------------------------------
     6. Ripple effect en botones
  ------------------------------------------------------------ */
  document.querySelectorAll('.ub-ripple').forEach((btn) => {
    btn.addEventListener('click', function (e) {
      const rect = this.getBoundingClientRect();
      const circle = document.createElement('span');
      const size = Math.max(rect.width, rect.height);

      circle.className = 'ub-ripple-circle';
      circle.style.width = circle.style.height = `${size}px`;
      circle.style.left = `${e.clientX - rect.left - size / 2}px`;
      circle.style.top = `${e.clientY - rect.top - size / 2}px`;

      this.appendChild(circle);
      circle.addEventListener('animationend', () => circle.remove());
    });
  });

  /* ------------------------------------------------------------
     7. Hover 3D sutil para las tarjetas de beneficios
  ------------------------------------------------------------ */
  document.querySelectorAll('.ub-benefit-card').forEach((card) => {
    if (prefersReducedMotion || !window.matchMedia('(pointer:fine)').matches) return;

    card.addEventListener('mousemove', (e) => {
      const rect = card.getBoundingClientRect();
      const px = (e.clientX - rect.left) / rect.width - 0.5;
      const py = (e.clientY - rect.top) / rect.height - 0.5;
      card.style.transform = `perspective(800px) rotateX(${-py * 6}deg) rotateY(${px * 6}deg) translateY(-4px)`;
    });
    card.addEventListener('mouseleave', () => { card.style.transform = ''; });
  });
});