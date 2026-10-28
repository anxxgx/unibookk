<?php if(session_status() === PHP_SESSION_NONE){ session_start(); } ?>

<?php include "views/partials/navbar.php"; ?>

<div class="ub-section">
<div class="container" style="max-width:640px;">

    <?php if(!$compraConfirmada){ ?>

        <div class="catalog-empty reveal">
            <div class="catalog-empty__icon"><i class="bi bi-receipt ub-icon" aria-hidden="true"></i></div>
            <h5 class="fw-bold mb-1">No encontramos esa compra</h5>
            <p class="text-muted mb-4">Puede que el enlace sea incorrecto o no te pertenezca.</p>
            <a href="index.php?accion=catalogo" class="btn-ub btn-ub-dark">← Volver al catálogo</a>
        </div>

    <?php }else{ ?>

        <?php
        $metodosPago = [
            'pse'          => 'PSE',
            'nequi'        => 'Nequi',
            'bancolombia'  => 'Bancolombia',
            'daviplata'    => 'Daviplata',
        ];
        $metodoLabel = $metodosPago[$compraConfirmada['metodo_pago']] ?? $compraConfirmada['metodo_pago'];
        // La consulta de Compra usa nombre_vendedor/apellido_vendedor.
        // Se conserva compatibilidad con datos de versiones anteriores.
        $vendedorNombre = trim(
            ($compraConfirmada['nombre_vendedor'] ?? $compraConfirmada['vendedor_nombre'] ?? '')
            . ' '
            . ($compraConfirmada['apellido_vendedor'] ?? $compraConfirmada['vendedor_apellido'] ?? '')
        );
        if ($vendedorNombre === '') {
            $vendedorNombre = 'Vendedor no disponible';
        }
        ?>

        <div class="text-center reveal">
            <div class="confirm-icon"><i class="bi bi-check-circle-fill ub-icon" aria-hidden="true"></i></div>
            <h1 class="fw-bold mb-2">¡Compra registrada!</h1>
            <p class="text-muted mb-1">Tu compra fue registrada correctamente.</p>
            <p class="text-muted mb-4" style="font-size:13.5px;">
                <i class="bi bi-exclamation-triangle ub-icon" aria-hidden="true"></i> Este es un proceso simulado — no se realizó ningún cobro real.
            </p>
        </div>

        <div class="checkout-summary reveal mb-4">

            <div class="checkout-summary__thumb">
                <?php if(!empty($compraConfirmada['imagen'])){ ?>
                    <img src="<?php echo $compraConfirmada['imagen']; ?>" alt="">
                <?php }else{ ?>
                    <span><i class="bi bi-book ub-icon" aria-hidden="true"></i></span>
                <?php } ?>
            </div>

            <div class="checkout-summary__info flex-grow-1">
                <h4><?php echo $compraConfirmada['titulo']; ?></h4>
                <p><i class="bi bi-person ub-icon" aria-hidden="true"></i> <?php echo $compraConfirmada['autor']; ?></p>
                <p><i class="bi bi-credit-card ub-icon" aria-hidden="true"></i> Método: <b><?php echo $metodoLabel; ?></b></p>
                <p><i class="bi bi-person-circle ub-icon" aria-hidden="true"></i> Vendedor: <b><?php echo htmlspecialchars($vendedorNombre, ENT_QUOTES, 'UTF-8'); ?></b></p>
            </div>

            <div class="checkout-summary__price">
                $<?php echo number_format($compraConfirmada['precio']); ?>
            </div>

        </div>

        <div class="text-center mb-4 reveal">
            <span class="stamp badge-compra-pendiente">Estado: Pendiente</span>
        </div>

        <p class="text-muted text-center reveal" style="font-size:14.5px;">
            Ahora puedes comunicarte con el vendedor para coordinar la entrega.
        </p>

        <div class="d-flex gap-3 flex-wrap justify-content-center mt-4 reveal">
            <a href="index.php?accion=detalle_libro&id=<?php echo $compraConfirmada['libro_id']; ?>" class="btn-ub btn-ub-gold">Ver el libro</a>
            <a href="index.php?accion=catalogo" class="btn-ub btn-ub-outline">Volver al catálogo</a>
        </div>

    <?php } ?>

</div>
</div>

<?php include "views/partials/footer.php"; ?>
