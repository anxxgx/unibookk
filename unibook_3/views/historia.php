<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "views/partials/navbar.php";

$librosGaleria = $historial ?? [];

?>

<section class="state-map">
    <div class="container">
        <header class="state-map__heading">
            <span class="state-map__eyebrow">381.46 — Nuevos ingresos</span>
            <h1>Los últimos libros de <em>UniBook Medellín</em></h1>
            <p>Descubre las publicaciones más recientes mientras recorren la galería.</p>
        </header>

        <div class="state-stream" aria-label="Galería de los últimos libros ingresados">
            <div class="state-stream__glow" aria-hidden="true"></div>
            <div class="state-stream__rail state-stream__rail--left" aria-hidden="true">
                <?php foreach ($librosGaleria as $indice => $libro): ?>
                    <div class="state-stream__ghost state-stream__ghost--<?php echo $indice; ?>" style="animation-delay: -<?php echo $indice * 3.75; ?>s;">
                        <?php $imagen = trim($libro['imagen'] ?? ''); ?>
                        <?php $archivoImagen = dirname(__DIR__) . '/' . ltrim($imagen, '/'); ?>
                        <?php if ($imagen !== '' && is_file($archivoImagen)) { ?>
                            <img class="state-book__image" src="<?php echo htmlspecialchars($imagen); ?>" alt="">
                        <?php } else { ?>
                            <div class="state-book__fallback"><i class="bi bi-book" aria-hidden="true"></i></div>
                        <?php } ?>
                        <span class="state-book__title"><?php echo htmlspecialchars($libro['titulo'] ?? 'Sin título'); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="state-stream__rail state-stream__rail--right" aria-hidden="true">
                <?php foreach ($librosGaleria as $indice => $libro): ?>
                    <div class="state-stream__ghost state-stream__ghost--<?php echo $indice; ?>" style="animation-delay: -<?php echo $indice * 3.75; ?>s;">
                        <?php $imagen = trim($libro['imagen'] ?? ''); ?>
                        <?php $archivoImagen = dirname(__DIR__) . '/' . ltrim($imagen, '/'); ?>
                        <?php if ($imagen !== '' && is_file($archivoImagen)) { ?>
                            <img class="state-book__image" src="<?php echo htmlspecialchars($imagen); ?>" alt="">
                        <?php } else { ?>
                            <div class="state-book__fallback"><i class="bi bi-book" aria-hidden="true"></i></div>
                        <?php } ?>
                        <span class="state-book__title"><?php echo htmlspecialchars($libro['titulo'] ?? 'Sin título'); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

        </div>
    </div>
</section>

<?php include "views/partials/footer.php"; ?>

<style>
    .state-map { overflow:hidden; padding:76px 0 92px; background:linear-gradient(180deg,#f8f5ef 0%,#eef2f7 100%); }
    .state-map__heading { max-width:760px; margin:0 auto 46px; text-align:center; }
    .state-map__eyebrow { color:#7a6540; font:700 11px/1 var(--font-mono,monospace); letter-spacing:.16em; text-transform:uppercase; }
    .state-map__heading h1 { margin:13px 0 0; color:#172033; font-size:clamp(34px,5vw,62px); letter-spacing:-.055em; line-height:.96; }
    .state-map__heading h1 em { color:#9c7b3d; font-style:normal; }
    .state-map__heading p { max-width:590px; margin:19px auto 0; color:#687386; font-size:16px; line-height:1.65; }
    .state-stream { position:relative; isolation:isolate; height:405px; padding:0 20px 45px; perspective:900px; }
    .state-stream__glow { position:absolute; z-index:-1; top:0; left:50%; width:min(58%,640px); aspect-ratio:1; transform:translateX(-50%); background:radial-gradient(circle,rgba(197,161,91,.27),transparent 68%); filter:blur(18px); }
    .state-stream__rail { position:absolute; top:0; right:0; left:0; height:375px; pointer-events:none; transform-style:preserve-3d; }
    .state-stream__ghost { position:absolute; top:48%; left:50%; display:grid; width:clamp(116px,14vw,220px); height:clamp(148px,18vw,280px); place-items:center; border:1px solid rgba(255,255,255,.42); border-radius:18px; background:rgba(255,255,255,.42); box-shadow:0 18px 45px rgba(28,39,57,.1); color:#aa8750; font-size:clamp(28px,4vw,52px); backface-visibility:hidden; animation:state-corridor-right 15s linear infinite; }
    .state-stream__rail--left .state-stream__ghost { animation-name:state-corridor-left; }
    .state-stream__ghost--0 { --card-order:0; }.state-stream__ghost--1 { --card-order:1; }.state-stream__ghost--2 { --card-order:2; }.state-stream__ghost--3 { --card-order:3; }
    .state-book__image,.state-book__fallback { width:100%; height:100%; object-fit:cover; }
    .state-book__fallback { display:grid; place-items:center; background:linear-gradient(135deg,#172033,#5c4a2d); color:#e3c378; }
    .state-book__title { position:absolute; right:0; bottom:0; left:0; padding:28px 12px 12px; background:linear-gradient(transparent,rgba(11,14,20,.86)); color:#fff; font:700 12px/1.25 var(--font-body,sans-serif); text-align:left; }
    @keyframes state-corridor-right { 0%{transform:translate3d(-9vw,0,-480px) scale(.2) rotateY(-14deg);opacity:0} 12%{opacity:.5} 58%{opacity:.38} 100%{transform:translate3d(48vw,0,330px) scale(1.4) rotateY(24deg);opacity:0} }
    @keyframes state-corridor-left { 0%{transform:translate3d(9vw,0,-480px) scale(.2) rotateY(14deg);opacity:0} 12%{opacity:.5} 58%{opacity:.38} 100%{transform:translate3d(-48vw,0,330px) scale(1.4) rotateY(-24deg);opacity:0} }
    @media (max-width:700px) { .state-stream{height:290px;padding:0}.state-stream__rail{height:290px}.state-stream__ghost{width:30vw;height:38vw;min-width:90px;min-height:115px;font-size:30px}.state-map{padding:55px 0 65px} }
    @media (prefers-reduced-motion:reduce) { .state-stream__ghost{animation:none}.state-card,.state-card__icon{transition:none} }
</style>
