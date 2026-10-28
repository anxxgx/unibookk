/* ==========================================================================
   UNIBOOK — Mi perfil (FASE 4)
   --------------------------------------------------------------------------
   Solo se carga en la vista "perfil". Maneja: ventana de edición, contador
   de la biografía, vista previa de la foto con validación en el navegador e
   indicadores de carga. La validación real se hace SIEMPRE en el servidor.
   ========================================================================== */
(function () {
  "use strict";

  var MAX_BYTES = 4 * 1024 * 1024;
  var TIPOS = ["image/jpeg", "image/png", "image/webp"];

  function $(sel, ctx) { return (ctx || document).querySelector(sel); }
  function $all(sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); }

  document.addEventListener("DOMContentLoaded", function () {

    /* ---------------- Ventana de edición ---------------- */

    var dialog = $("#ubpDialog");

    function openDialog() {
      if (!dialog) { return; }
      if (typeof dialog.showModal === "function") {
        if (!dialog.open) { dialog.showModal(); }
      } else {
        dialog.setAttribute("open", "");
      }
      var first = $("input:not([type=hidden]):not([readonly]), textarea, select", dialog);
      if (first) { first.focus({ preventScroll: true }); }
    }

    function closeDialog() {
      if (!dialog || !dialog.open) { return; }
      var finish = function () {
        dialog.classList.remove("is-closing");
        dialog.close();
      };
      var reduce = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
      if (reduce) { finish(); return; }
      dialog.classList.add("is-closing");
      window.setTimeout(finish, 210);
    }

    if (dialog) {

      $all("[data-ubp-open]").forEach(function (btn) {
        btn.addEventListener("click", openDialog);
      });

      $all("[data-ubp-close]", dialog).forEach(function (btn) {
        btn.addEventListener("click", closeDialog);
      });

      // Esc: se anima el cierre en lugar de cortarlo de golpe.
      dialog.addEventListener("cancel", function (ev) {
        ev.preventDefault();
        closeDialog();
      });

      // Clic sobre el fondo oscuro (fuera del panel).
      dialog.addEventListener("mousedown", function (ev) {
        if (ev.target === dialog) { closeDialog(); }
      });

      if (dialog.getAttribute("data-open-on-load") === "1") { openDialog(); }
    }


    /* ---------------- Contador de la biografía ---------------- */

    var bio = $("#ubpBioInput");
    var counter = $("[data-ubp-count]");

    if (bio && counter) {
      var paint = function () {
        var n = bio.value.length;
        counter.textContent = n;
        counter.parentNode.classList.toggle("is-near", n >= 270);
      };
      bio.addEventListener("input", paint);
      paint();
    }


    /* ---------------- Guardar datos: indicador de carga ---------------- */

    var form = $("#ubpForm");

    if (form) {
      form.addEventListener("submit", function () {
        var btn = $("#ubpSave");
        if (btn && form.checkValidity()) {
          btn.classList.add("is-loading");
          btn.setAttribute("aria-busy", "true");
          $(".ubp-btn-label", btn).textContent = "Guardando…";
        }
      });
    }


    /* ---------------- Foto de perfil ---------------- */

    var pick = $("[data-ubp-pick]");
    var input = $("#ubpFotoInput");
    var bar = $("#ubpPhotoBar");
    var msg = $("#ubpPhotoMsg");
    var save = $("#ubpPhotoSave");
    var cancel = $("#ubpPhotoCancel");
    var photoForm = $("#ubpFotoForm");
    var avatar = $("#ubpAvatar");

    if (!pick || !input || !bar || !avatar) { return; }

    var originalHTML = avatar.innerHTML;
    var originalMsg = msg ? msg.textContent : "";
    var objectUrl = null;

    function releaseUrl() {
      if (objectUrl) { URL.revokeObjectURL(objectUrl); objectUrl = null; }
    }

    function resetPhoto() {
      releaseUrl();
      input.value = "";
      avatar.innerHTML = originalHTML;
      avatar.classList.toggle("is-empty", !$("#ubpAvatarImg"));
      bar.hidden = true;
      if (msg) { msg.textContent = originalMsg; msg.classList.remove("is-error"); }
      if (save) { save.disabled = false; }
      pick.focus();
    }

    function showError(text) {
      bar.hidden = false;
      if (msg) { msg.textContent = text; msg.classList.add("is-error"); }
      if (save) { save.disabled = true; }
    }

    pick.addEventListener("click", function () { input.click(); });

    input.addEventListener("change", function () {

      var file = input.files && input.files[0];

      if (!file) { return; }

      releaseUrl();

      if (TIPOS.indexOf(file.type) === -1) {
        showError("Formato no permitido. Usa una imagen JPG, PNG o WebP.");
        input.value = "";
        return;
      }

      if (file.size > MAX_BYTES) {
        showError("La imagen pesa más de 4 MB. Elige una más ligera.");
        input.value = "";
        return;
      }

      objectUrl = URL.createObjectURL(file);

      var img = new Image();
      img.alt = "Vista previa de tu nueva foto de perfil";
      img.width = 152;
      img.height = 152;
      img.onload = function () {
        avatar.classList.remove("is-empty");
        avatar.innerHTML = "";
        avatar.appendChild(img);
        bar.hidden = false;
        if (msg) { msg.textContent = originalMsg; msg.classList.remove("is-error"); }
        if (save) { save.disabled = false; save.focus(); }
      };
      img.onerror = function () {
        showError("No se pudo leer la imagen. Prueba con otro archivo.");
        input.value = "";
      };
      img.src = objectUrl;
    });

    if (cancel) { cancel.addEventListener("click", resetPhoto); }

    if (photoForm) {
      photoForm.addEventListener("submit", function (ev) {
        if (!input.files || !input.files[0]) { ev.preventDefault(); return; }
        if (save) {
          save.classList.add("is-loading");
          save.setAttribute("aria-busy", "true");
          $(".ubp-btn-label", save).textContent = "Guardando…";
        }
      });
    }

  });

})();
