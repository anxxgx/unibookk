<?php
/**
 * ============================================================
 * UNIBOOK MEDELLÍN — LANDING PAGE (VISTA)
 * ============================================================
 * Ubicación sugerida dentro de tu MVC:
 *   /views/landing.php   (o /app/Views/home/index.php, según tu router)
 *
 * Esta vista es 100% independiente de tu lógica PHP/MySQL actual.
 * Solo reemplaza el contenido de tu vista de landing por este archivo.
 * Si usas un header/footer PHP separado (include 'partials/header.php'),
 * copia el <head> y el <body> de aquí dentro de esos parciales.
 *
 * Variables PHP dinámicas (opcional, ya vienen con valores por defecto
 * para que la vista funcione aunque no las definas todavía):
 *   $totalLibros, $totalUniversidades, $totalUsuarios, $totalIntercambios
 * ============================================================
 */

$totalLibros         = $totalLibros         ?? 4820;
$totalUniversidades  = $totalUniversidades  ?? 18;
$totalUsuarios       = $totalUsuarios       ?? 12400;
$totalIntercambios   = $totalIntercambios   ?? 3150;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UniBook Medellín — Estudia más, paga menos.</title>
<meta name="description" content="Compra, vende e intercambia libros universitarios en Medellín.">

<!-- Google Fonts: Space Grotesk (display) + Inter (body) + JetBrains Mono (data/eyebrows) -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">

<!-- AOS (scroll reveal) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">

<!-- Bootstrap 5 grid utilities (mantenido para compatibilidad con el resto del sitio) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<!-- Estilos propios de la landing -->
<link rel="stylesheet" href="assets/css/landing.css">

<!-- Rediseño visual "Glass" -->
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/landing-glass.css">
<style>
  .ub-icon{display:inline-block;margin-left:.38em;font-size:1.18em;line-height:1;vertical-align:-.08em;animation:ub-icon-float 2.6s ease-in-out infinite;transition:transform .22s ease,color .22s ease}
  a:has(.ub-icon),button:has(.ub-icon){display:inline-flex;align-items:center;flex-direction:row-reverse}
  .ub-btn:hover .ub-icon,.ub-btn:focus-visible .ub-icon{animation:none;transform:translateY(-3px) scale(1.18) rotate(-7deg)}
  @keyframes ub-icon-float{0%,100%{transform:translateY(0)}50%{transform:translateY(-2px)}}
  @media (prefers-reduced-motion:reduce){.ub-icon{animation:none;transition:none}}
</style>
</head>
<body>

<!-- ============================================================
     NAVBAR
============================================================ -->
<header class="ub-nav" id="ubNav">
  <div class="ub-nav__inner">
    <a href="index.php?accion=landing" class="ub-nav__brand">
      <span class="ub-nav__mark">UB</span>
      <span class="ub-nav__wordmark">UniBook</span>
    </a>

    <nav class="ub-nav__links">
      <a href="index.php?accion=catalogo">Catálogo</a>
      <a href="#como-funciona">Cómo funciona</a>
      <a href="#beneficios">Beneficios</a>
      <a href="#estadisticas">Comunidad</a>
    </nav>

    <div class="ub-nav__actions">
      <a href="index.php?accion=login" class="ub-btn ub-btn--ghost">Ingresar</a>
      <a href="index.php?accion=registro" class="ub-btn ub-btn--gold">Crear cuenta</a>
    </div>
  </div>
</header>

<!-- ============================================================
     HERO
============================================================ -->
<section class="ub-hero">
  <div class="ub-hero__glow" aria-hidden="true"></div>
  <div class="ub-hero__grid" aria-hidden="true"></div>

  <div class="ub-hero__inner">
    <div class="ub-hero__copy" data-hero-copy>
      <span class="ub-eyebrow">Marketplace universitario · Medellín</span>

      <h1 class="ub-hero__title">
        Estudia más,<br>
        <span class="ub-hero__title-gold">paga menos.</span>
      </h1>

      <p class="ub-hero__subtitle">
        Compra, vende, e intercambia libros universitarios
        con estudiantes de tu propia carrera y semestre.
      </p>

      <div class="ub-hero__cta">
        <a href="index.php?accion=catalogo" class="ub-btn ub-btn--gold ub-btn--lg ub-ripple">
          Explorar catálogo
        </a>
        <a href="index.php?accion=publicar_libro" class="ub-btn ub-btn--outline ub-btn--lg ub-ripple">
          Vender un libro
        </a>
      </div>

      <div class="ub-hero__trust">
        <div class="ub-hero__trust-item">
          <strong><?= number_format($totalUniversidades) ?>+</strong>
          <span>Universidades</span>
        </div>
        <div class="ub-hero__trust-divider"></div>
        <div class="ub-hero__trust-item">
          <strong><?= number_format($totalLibros) ?>+</strong>
          <span>Libros activos</span>
        </div>
      </div>
    </div>

    <!-- SIGNATURE ELEMENT: pila de libros en abanico, con parallax 3D al mover el mouse -->
    <div class="ub-hero__stack" data-hero-stack aria-hidden="true">
      <div class="ub-book-card ub-book-card--1" data-depth="18">
        <div class="ub-book-card__spine"></div>
        <span class="ub-book-card__tag">Cálculo III</span>
        <span class="ub-book-card__price">$45.000</span>
      </div>
      <div class="ub-book-card ub-book-card--2" data-depth="30">
        <div class="ub-book-card__spine"></div>
        <span class="ub-book-card__tag">Física Mecánica</span>
        <span class="ub-book-card__price">$38.000</span>
      </div>
      <div class="ub-book-card ub-book-card--3" data-depth="42">
        <div class="ub-book-card__spine"></div>
        <span class="ub-book-card__tag">Bases de Datos</span>
        <span class="ub-book-card__price">Intercambio</span>
      </div>
    </div>
  </div>

  <div class="ub-hero__scroll-cue" aria-hidden="true">
    <span></span>
  </div>
</section>

<!-- ============================================================
     ESTADÍSTICAS (contadores animados)
============================================================ -->
<section class="ub-stats" id="estadisticas">
  <div class="ub-container">
    <div class="ub-stats__grid">

      <div class="ub-stat" data-aos="fade-up" data-aos-delay="0">
        <span class="ub-stat__number" data-counter data-target="<?= (int)$totalLibros ?>">0</span>
        <span class="ub-stat__label">Libros publicados</span>
      </div>

      <div class="ub-stat" data-aos="fade-up" data-aos-delay="80">
        <span class="ub-stat__number" data-counter data-target="<?= (int)$totalUniversidades ?>">0</span>
        <span class="ub-stat__label">Universidades activas</span>
      </div>

      <div class="ub-stat" data-aos="fade-up" data-aos-delay="160">
        <span class="ub-stat__number" data-counter data-target="<?= (int)$totalUsuarios ?>">0</span>
        <span class="ub-stat__label">Estudiantes registrados</span>
      </div>

      <div class="ub-stat" data-aos="fade-up" data-aos-delay="240">
        <span class="ub-stat__number" data-counter data-target="<?= (int)$totalIntercambios ?>">0</span>
        <span class="ub-stat__label">Intercambios realizados</span>
      </div>

    </div>
  </div>
</section>

<!-- ============================================================
     CÓMO FUNCIONA
============================================================ -->
<section class="ub-how" id="como-funciona">
  <div class="ub-container">
    <div class="ub-section-head" data-aos="fade-up">
      <span class="ub-eyebrow">El proceso</span>
      <h2>Tres pasos entre tú y tu próximo semestre</h2>
    </div>

    <div class="ub-how__grid">
      <div class="ub-how__step" data-aos="fade-up" data-aos-delay="0">
        <span class="ub-how__index">01</span>
        <h3>Busca</h3>
        <p>Filtra por universidad, carrera, semestre y materia hasta encontrar exactamente el libro que necesitas.</p>
      </div>

      <div class="ub-how__connector" aria-hidden="true"></div>

      <div class="ub-how__step" data-aos="fade-up" data-aos-delay="120">
        <span class="ub-how__index">02</span>
        <h3>Conecta</h3>
        <p>Escribe directamente al estudiante que lo publicó y acuerda el punto de encuentro o el envío.</p>
      </div>

      <div class="ub-how__connector" aria-hidden="true"></div>

      <div class="ub-how__step" data-aos="fade-up" data-aos-delay="240">
        <span class="ub-how__index">03</span>
        <h3>Aprende</h3>
        <p>Compra o intercambia. Ahorra dinero y entra a clase con el material correcto desde el primer día.</p>
      </div>
    </div>
  </div>
</section>

<!-- ============================================================
     BENEFICIOS
============================================================ -->
<section class="ub-benefits" id="beneficios">
  <div class="ub-container">
    <div class="ub-section-head" data-aos="fade-up">
      <span class="ub-eyebrow">Por qué UniBook</span>
      <h2>Diseñado para el bolsillo y el calendario de un estudiante</h2>
    </div>

    <div class="ub-benefits__grid">

      <div class="ub-benefit-card" data-aos="fade-up" data-aos-delay="0">
        <div class="ub-benefit-card__icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none"><path d="M12 2v20M17 5.5c0-1.93-2.24-3.5-5-3.5s-5 1.57-5 3.5 2.24 3.5 5 3.5 5 1.57 5 3.5-2.24 3.5-5 3.5-5-1.57-5-3.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
        </div>
        <h3>Ahorro real</h3>
        <p>Precios hasta 70% más bajos que en librerías, definidos por la misma comunidad estudiantil.</p>
      </div>

      <div class="ub-benefit-card" data-aos="fade-up" data-aos-delay="80">
        <div class="ub-benefit-card__icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none"><path d="M17 20a4 4 0 00-10 0M12 12a4 4 0 100-8 4 4 0 000 8zM21 20a4 4 0 00-3-3.87M17 4.13A4 4 0 0119 8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
        </div>
        <h3>Comunidad estudiantil</h3>
        <p>Cada libro fue subrayado, usado y aprobado por alguien que cursó exactamente tu misma materia.</p>
      </div>

      <div class="ub-benefit-card" data-aos="fade-up" data-aos-delay="160">
        <div class="ub-benefit-card__icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none"><path d="M12 2l8 4v6c0 5-3.5 8.5-8 10-4.5-1.5-8-5-8-10V6l8-4z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
        </div>
        <h3>Intercambio seguro</h3>
        <p>Gestiona solicitudes de intercambio y conversa con otros estudiantes dentro de la plataforma.</p>
      </div>

      <div class="ub-benefit-card" data-aos="fade-up" data-aos-delay="240">
        <div class="ub-benefit-card__icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none"><path d="M13 2L4 14h6l-1 8 9-12h-6l1-8z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
        </div>
        <h3>Acceso rápido</h3>
        <p>Publica en menos de un minuto y encuentra tu próximo libro con búsqueda y filtros instantáneos.</p>
      </div>

    </div>
  </div>
</section>

<!-- ============================================================
     CTA FINAL
============================================================ -->
<section class="ub-final-cta" data-aos="zoom-in">
  <div class="ub-container ub-final-cta__inner">
    <h2>Tu próximo semestre puede costar menos.</h2>
    <p>Únete a la comunidad de estudiantes que ya está ahorrando en material universitario.</p>
    <a href="index.php?accion=registro" class="ub-btn ub-btn--gold ub-btn--lg ub-ripple">Crear cuenta gratis</a>
  </div>
</section>

<!-- ============================================================
     FOOTER
============================================================ -->
<footer class="ub-footer">
  <div class="ub-container ub-footer__inner">
    <div class="ub-footer__brand">
      <span class="ub-nav__mark">UB</span>
      <span class="ub-nav__wordmark">UniBook</span>
    </div>
    <p>&copy; <?= date('Y') ?> UniBook Medellín. Hecho por y para estudiantes.</p>
  </div>
</footer>

<!-- Scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script src="assets/js/landing.js"></script>
</body>
</html>
