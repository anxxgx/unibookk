<?php

/*
 * FASE 5 — formulario de publicación (crear y editar).
 * Entradas: $formModo ('crear'|'editar'), $formPub (fila o null),
 * $formValores (valores a mostrar), $formSugerencias (datalist).
 * El servidor vuelve a validar todo: estos atributos son solo comodidad.
 */

$formEditar = ($formModo ?? 'crear') === 'editar';
$fv = is_array($formValores ?? null) ? $formValores : [];

$fvCampo = function ($clave) use ($fv, $comE) {
    return $comE($fv[$clave] ?? '');
};

$fvCategoria = (string)($fv['categoria'] ?? '');
$fvModalidad = (string)($fv['modalidad'] ?? '');
$fvImagen = $formEditar && !empty($formPub['imagen'])
    ? $comunidadController->rutaImagenValida($formPub['imagen'])
    : null;

$fvId = $formEditar ? 'e' . (int)$formPub['id'] : 'n';

?>
<form method="post"
      enctype="multipart/form-data"
      action="index.php?accion=<?= $formEditar ? 'comunidad_editar&amp;id=' . (int)$formPub['id'] : 'comunidad' ?>"
      class="uc-form"
      data-uc-submit
      data-uc-form>

    <input type="hidden" name="com_accion" value="<?= $formEditar ? 'editar_publicacion' : 'publicar' ?>">
    <input type="hidden" name="csrf" value="<?= $comE($comCsrf) ?>">
    <?php if ($formEditar) { ?>
        <input type="hidden" name="id" value="<?= (int)$formPub['id'] ?>">
    <?php } else { ?>
        <input type="hidden" name="nonce" value="<?= $comE($comNonce) ?>">
    <?php } ?>
    <input type="hidden" name="MAX_FILE_SIZE" value="4194304">

    <div class="uc-form__grid">

        <fieldset class="uc-field uc-field--full uc-fieldset" data-uc-categoria>
            <legend>1 · ¿Qué quieres publicar?</legend>
            <div class="uc-radios">
                <?php foreach ($comCategorias as $clave => $cat) { ?>
                    <label class="uc-radio">
                        <input type="radio" name="categoria" value="<?= $comE($clave) ?>"<?= $fvCategoria === $clave ? ' checked' : '' ?> required>
                        <span><i class="bi <?= $comE($cat['icono']) ?>" aria-hidden="true"></i><?= $comE($comCatCorta[$clave] ?? $cat['etiqueta']) ?></span>
                    </label>
                <?php } ?>
            </div>
        </fieldset>

        <div class="uc-field uc-field--full">
            <label for="uc-titulo-<?= $fvId ?>">2 · Título</label>
            <input class="form-control" type="text" id="uc-titulo-<?= $fvId ?>" name="titulo"
                   value="<?= $fvCampo('titulo') ?>" minlength="5" maxlength="150" required
                   placeholder="Ej.: Busco Cálculo de Stewart, 8.ª edición">
        </div>

        <div class="uc-field uc-field--full">
            <label for="uc-contenido-<?= $fvId ?>">3 · Cuéntanos más</label>
            <textarea class="form-control" id="uc-contenido-<?= $fvId ?>" name="contenido"
                      rows="4" minlength="10" maxlength="3000" required data-uc-autosize data-uc-count="3000"
                      placeholder="Describe lo que necesitas, tu pregunta o por qué recomiendas el libro."><?= $fvCampo('contenido') ?></textarea>
            <small class="uc-count" data-uc-counter></small>
        </div>

        <p class="uc-form__sep uc-field--full">Detalles opcionales: ayudan a que te respondan más rápido</p>

        <div class="uc-field" data-uc-libro hidden>
            <label for="uc-lt-<?= $fvId ?>">Título del libro</label>
            <input class="form-control" type="text" id="uc-lt-<?= $fvId ?>" name="libro_titulo"
                   value="<?= $fvCampo('libro_titulo') ?>" maxlength="150" placeholder="Opcional">
        </div>

        <div class="uc-field" data-uc-libro hidden>
            <label for="uc-la-<?= $fvId ?>">Autor</label>
            <input class="form-control" type="text" id="uc-la-<?= $fvId ?>" name="libro_autor"
                   value="<?= $fvCampo('libro_autor') ?>" maxlength="100" placeholder="Opcional">
        </div>

        <div class="uc-field uc-field--full" data-uc-modalidad hidden>
            <label for="uc-mod-<?= $fvId ?>">Modalidad preferida</label>
            <select class="form-select" id="uc-mod-<?= $fvId ?>" name="modalidad">
                <option value="">Sin preferencia</option>
                <?php foreach ($comModalidades as $clave => $etiqueta) { ?>
                    <option value="<?= $comE($clave) ?>"<?= $fvModalidad === $clave ? ' selected' : '' ?>><?= $comE($etiqueta) ?></option>
                <?php } ?>
            </select>
        </div>

        <div class="uc-field">
            <label for="uc-asig-<?= $fvId ?>">Asignatura <span>(opcional)</span></label>
            <input class="form-control" type="text" id="uc-asig-<?= $fvId ?>" name="asignatura"
                   value="<?= $fvCampo('asignatura') ?>" maxlength="120" list="uc-lista-asig"
                   placeholder="Ej.: Cálculo diferencial">
        </div>

        <div class="uc-field">
            <label for="uc-carr-<?= $fvId ?>">Carrera <span>(opcional)</span></label>
            <input class="form-control" type="text" id="uc-carr-<?= $fvId ?>" name="carrera"
                   value="<?= $fvCampo('carrera') ?>" maxlength="120" list="uc-lista-carr"
                   placeholder="Ej.: Ingeniería de sistemas">
        </div>

        <div class="uc-field uc-field--full">
            <label for="uc-img-<?= $fvId ?>">Imagen <span>(opcional · JPG, PNG o WebP · máx. 4 MB)</span></label>
            <input class="form-control" type="file" id="uc-img-<?= $fvId ?>" name="imagen"
                   accept="image/jpeg,image/png,image/webp" data-uc-image>
            <div class="uc-preview" data-uc-preview hidden>
                <img src="" alt="Vista previa de la imagen seleccionada">
            </div>
            <small class="uc-error" data-uc-image-error role="alert"></small>

            <?php if ($fvImagen) { ?>
                <div class="uc-current">
                    <img src="<?= $comE($fvImagen) ?>" alt="Imagen actual de la publicación" loading="lazy">
                    <label class="uc-check">
                        <input type="checkbox" name="quitar_imagen" value="1">
                        <span>Quitar la imagen actual</span>
                    </label>
                </div>
            <?php } ?>
        </div>

    </div>

    <?php $sg = is_array($formSugerencias ?? null) ? $formSugerencias : ['asignaturas' => [], 'carreras' => []]; ?>
    <datalist id="uc-lista-asig">
        <?php foreach ($sg['asignaturas'] as $v) { ?><option value="<?= $comE($v) ?>"><?php } ?>
    </datalist>
    <datalist id="uc-lista-carr">
        <?php foreach ($sg['carreras'] as $v) { ?><option value="<?= $comE($v) ?>"><?php } ?>
    </datalist>

    <div class="uc-form__actions">
        <?php if ($formEditar) { ?>
            <a class="uc-btn uc-btn--ghost" href="index.php?accion=comunidad_publicacion&amp;id=<?= (int)$formPub['id'] ?>">Cancelar</a>
        <?php } ?>
        <button type="submit" class="ub-btn-navy" data-uc-busy="<?= $formEditar ? 'Guardando…' : 'Publicando…' ?>">
            <i class="bi <?= $formEditar ? 'bi-check2' : 'bi-send' ?>" aria-hidden="true"></i>
            <?= $formEditar ? 'Guardar cambios' : 'Publicar' ?>
        </button>
    </div>

</form>
