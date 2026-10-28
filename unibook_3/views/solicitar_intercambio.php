<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.php?accion=login");
    exit();
}

$librosDisponibles = [];

if ($misLibros) {
    while ($libro = $misLibros->fetch_assoc()) {

        $estadoDisponibilidad =
            $libro['estado_disponibilidad'] ?? 'disponible';

        if ($estadoDisponibilidad === 'disponible') {
            $librosDisponibles[] = $libro;
        }
    }
}

?>

<?php include "views/partials/navbar.php"; ?>

<div class="container py-5">

    <!-- ENCABEZADO -->

    <div class="mb-5">

        <a
            href="index.php?accion=detalle_libro&id=<?php echo (int)$libroSolicitado['id']; ?>"
            class="text-decoration-none text-muted"
        >
            ← Volver al libro
        </a>

        <div class="mt-4">

            <span class="badge bg-primary-subtle text-primary mb-2">
                <i class="bi bi-arrow-left-right ub-icon" aria-hidden="true"></i> INTERCAMBIO
            </span>

            <h1 class="fw-bold">
                Solicitar intercambio
            </h1>

            <p class="text-muted mb-0">
                Elige uno de tus libros para ofrecer a cambio.
            </p>

        </div>

    </div>


    <!-- LIBRO QUE QUIERES -->

    <div class="card border-0 shadow-sm rounded-4 mb-4">

        <div class="card-body p-4">

            <h5 class="fw-bold mb-3">
                <i class="bi bi-book ub-icon" aria-hidden="true"></i> Libro que quieres recibir
            </h5>

            <div class="row align-items-center">

                <?php if (!empty($libroSolicitado['imagen'])): ?>

                    <div class="col-md-2 text-center mb-3 mb-md-0">

                        <img
                            src="<?php echo htmlspecialchars($libroSolicitado['imagen']); ?>"
                            alt="Portada del libro"
                            class="img-fluid rounded-3"
                            style="max-height: 150px; object-fit: cover;"
                        >

                    </div>

                <?php endif; ?>

                <div
                    class="<?php echo !empty($libroSolicitado['imagen']) ? 'col-md-10' : 'col-12'; ?>"
                >

                    <h3 class="fw-bold mb-2">
                        <?php
                        echo htmlspecialchars(
                            $libroSolicitado['titulo']
                        );
                        ?>
                    </h3>

                    <?php if (!empty($libroSolicitado['autor'])): ?>

                        <p class="text-muted mb-2">
                            <i class="bi bi-pencil-square ub-icon" aria-hidden="true"></i>
                            <?php
                            echo htmlspecialchars(
                                $libroSolicitado['autor']
                            );
                            ?>
                        </p>

                    <?php endif; ?>

                    <div class="d-flex flex-wrap gap-2">

                        <?php if (!empty($libroSolicitado['universidad'])): ?>

                            <span class="badge bg-light text-dark">
                                <i class="bi bi-mortarboard ub-icon" aria-hidden="true"></i>
                                <?php
                                echo htmlspecialchars(
                                    $libroSolicitado['universidad']
                                );
                                ?>
                            </span>

                        <?php endif; ?>

                        <?php if (!empty($libroSolicitado['carrera'])): ?>

                            <span class="badge bg-light text-dark">
                                <i class="bi bi-journal-bookmark ub-icon" aria-hidden="true"></i>
                                <?php
                                echo htmlspecialchars(
                                    $libroSolicitado['carrera']
                                );
                                ?>
                            </span>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- LIBRO QUE OFRECES -->

    <div class="card border-0 shadow-sm rounded-4">

        <div class="card-body p-4">

            <h5 class="fw-bold mb-2">
                <i class="bi bi-arrow-left-right ub-icon" aria-hidden="true"></i> Elige el libro que vas a ofrecer
            </h5>

            <p class="text-muted mb-4">
                Solo puedes ofrecer libros que actualmente estén disponibles.
            </p>


            <?php if (count($librosDisponibles) > 0): ?>

                <form
                    action="index.php?accion=solicitar_intercambio&amp;id=<?php echo (int)$libroSolicitado['id']; ?>"
                    method="POST"
                >

                    <input
                        type="hidden"
                        name="crear_intercambio"
                        value="1"
                    >

                    <input
                        type="hidden"
                        name="libro_solicitado_id"
                        value="<?php echo (int)$libroSolicitado['id']; ?>"
                    >


                    <div class="row g-3">

                        <?php foreach ($librosDisponibles as $libro): ?>

                            <div class="col-md-6 col-lg-4">

                                <label
                                    class="w-100 h-100"
                                    style="cursor: pointer;"
                                >

                                    <input
                                        type="radio"
                                        name="libro_ofrecido_id"
                                        value="<?php echo (int)$libro['id']; ?>"
                                        class="d-none libro-intercambio-radio"
                                        required
                                    >

                                    <div
                                        class="card h-100 border shadow-sm rounded-4 libro-intercambio-card"
                                    >

                                        <?php if (!empty($libro['imagen'])): ?>

                                            <div
                                                style="
                                                    height: 190px;
                                                    overflow: hidden;
                                                    border-radius: 1rem 1rem 0 0;
                                                "
                                            >

                                                <img
                                                    src="<?php echo htmlspecialchars($libro['imagen']); ?>"
                                                    alt="Portada del libro"
                                                    style="
                                                        width: 100%;
                                                        height: 100%;
                                                        object-fit: cover;
                                                    "
                                                >

                                            </div>

                                        <?php else: ?>

                                            <div
                                                class="d-flex align-items-center justify-content-center bg-light"
                                                style="
                                                    height: 190px;
                                                    border-radius: 1rem 1rem 0 0;
                                                "
                                            >

                                                <span
                                                    style="font-size: 3rem;"
                                                >
                                                    <i class="bi bi-book ub-icon" aria-hidden="true"></i>
                                                </span>

                                            </div>

                                        <?php endif; ?>


                                        <div class="card-body">

                                            <h5 class="fw-bold">

                                                <?php
                                                echo htmlspecialchars(
                                                    $libro['titulo']
                                                );
                                                ?>

                                            </h5>


                                            <?php if (!empty($libro['autor'])): ?>

                                                <p class="text-muted mb-2">

                                                    <?php
                                                    echo htmlspecialchars(
                                                        $libro['autor']
                                                    );
                                                    ?>

                                                </p>

                                            <?php endif; ?>


                                            <?php if (!empty($libro['universidad'])): ?>

                                                <small class="text-muted">

                                                    <i class="bi bi-mortarboard ub-icon" aria-hidden="true"></i>
                                                    <?php
                                                    echo htmlspecialchars(
                                                        $libro['universidad']
                                                    );
                                                    ?>

                                                </small>

                                            <?php endif; ?>


                                            <div class="mt-3">

                                                <span
                                                    class="badge bg-success-subtle text-success"
                                                >
                                                    Disponible
                                                </span>

                                            </div>

                                        </div>

                                    </div>

                                </label>

                            </div>

                        <?php endforeach; ?>

                    </div>


                    <!-- BOTÓN -->

                    <div class="mt-5 pt-3 border-top">

                        <div class="d-flex flex-column flex-md-row gap-3 justify-content-end">

                            <a
                                href="index.php?accion=detalle_libro&id=<?php echo (int)$libroSolicitado['id']; ?>"
                                class="btn btn-light rounded-pill px-4"
                            >
                                Cancelar
                            </a>

                            <button
                                type="submit"
                                class="btn btn-primary rounded-pill px-4"
                            >
                                <i class="bi bi-arrow-left-right ub-icon" aria-hidden="true"></i> Enviar solicitud de intercambio
                            </button>

                        </div>

                    </div>

                </form>


            <?php else: ?>

                <!-- SIN LIBROS DISPONIBLES -->

                <div class="text-center py-5">

                    <div
                        style="font-size: 4rem;"
                        class="mb-3"
                    >
                        <i class="bi bi-bookshelf ub-icon" aria-hidden="true"></i>
                    </div>

                    <h4 class="fw-bold">
                        No tienes libros disponibles
                    </h4>

                    <p class="text-muted">
                        Para solicitar un intercambio necesitas tener
                        al menos un libro disponible para ofrecer.
                    </p>

                    <a
                        href="index.php?accion=publicar_libro"
                        class="btn btn-primary rounded-pill px-4 mt-2"
                    >
                        + Publicar un libro
                    </a>

                </div>

            <?php endif; ?>

        </div>

    </div>

</div>


<style>

.libro-intercambio-card {
    transition:
        transform 0.2s ease,
        box-shadow 0.2s ease,
        border-color 0.2s ease;
}

.libro-intercambio-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 0.75rem 1.5rem rgba(0, 0, 0, 0.08) !important;
}

.libro-intercambio-radio:checked + .libro-intercambio-card {
    border: 2px solid #0d6efd !important;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.12) !important;
}

</style>

<?php include "views/partials/footer.php"; ?>
