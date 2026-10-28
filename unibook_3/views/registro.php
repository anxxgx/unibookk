<?php if(!empty($mensajeRegistro)){ ?>
    <div class="container mt-4"><?php echo $mensajeRegistro; ?></div>
<?php } ?>

<!-- =========================================================
     HERO — el estante interactivo
     ========================================================= -->
<section class="ub-hero">

    <div class="container">

        <div class="row align-items-center g-5">

            <div class="col-lg-6">

                <a class="ub-auth-brand" href="index.php">
                    <img src="assets/img/unibook-logo.svg" alt="" width="38" height="36">
                    <span>UniBook</span>
                </a>


                <div class="hero-inner">

                    <span class="ub-eyebrow">381.45 — UniBook Medellín</span>

                    <h1>Estudia más,<br><em>paga menos.</em></h1>

                    <p class="lead">
                        Compra, vende e intercambia libros universitarios
                        con estudiantes de tu misma carrera y universidad. Sin
                        estafas, sin desorden, sin pagar de más por un semestre.
                    </p>

                    <div class="hero-actions">
                        <a href="#crear-cuenta" class="btn-ub btn-ub-gold">
                            Crear cuenta gratis
                        </a>
                        <a href="index.php?accion=login" class="btn-ub btn-ub-outline on-dark">
                            Ya tengo cuenta
                        </a>
                    </div>

                </div>

            </div>

            <div class="col-lg-6">

                <div class="shelf" aria-hidden="true">
                    <div class="spine">Cálculo</div>
                    <div class="spine">Anatomía</div>
                    <div class="spine">Derecho Civil</div>
                    <div class="spine">UniBook</div>
                    <div class="spine">Economía</div>
                    <div class="spine">Diseño</div>
                </div>

            </div>

        </div>

    </div>

</section>

<!-- =========================================================
     PROPUESTA DE VALOR
     ========================================================= -->
<section class="ub-section">

    <div class="container">

        <div class="row g-4">

            <div class="col-md-4 reveal">
                <div class="stat-card">
                    <div class="num"><i class="bi bi-mortarboard ub-icon" aria-hidden="true"></i></div>
                    <h5 class="mt-2 mb-2 fw-bold">Filtra por carrera</h5>
                    <p class="text-muted mb-0" style="font-size:14.5px;">
                        Encuentra libros exactos para tu universidad, carrera y semestre.
                    </p>
                </div>
            </div>

            <div class="col-md-4 reveal">
                <div class="stat-card">
                    <div class="num"><i class="bi bi-shield-lock ub-icon" aria-hidden="true"></i></div>
                    <h5 class="mt-2 mb-2 fw-bold">Correo institucional</h5>
                    <p class="text-muted mb-0" style="font-size:14.5px;">
                        Solo estudiantes verificados. Más confianza, cero estafas.
                    </p>
                </div>
            </div>

            <div class="col-md-4 reveal">
                <div class="stat-card">
                    <div class="num"><i class="bi bi-box-seam ub-icon" aria-hidden="true"></i></div>
                    <h5 class="mt-2 mb-2 fw-bold">Venta o intercambio</h5>
                    <p class="text-muted mb-0" style="font-size:14.5px;">
                        Elige cómo quieres tus libros: comprarlos o intercambiarlos.
                    </p>
                </div>
            </div>

        </div>

    </div>

</section>

<!-- =========================================================
     FORMULARIO DE REGISTRO
     ========================================================= -->
<section class="ub-section ub-section-alt" id="crear-cuenta">

    <div class="container">

        <div class="row justify-content-center">

            <div class="col-lg-6 reveal">

                <div class="text-center mb-4">
                    <span class="ub-eyebrow" style="justify-content:center;">Únete</span>
                    <h2 class="fw-bold mt-2">Crea tu cuenta</h2>
                    <p class="text-muted">Es gratis y toma menos de un minuto.</p>
                </div>

                <div class="ub-form-card">

                    <form method="POST">

                        <div class="row">

                            <div class="col-md-6">
                                <div class="ub-field">
                                    <label for="nombre">Nombre</label>
                                    <input class="form-control" type="text" id="nombre" name="nombre" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="ub-field">
                                    <label for="apellido">Apellido</label>
                                    <input class="form-control" type="text" id="apellido" name="apellido" required>
                                </div>
                            </div>

                        </div>

                        <div class="ub-field">
                            <label for="correo">Correo institucional</label>
                            <input class="form-control" type="email" id="correo" name="correo" placeholder="tucorreo@udea.edu.co" required>
                        </div>

                        <div class="ub-field">
                            <label for="password">Contraseña</label>
                            <input class="form-control" type="password" id="password" name="password" required>
                        </div>

                        <button name="registrar" class="btn-ub btn-ub-gold btn-ub-block mt-2">
                            Crear cuenta
                        </button>

                    </form>

                    <p class="text-center text-muted mt-4 mb-0" style="font-size:14.5px;">
                        ¿Ya tienes cuenta?
                        <a href="index.php?accion=login" style="color:var(--gold-deep); font-weight:600;">Inicia sesión</a>
                    </p>

                </div>

            </div>

        </div>

    </div>

</section>

<?php include "views/partials/footer.php"; ?>
