<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['id_usuario'])) {
    include "views/partials/navbar.php";
} else {
?>
    <nav class="navbar ub-nav">
        <div class="container justify-content-between">
            <a class="navbar-brand" href="index.php">
                <span class="mark"><i class="bi bi-bookshelf ub-icon" aria-hidden="true"></i></span> UniBook
            </a>

            <a
                href="index.php?accion=login"
                class="btn-ub btn-ub-outline on-dark btn-ub-sm">
                Iniciar sesión
            </a>
        </div>
    </nav>
<?php
}

?>

<?php
$libro = $libro ?? null;
$vendedor = $vendedor ?? null;
?>
<div class="ub-section">

    <div class="container">

        <?php if (!$libro) { ?>

            <!-- LIBRO NO ENCONTRADO -->

            <div
                class="catalog-empty reveal"
                style="max-width:520px; margin:0 auto;">

                <div class="catalog-empty__icon">
                    <i class="bi bi-book ub-icon" aria-hidden="true"></i>
                </div>

                <h5 class="fw-bold mb-1">
                    No encontramos ese libro
                </h5>

                <p class="text-muted mb-4">
                    Puede que ya haya sido eliminado o el enlace sea incorrecto.
                </p>

                <a
                    href="index.php?accion=catalogo"
                    class="btn-ub btn-ub-dark">
                    ← Volver al catálogo
                </a>

            </div>

        <?php } else { ?>

            <?php

            /*
            ============================================================
            DATOS DEL LIBRO
            ============================================================
            */

            $tipo = strtolower(
                trim($libro['tipo_publicacion'] ?? '')
            );

            $disponibilidad = strtolower(
                trim(
                    $libro['estado_disponibilidad']
                        ?? 'disponible'
                )
            );

            $esPropio =
                isset($_SESSION['id_usuario']) &&
                (int)$_SESSION['id_usuario'] ===
                (int)$libro['usuario_id'];


            /*
            ============================================================
            CLASE DEL TIPO DE PUBLICACIÓN
            ============================================================
            */

            if ($tipo === 'venta') {

                $stampClass = 'stamp-venta';

            } elseif ($tipo === 'intercambio') {

                $stampClass = 'stamp-intercambio';

            } else {

                $stampClass = 'stamp-venta';

            }

            $tagClass = str_replace(
                'stamp-',
                'tag-',
                $stampClass
            );


            /*
            ============================================================
            CLASE DE DISPONIBILIDAD
            ============================================================
            */

            $dispoClases = [

                'disponible' =>
                'badge-disp-disponible',

                'reservado' =>
                'badge-disp-reservado',

                'vendido' =>
                'badge-disp-vendido',

                'intercambiado' =>
                'badge-disp-intercambiado'

            ];

            $dispoClass =
                $dispoClases[$disponibilidad]
                ?? 'badge-disp-disponible';

            $dispoLabel =
                ucfirst($disponibilidad);


            /*
            ============================================================
            DATOS DE PRESENTACIÓN (solo visual)
            ============================================================
            */

            /* Ruta real del catálogo; conserva la búsqueda si llegó en la URL. */
            $volverCatalogo = 'index.php?accion=catalogo';

            $busquedaVolver = trim((string)($_GET['q'] ?? ''));

            if ($busquedaVolver !== '') {

                $volverCatalogo .=
                    '&q=' . rawurlencode($busquedaVolver);

            }

            /* Muestra "—" cuando un dato académico viene vacío. */
            $ubDato = function ($valor) {

                $valor = trim((string)$valor);

                return $valor !== ''
                    ? htmlspecialchars($valor)
                    : '—';

            };

            $descripcionLibro =
                trim((string)($libro['descripcion'] ?? ''));

            ?>


            <!-- VOLVER -->

            <a
                href="<?php echo htmlspecialchars($volverCatalogo); ?>"
                class="book-detail__back reveal">
                <span class="book-detail__back-icon" aria-hidden="true">
                    <i class="bi bi-arrow-left"></i>
                </span>
                <span>Volver al catálogo</span>
            </a>


            <!-- MENSAJE DE COMPRA -->

            <?php if (isset($_GET['compra'])) { ?>

                <?php

                $mensajesCompra = [

                    'propio' =>
                    'No puedes comprar tu propio libro.',

                    'no_disponible' =>
                    'Este libro ya no está disponible — alguien más lo reservó.',

                    'tipo_no_venta' =>
                    'Este libro no está publicado como Venta.',

                    'invalido' =>
                    'Solicitud de compra inválida.',

                    'error' =>
                    'No se pudo procesar tu compra. Intenta de nuevo.'

                ];

                $motivoCompra =
                    $_GET['compra'];

                ?>

                <?php if (
                    isset($mensajesCompra[$motivoCompra])
                ) { ?>

                    <div
                        class="ub-alert ub-alert-danger reveal"
                        style="max-width:600px;">

                        <i class="bi bi-exclamation-triangle ub-icon" aria-hidden="true"></i>

                        <?php
                        echo htmlspecialchars(
                            $mensajesCompra[$motivoCompra]
                        );
                        ?>

                    </div>

                <?php } ?>

            <?php } ?>


            <!-- MENSAJE DE INTERCAMBIO -->

            <?php if (isset($_GET['intercambio'])) { ?>

                <?php

                $mensajesIntercambio = [

                    'error' =>
                    'No se pudo enviar la solicitud de intercambio. Verifica que el libro siga disponible.',

                    'invalido' =>
                    'La solicitud de intercambio no es válida.'

                ];

                $motivoIntercambio =
                    $_GET['intercambio'];

                ?>

                <?php if (
                    isset(
                        $mensajesIntercambio[$motivoIntercambio]
                    )
                ) { ?>

                    <div
                        class="ub-alert ub-alert-danger reveal"
                        style="max-width:650px;">

                        <i class="bi bi-exclamation-triangle ub-icon" aria-hidden="true"></i>

                        <?php
                        echo htmlspecialchars(
                            $mensajesIntercambio[$motivoIntercambio]
                        );
                        ?>

                    </div>

                <?php } ?>

            <?php } ?>


            <!-- ======================================================
                 DETALLE DEL LIBRO
                 ====================================================== -->

            <div class="book-detail">


                <!-- ==================================================
                     PORTADA
                     ================================================== -->

                <div class="book-detail__cover reveal">

                    <span
                        class="tag <?php echo $tagClass; ?> book-detail__tag">

                        <?php
                        echo htmlspecialchars(
                            $libro['tipo_publicacion']
                        );
                        ?>

                    </span>


                    <?php if (!empty($libro['imagen'])) { ?>

                        <img
                            class="cover__bg"
                            src="<?php
                                    echo htmlspecialchars(
                                        $libro['imagen']
                                    );
                                    ?>"
                            alt=""
                            aria-hidden="true">

                        <img
                            class="cover__img"
                            src="<?php
                                    echo htmlspecialchars(
                                        $libro['imagen']
                                    );
                                    ?>"
                            alt="<?php
                                    echo htmlspecialchars(
                                        $libro['titulo']
                                    );
                                    ?>">

                    <?php } else { ?>

                        <div class="no-image">
                            <i class="bi bi-book" aria-hidden="true"></i>
                            <span>Sin portada</span>
                        </div>

                    <?php } ?>

                </div>


                <!-- ==================================================
                     INFORMACIÓN
                     ================================================== -->

                <div class="book-detail__info">


                    <!-- ENCABEZADO -->

                    <div class="book-detail__header reveal reveal-d1">


                        <?php if ($esPropio) { ?>

                            <span
                                class="stamp stamp-own mb-2 d-inline-block">
                                Tu publicación
                            </span>

                        <?php } ?>


                        <h1 class="book-detail__title">

                            <?php
                            echo htmlspecialchars(
                                $libro['titulo']
                            );
                            ?>

                        </h1>


                        <p class="book-detail__author">

                            <i class="bi bi-person ub-icon" aria-hidden="true"></i>

                            <?php
                            echo htmlspecialchars(
                                $libro['autor']
                            );
                            ?>


                            <?php if (
                                !empty($libro['editorial'])
                            ) { ?>

                                &nbsp;·&nbsp;

                                <?php
                                echo htmlspecialchars(
                                    $libro['editorial']
                                );
                                ?>

                            <?php } ?>

                        </p>


                        <!-- PRECIO Y ESTADOS -->

                        <div class="book-detail__price-row">

                            <span class="price book-detail__price">

                                $

                                <?php
                                echo number_format(
                                    $libro['precio']
                                );
                                ?>

                            </span>


                            <span class="stamp stamp-neutral">

                                Estado:

                                <?php
                                echo htmlspecialchars(
                                    $libro['estado']
                                );
                                ?>

                            </span>


                            <span
                                class="stamp <?php echo $dispoClass; ?>">

                                <?php
                                echo htmlspecialchars(
                                    $dispoLabel
                                );
                                ?>

                            </span>

                        </div>


                        <!-- ==================================================
                             ACCIONES
                             ================================================== -->

                        <div class="book-detail__actions">


                            <?php if (
                                !isset($_SESSION['id_usuario'])
                            ) { ?>


                                <!-- USUARIO NO AUTENTICADO -->


                                <?php if ($tipo === 'venta') { ?>

                                    <a
                                        href="index.php?accion=login"
                                        class="btn-ub btn-ub-dark btn-ub-block">
                                        Inicia sesión para comprar
                                    </a>

                                <?php } ?>


                                <?php if ($tipo === 'intercambio') { ?>

                                    <a
                                        href="index.php?accion=login"
                                        class="btn-ub btn-ub-outline btn-ub-block">
                                        <i class="bi bi-arrow-left-right ub-icon" aria-hidden="true"></i> Inicia sesión para solicitar intercambio
                                    </a>

                                <?php } ?>


                            <?php } elseif ($esPropio) { ?>


                                <!-- ==================================================
                                     LIBRO PROPIO
                                     ================================================== -->


                                <?php if ($tipo === 'venta') { ?>

                                    <p
                                        class="text-muted mb-0"
                                        style="font-size:14px;">
                                        <i class="bi bi-pin-angle ub-icon" aria-hidden="true"></i> Esta es tu publicación.
                                    </p>

                                <?php } elseif (
                                    $tipo === 'intercambio'
                                ) { ?>

                                    <p
                                        class="text-muted mb-0"
                                        style="font-size:14px;">
                                        <i class="bi bi-pin-angle ub-icon" aria-hidden="true"></i> Esta es tu publicación de intercambio.
                                    </p>

                                <?php } ?>


                            <?php } else { ?>


                                <!-- ==================================================
                                     COMPRA
                                     ================================================== -->


                                <?php if (
                                    $tipo === 'venta' &&
                                    $disponibilidad === 'disponible'
                                ) { ?>

                                    <a
                                        href="index.php?accion=checkout&id=<?php
                                                                            echo (int)$libro['id'];
                                                                            ?>"
                                        class="btn-ub btn-ub-gold btn-ub-block">
                                        <i class="bi bi-cart-plus ub-icon" aria-hidden="true"></i> Comprar libro
                                    </a>

                                <?php } elseif (
                                    $tipo === 'venta'
                                ) { ?>

                                    <p
                                        class="text-muted mb-0"
                                        style="font-size:14px;">
                                        Este libro ya no está disponible para compra.
                                    </p>

                                <?php } ?>


                                <!-- ==================================================
                                     INTERCAMBIO
                                     ================================================== -->


                                <?php if (
                                    $tipo === 'intercambio' &&
                                    $disponibilidad === 'disponible'
                                ) { ?>

                                    <a
                                        href="index.php?accion=solicitar_intercambio&id=<?php
                                                                                        echo (int)$libro['id'];
                                                                                        ?>"
                                        class="btn-ub btn-ub-outline btn-ub-block">
                                        <i class="bi bi-arrow-left-right ub-icon" aria-hidden="true"></i> Solicitar intercambio
                                    </a>

                                    <p
                                        class="text-muted mt-2 mb-0"
                                        style="font-size:13px;">
                                        Podrás elegir uno de tus libros
                                        para ofrecer a cambio.
                                    </p>

                                <?php } elseif (
                                    $tipo === 'intercambio'
                                ) { ?>

                                    <p
                                        class="text-muted mb-0"
                                        style="font-size:14px;">
                                        Este libro ya no está disponible
                                        para intercambio.
                                    </p>

                                <?php } ?>


                            <?php } ?>

                        </div>

                    </div>


                    <!-- ==================================================
                         INFORMACIÓN ACADÉMICA
                         ================================================== -->

                    <div
                        class="book-detail__meta reveal reveal-d2">

                        <div class="meta-item">

                            <span class="ub-eyebrow">
                                <i class="bi bi-building" aria-hidden="true"></i>
                                Universidad
                            </span>

                            <p>
                                <?php echo $ubDato($libro['universidad']); ?>
                            </p>

                        </div>


                        <div class="meta-item">

                            <span class="ub-eyebrow">
                                <i class="bi bi-mortarboard" aria-hidden="true"></i>
                                Carrera
                            </span>

                            <p>
                                <?php echo $ubDato($libro['carrera']); ?>
                            </p>

                        </div>


                        <div class="meta-item">

                            <span class="ub-eyebrow">
                                <i class="bi bi-calendar3" aria-hidden="true"></i>
                                Semestre
                            </span>

                            <p>
                                <?php echo $ubDato($libro['semestre']); ?>
                            </p>

                        </div>


                        <div class="meta-item">

                            <span class="ub-eyebrow">
                                <i class="bi bi-journal-bookmark" aria-hidden="true"></i>
                                Materia
                            </span>

                            <p>
                                <?php echo $ubDato($libro['materia']); ?>
                            </p>

                        </div>

                    </div>


                    <!-- ==================================================
                         DESCRIPCIÓN
                         ================================================== -->

                    <?php if ($descripcionLibro !== '') { ?>

                        <div
                            class="book-detail__description reveal reveal-d2">

                            <span class="ub-eyebrow">
                                <i class="bi bi-text-paragraph" aria-hidden="true"></i>
                                Descripción
                            </span>

                            <p>

                                <?php
                                echo nl2br(
                                    htmlspecialchars(
                                        $libro['descripcion']
                                    )
                                );
                                ?>

                            </p>

                        </div>

                    <?php } ?>


                    <!-- ==================================================
                         VENDEDOR / PROPIETARIO
                         ================================================== -->

                    <?php if ($vendedor) { ?>

                        <div
                            class="seller-card reveal reveal-d3">

                            <div class="seller-card__avatar" aria-hidden="true">
                                <?php
                                $nombreVendedor = trim((string)$vendedor['nombre']);

                                echo htmlspecialchars(
                                    function_exists('mb_substr')
                                        ? mb_strtoupper(mb_substr($nombreVendedor, 0, 1, 'UTF-8'), 'UTF-8')
                                        : strtoupper(substr($nombreVendedor, 0, 1))
                                );
                                ?>
                            </div>


                            <div class="seller-card__info">

                                <span class="ub-eyebrow">
                                    Publicado por
                                </span>

                                <h5>

                                    <?php
                                    echo htmlspecialchars(
                                        $vendedor['nombre']
                                            . ' '
                                            . $vendedor['apellido']
                                    );
                                    ?>

                                </h5>

                                <p
                                    class="text-muted mb-0"
                                    style="font-size:13.5px;">
                                    Estudiante verificado de UniBook
                                </p>

                            </div>


                            <?php if (!$esPropio) { ?>

                                <a
                                    href="index.php?accion=chat&id=<?php echo (int)$libro['id']; ?>"
                                    class="btn-ub btn-ub-outline btn-ub-sm seller-card__cta">
                                    <i class="bi bi-chat-dots ub-icon" aria-hidden="true"></i> Contactar
                                </a>

                            <?php } ?>

                        </div>

                    <?php } ?>


                    <!-- ==================================================
                         INFORMACIÓN DEL INTERCAMBIO
                         ================================================== -->

                    <?php if (
                        isset($_SESSION['id_usuario']) &&
                        !$esPropio &&
                        $tipo === 'intercambio' &&
                        $disponibilidad === 'disponible'
                    ) { ?>

                        <div
                            class="reveal reveal-d3 mt-4">

                            <div class="ub-alert book-detail__hint">

                                <strong>
                                    <i class="bi bi-arrow-left-right ub-icon" aria-hidden="true"></i> ¿Te interesa este intercambio?
                                </strong>

                                <p
                                    class="mb-0 mt-1"
                                    style="font-size:14px;">

                                    Pulsa

                                    <strong>
                                        “Solicitar intercambio”
                                    </strong>

                                    para elegir uno de tus libros
                                    y enviárselo al propietario
                                    de esta publicación.

                                </p>

                            </div>

                        </div>

                    <?php } ?>


                </div>

            </div>

        <?php } ?>

    </div>

</div>


<?php include "views/partials/footer.php"; ?>


<style>
    .book-detail__actions {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .book-detail__actions .btn-ub-block {
        width: 100%;
    }
</style>
