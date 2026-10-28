<?php if(session_status() === PHP_SESSION_NONE){ session_start(); } ?>

<?php if(isset($_SESSION['id_usuario'])){ ?>
    <?php include "views/partials/navbar.php"; ?>
<?php }else{ ?>
    <nav class="navbar ub-nav">
        <div class="container justify-content-between">
            <a class="navbar-brand" href="index.php"><span class="mark"><i class="bi bi-bookshelf ub-icon" aria-hidden="true"></i></span> UniBook</a>
            <a href="index.php?accion=login" class="btn-ub btn-ub-outline on-dark btn-ub-sm">Iniciar sesión</a>
        </div>
    </nav>
<?php } ?>

<div class="ub-section">
<div class="container" style="max-width:760px;">

    <?php if(!$checkoutInfo['valido']){ ?>

        <?php
        $mensajesCheckout = [
            'sesion'        => 'Debes iniciar sesión para comprar este libro.',
            'invalido'      => 'No encontramos ese libro.',
            'propio'        => 'No puedes comprar tu propio libro.',
            'tipo_no_venta' => 'Este libro no está publicado como Venta.',
            'no_disponible' => 'Este libro ya no está disponible.',
        ];
        $motivo = $checkoutInfo['motivo'] ?? 'invalido';
        $texto = $mensajesCheckout[$motivo] ?? 'No se pudo abrir el checkout.';
        ?>

        <div class="catalog-empty reveal">
            <div class="catalog-empty__icon"><i class="bi bi-cart-x ub-icon" aria-hidden="true"></i></div>
            <h5 class="fw-bold mb-1">No se puede continuar</h5>
            <p class="text-muted mb-4"><?php echo $texto; ?></p>

            <?php if($motivo === 'sesion'){ ?>
                <a href="index.php?accion=login" class="btn-ub btn-ub-dark">Iniciar sesión</a>
            <?php }else{ ?>
                <a href="index.php?accion=catalogo" class="btn-ub btn-ub-dark">← Volver al catálogo</a>
            <?php } ?>
        </div>

    <?php }else{ ?>

        <?php $libro = $checkoutInfo['libro']; $vendedor = $checkoutInfo['vendedor']; ?>

        <div class="text-center mb-4 reveal">
            <span class="ub-eyebrow" style="justify-content:center;">Checkout</span>
            <h1 class="fw-bold mt-2">Confirma tu compra</h1>
            <p class="text-muted">Revisa los datos antes de continuar. Esta compra es simulada.</p>
        </div>

        <!-- Resumen del libro -->
        <div class="checkout-summary reveal">

            <div class="checkout-summary__thumb">
                <?php if(!empty($libro['imagen'])){ ?>
                    <img src="<?php echo $libro['imagen']; ?>" alt="<?php echo htmlspecialchars($libro['titulo']); ?>">
                <?php }else{ ?>
                    <span><i class="bi bi-book ub-icon" aria-hidden="true"></i></span>
                <?php } ?>
            </div>

            <div class="checkout-summary__info flex-grow-1">
                <h4><?php echo $libro['titulo']; ?></h4>
                <p><i class="bi bi-person ub-icon" aria-hidden="true"></i> <?php echo $libro['autor']; ?></p>
                <p><i class="bi bi-building ub-icon" aria-hidden="true"></i> <?php echo $libro['universidad']; ?> · <i class="bi bi-mortarboard ub-icon" aria-hidden="true"></i> <?php echo $libro['carrera']; ?> · Sem. <?php echo $libro['semestre']; ?></p>
                <p>Estado del libro: <b><?php echo $libro['estado']; ?></b></p>
            </div>

            <div class="checkout-summary__price">
                $<?php echo number_format($libro['precio']); ?>
            </div>

        </div>

        <!-- Información del vendedor -->
        <?php if($vendedor){ ?>
            <div class="seller-card reveal mb-4">
                <div class="seller-card__avatar"><i class="bi bi-person ub-icon" aria-hidden="true"></i></div>
                <div class="seller-card__info">
                    <span class="ub-eyebrow">Vendedor</span>
                    <h5><?php echo htmlspecialchars($vendedor['nombre'] . ' ' . $vendedor['apellido']); ?></h5>
                </div>
            </div>
        <?php } ?>

        <!-- Formulario de confirmación -->
        <form method="POST" action="index.php">

            <input type="hidden" name="libro_id" value="<?php echo $libro['id']; ?>">

            <div class="ub-card p-4 reveal mb-4">

                <h5 class="fw-bold mb-1">Método de pago</h5>
                <p class="text-muted mb-3" style="font-size:13.5px;">
                    <i class="bi bi-exclamation-triangle ub-icon" aria-hidden="true"></i> Pago simulado — no se realizará ningún cobro real.
                </p>

                <div class="payment-methods">

                    <label class="payment-method">
                        <input type="radio" name="metodo_pago" value="pse" checked>
                        <span class="payment-method__card">
                            <span class="payment-method__icon"><i class="bi bi-bank ub-icon" aria-hidden="true"></i></span>
                            <span>PSE</span>
                        </span>
                    </label>

                    <label class="payment-method">
                        <input type="radio" name="metodo_pago" value="nequi">
                        <span class="payment-method__card">
                            <span class="payment-method__icon"><i class="bi bi-wallet2 ub-icon" aria-hidden="true"></i></span>
                            <span>Nequi</span>
                        </span>
                    </label>

                    <label class="payment-method">
                        <input type="radio" name="metodo_pago" value="bancolombia">
                        <span class="payment-method__card">
                            <span class="payment-method__icon"><i class="bi bi-credit-card ub-icon" aria-hidden="true"></i></span>
                            <span>Bancolombia</span>
                        </span>
                    </label>

                    <label class="payment-method">
                        <input type="radio" name="metodo_pago" value="daviplata">
                        <span class="payment-method__card">
                            <span class="payment-method__icon"><i class="bi bi-phone ub-icon" aria-hidden="true"></i></span>
                            <span>Daviplata</span>
                        </span>
                    </label>

                </div>

            </div>

            <div class="d-flex gap-3 flex-wrap reveal">
                <a href="index.php?accion=detalle_libro&id=<?php echo $libro['id']; ?>" class="btn-ub btn-ub-outline">
                    Cancelar
                </a>
                <button type="submit" name="confirmar_compra" class="btn-ub btn-ub-gold btn-ub-block flex-grow-1">
                    <i class="bi bi-check-circle ub-icon" aria-hidden="true"></i> Confirmar compra — $<?php echo number_format($libro['precio']); ?>
                </button>
            </div>

        </form>

    <?php } ?>

</div>
</div>

<?php include "views/partials/footer.php"; ?>
