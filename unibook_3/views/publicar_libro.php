<?php if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.php?accion=login");
    exit();
} ?>

<?php include "views/partials/navbar.php"; ?>

<div class="ub-section ub-section-alt">
    <div class="container">

        <?php if (!empty($mensajePublicar)) { ?>
            <div class="row justify-content-center">
                <div class="col-lg-8"><?php echo $mensajePublicar; ?></div>
            </div>
        <?php } ?>

        <div class="row justify-content-center">

            <div class="col-lg-8 reveal">

                <div class="text-center mb-4">
                    <span class="ub-eyebrow" style="justify-content:center;">Nueva publicación</span>
                    <h2 class="fw-bold mt-2">Publicar un libro</h2>
                    <p class="text-muted">Compártelo en venta o intercambio con la comunidad.</p>
                </div>

                <div class="ub-form-card">

                    <form method="POST" enctype="multipart/form-data">

                        <div class="row">

                            <div class="col-md-6">
                                <div class="ub-field">
                                    <label for="titulo">Título</label>
                                    <input class="form-control" type="text" id="titulo" name="titulo"
                                        placeholder="Cálculo de una variable" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="ub-field">
                                    <label for="autor">Autor</label>
                                    <input class="form-control" type="text" id="autor" name="autor"
                                        placeholder="James Stewart" required>
                                </div>
                            </div>

                        </div>

                        <div class="row">

                            <div class="col-md-6">
                                <div class="ub-field">
                                    <label for="editorial">Editorial</label>
                                    <input class="form-control" type="text" id="editorial" name="editorial">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="ub-field">
                                    <label for="universidad">Universidad</label>
                                    <input class="form-control" type="text" id="universidad" name="universidad">
                                </div>
                            </div>

                        </div>

                        <div class="row">

                            <div class="col-md-4">
                                <div class="ub-field">
                                    <label for="carrera">Carrera</label>
                                    <input class="form-control" type="text" id="carrera" name="carrera">
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="ub-field">
                                    <label for="semestre">Semestre</label>
                                    <input class="form-control" type="text" id="semestre" name="semestre">
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="ub-field">
                                    <label for="materia">Materia</label>
                                    <input class="form-control" type="text" id="materia" name="materia">
                                </div>
                            </div>

                        </div>

                        <div class="ub-field">
                            <label for="descripcion">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion"
                                placeholder="Estado del libro, ediciones, notas, etc."></textarea>
                        </div>

                        <div class="row">

                            <div class="col-md-4">
                                <div class="ub-field">
                                    <label for="precio">Precio</label>
                                    <input class="form-control" type="number" id="precio" name="precio"
                                        placeholder="120000">
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="ub-field">
                                    <label for="estado">Estado</label>
                                    <select class="form-select" id="estado" name="estado">
                                        <option value="Nuevo">Nuevo</option>
                                        <option value="Usado">Usado</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="ub-field">
                                    <label for="tipo_publicacion">Tipo</label>
                                    <select class="form-select" id="tipo_publicacion" name="tipo_publicacion">
                                        <option>Venta</option>
                                        <option>Intercambio</option>
                                    </select>
                                </div>
                            </div>

                        </div>

                        <div class="ub-field">
                            <label>Imagen del libro</label>
                            <label class="ub-file d-block">
                                <span id="fileLabel"><i class="bi bi-camera ub-icon" aria-hidden="true"></i> Haz clic para subir una foto de la portada</span>
                                <input type="file" name="imagen" accept="image/*" style="display:none;"
                                    onchange="document.getElementById('fileLabel').textContent = this.files[0] ? this.files[0].name : 'Haz clic para subir una foto de la portada';">
                            </label>
                        </div>

                        <button name="publicar_libro" class="btn-ub btn-ub-gold btn-ub-block mt-2">
                            Publicar libro
                        </button>

                    </form>

                </div>

            </div>

        </div>

    </div>
</div>

<?php include "views/partials/footer.php"; ?>
