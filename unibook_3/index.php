<?php

session_start();

require_once "config/conexion.php";

require_once "controllers/UsuarioController.php";
require_once "controllers/LibroController.php";
require_once "controllers/CompraController.php";
require_once "controllers/NotificacionController.php";
require_once "controllers/IntercambioController.php";
require_once "controllers/MensajeController.php";
require_once "controllers/ComunidadController.php";


$usuarioController =
    new UsuarioController($conexion);

$libroController =
    new LibroController($conexion);

$compraController =
    new CompraController($conexion);

$notificacionController =
    new NotificacionController($conexion);

$intercambioController =
    new IntercambioController($conexion);

$mensajeController =
    new MensajeController($conexion);

$comunidadController =
    new ComunidadController($conexion);


$accion =
    $_GET['accion'] ?? 'landing';

/* La landing contiene su propio documento HTML. */
if ($accion === 'landing') {
    $totalesLanding = [
        'libros' => 0,
        'universidades' => 0,
        'usuarios' => 0,
        'intercambios' => 0,
    ];
    $consultaTotales = $conexion->query(
        "SELECT
            (SELECT COUNT(*) FROM libros WHERE estado_disponibilidad = 'disponible') AS libros,
            (SELECT COUNT(DISTINCT universidad) FROM libros WHERE universidad IS NOT NULL AND universidad <> '') AS universidades,
            (SELECT COUNT(*) FROM usuarios) AS usuarios,
            (SELECT COUNT(*) FROM intercambios WHERE estado = 'aceptado') AS intercambios"
    );
    if ($consultaTotales) {
        $totalesLanding = $consultaTotales->fetch_assoc();
    }
    $totalLibros = (int)$totalesLanding['libros'];
    $totalUniversidades = (int)$totalesLanding['universidades'];
    $totalUsuarios = (int)$totalesLanding['usuarios'];
    $totalIntercambios = (int)$totalesLanding['intercambios'];
    include "views/landing.php";
    exit();
}


// ============================================================
// REGISTRO
// ============================================================

if (isset($_POST['registrar'])) {

    $registroExitoso = $usuarioController->registrar();

    $mensajeRegistro = $registroExitoso ? "
        <div class='container mt-4'>
            <div class='ub-alert ub-alert-success'>
                <i class='bi bi-check-circle-fill ub-icon' aria-hidden='true'></i> Cuenta creada correctamente. Ya puedes iniciar sesión.
            </div>
        </div>
    " : "
        <div class='container mt-4'>
            <div class='ub-alert ub-alert-danger'>
                <i class='bi bi-x-circle-fill ub-icon' aria-hidden='true'></i> No se pudo crear la cuenta. Revisa los datos, usa una contraseña de al menos 8 caracteres y prueba con otro correo.
            </div>
        </div>
    ";
}


// ============================================================
// LOGIN
// ============================================================

if (isset($_POST['login'])) {

    $usuarioController->login();
}


// ============================================================
// PUBLICAR LIBRO
// ============================================================

if (isset($_POST['publicar_libro'])) {

    $publicacionExitosa = $libroController->publicar();

    $mensajePublicar = $publicacionExitosa ? "
        <div class='container mt-4'>
            <div class='ub-alert ub-alert-success'>
                <i class='bi bi-check-circle-fill ub-icon' aria-hidden='true'></i> Libro publicado correctamente.
            </div>
        </div>
    " : "
        <div class='container mt-4'>
            <div class='ub-alert ub-alert-danger'>
                <i class='bi bi-x-circle-fill ub-icon' aria-hidden='true'></i> No se pudo publicar el libro. Verifica los datos y, si adjuntas una imagen, que sea JPG, PNG o WEBP de máximo 5 MB.
            </div>
        </div>
    ";
}


// ============================================================
// ELIMINAR LIBRO
// ============================================================

if (isset($_POST['eliminar_libro'])) {

    $id =
        $_POST['id'] ?? 0;

    $eliminado =
        $libroController->eliminar($id);

    if ($eliminado) {

        header(
            "Location: index.php?accion=perfil&eliminado=1"
        );

        exit();

    } else {

        header(
            "Location: index.php?accion=perfil&error=1"
        );

        exit();
    }
}


// ============================================================
// CONFIRMAR COMPRA
// FASE 3.2
// ============================================================

if (isset($_POST['confirmar_compra'])) {

    $resultado =
        $compraController->confirmar();

    if ($resultado['motivo'] === 'sesion') {

        header(
            "Location: index.php?accion=login"
        );

        exit();
    }

    if ($resultado['ok']) {

        header(
            "Location: index.php?accion=compra_confirmada&id="
            . $resultado['compra_id']
        );

        exit();

    } else {

        header(
            "Location: index.php?accion=detalle_libro&id="
            . $resultado['libro_id']
            . "&compra="
            . $resultado['motivo']
        );

        exit();
    }
}


// ============================================================
// ACEPTAR VENTA
// FASE 3.5
// ============================================================

if (isset($_POST['aceptar_venta'])) {

    $id =
        $_POST['compra_id'] ?? 0;

    $resultado =
        $compraController->aceptarVenta($id);

    if ($resultado['ok']) {

        header(
            "Location: index.php?accion=detalle_compra&id="
            . $resultado['compra_id']
            . "&venta=confirmada"
        );

        exit();

    } else {

        header(
            "Location: index.php?accion=detalle_compra&id="
            . $id
            . "&venta="
            . $resultado['motivo']
        );

        exit();
    }
}


// ============================================================
// RECHAZAR VENTA
// FASE 3.5
// ============================================================

if (isset($_POST['rechazar_venta'])) {

    $id =
        $_POST['compra_id'] ?? 0;

    $resultado =
        $compraController->rechazarVenta($id);

    if ($resultado['ok']) {

        header(
            "Location: index.php?accion=detalle_compra&id="
            . $resultado['compra_id']
            . "&venta=cancelada"
        );

        exit();

    } else {

        header(
            "Location: index.php?accion=detalle_compra&id="
            . $id
            . "&venta="
            . $resultado['motivo']
        );

        exit();
    }
}


// ============================================================
// CREAR SOLICITUD DE INTERCAMBIO
// ============================================================

if (isset($_POST['crear_intercambio'])) {

    $resultado =
        $intercambioController->crearSolicitud();

    if ($resultado) {

        header(
            "Location: index.php?accion=intercambios_enviados&intercambio=creado"
        );

        exit();

    } else {

        $libroSolicitado =
            $_POST['libro_solicitado_id'] ?? 0;

        header(
            "Location: index.php?accion=detalle_libro&id="
            . $libroSolicitado
            . "&intercambio=error"
        );

        exit();
    }
}


// ============================================================
// ACEPTAR INTERCAMBIO
// ============================================================

if (isset($_POST['aceptar_intercambio'])) {

    $resultado =
        $intercambioController->aceptar();

    if ($resultado) {

        header(
            "Location: index.php?accion=intercambios_recibidos&intercambio=aceptado"
        );

        exit();

    } else {

        header(
            "Location: index.php?accion=intercambios_recibidos&intercambio=error"
        );

        exit();
    }
}


// ============================================================
// RECHAZAR INTERCAMBIO
// ============================================================

if (isset($_POST['rechazar_intercambio'])) {

    $resultado =
        $intercambioController->rechazar();

    if ($resultado) {

        header(
            "Location: index.php?accion=intercambios_recibidos&intercambio=rechazado"
        );

        exit();

    } else {

        header(
            "Location: index.php?accion=intercambios_recibidos&intercambio=error"
        );

        exit();
    }
}


// ============================================================
// MARCAR NOTIFICACIÓN COMO LEÍDA
// ============================================================

if (isset($_POST['marcar_notificacion'])) {

    $id =
        (int)($_POST['notificacion_id'] ?? 0);

    $notificacionController->marcarLeida($id);

    $volver =
        $_POST['volver'] ?? 'notificaciones';

    header(
        "Location: index.php?accion="
        . $volver
    );

    exit();
}


// ============================================================
// MARCAR TODAS LAS NOTIFICACIONES COMO LEÍDAS
// ============================================================

if (isset($_POST['marcar_todas_notificaciones'])) {

    $notificacionController->marcarTodasLeidas();

    header(
        "Location: index.php?accion=notificaciones"
    );

    exit();
}


// ============================================================
// ENVIAR MENSAJE
// ============================================================

if (isset($_POST['enviar_mensaje'])) {

    if (!isset($_SESSION['id_usuario'])) {

        header(
            "Location: index.php?accion=login"
        );

        exit();
    }

    $libroId =
        (int)($_POST['libro_id'] ?? 0);

    $destinatarioId =
        (int)($_POST['destinatario_id'] ?? 0);

    $otroUsuarioId =
        (int)($_POST['otro_usuario_id'] ?? 0);

    if ($otroUsuarioId <= 0) {
        $otroUsuarioId = $destinatarioId;
    }

    $mensajeController->enviar();

    /*
     * Volvemos exactamente a la conversación
     * donde estábamos.
     */
    header(
        "Location: index.php?accion=chat&id="
        . $libroId
        . "&usuario="
        . $otroUsuarioId
    );

    exit();
}


// ============================================================
// COMUNIDAD — formularios (FASE 5)
// Todos llevan el campo com_accion. El controlador valida sesión, CSRF,
// permisos y datos, y siempre termina con redirección (PRG).
// ============================================================

if (isset($_POST['com_accion'])) {

    $comunidadController->procesarPost();
}


// ============================================================
// MI PERFIL — foto, datos y biografía (FASE 4)
// Solo atiende formularios enviados a index.php?accion=perfil.
// Valida CSRF y sesión dentro del controlador y redirige (PRG).
// ============================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && $accion === 'perfil'
) {

    $usuarioController->procesarPerfil();
}


// ============================================================
// COMUNIDAD — datos de las páginas accion=comunidad* (FASE 5)
// Se preparan ANTES de imprimir HTML para poder redirigir sin errores.
// ============================================================

$comunidadDatos = [];

if (strpos($accion, 'comunidad') === 0) {

    $comunidadDatos = $comunidadController->preparar($accion);
}

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        UniBook Medellín — Estudia más, paga menos.
    </title>

    <link
        rel="icon"
        href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.72em%22 font-size=%2270%22>U</text></svg>"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >

    <link
        href="assets/css/style.css"
        rel="stylesheet"
    >

    <style>
        .ub-icon {
            display: inline-block;
            margin-left: .38em;
            font-size: 1.18em;
            line-height: 1;
            vertical-align: -.08em;
            animation: ub-icon-float 2.6s ease-in-out infinite;
            transition: transform .22s ease, color .22s ease;
        }
        a:has(.ub-icon), button:has(.ub-icon) {
            display: inline-flex;
            align-items: center;
            flex-direction: row-reverse;
        }
        a:hover .ub-icon, a:focus-visible .ub-icon,
        button:hover .ub-icon, button:focus-visible .ub-icon {
            animation: none;
            transform: translateY(-3px) scale(1.18) rotate(-7deg);
        }
        @keyframes ub-icon-float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-2px); }
        }
        @media (prefers-reduced-motion: reduce) {
            .ub-icon { animation: none; transition: none; }
        }
    </style>

    <!-- Rediseño visual "Glass": tipografías, estilos compartidos y tema -->

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,400;0,500;0,600;0,700;0,800;1,500&family=Playfair+Display:ital,wght@0,600;0,700;1,500&display=swap"
        rel="stylesheet"
    >

    <link
        href="assets/css/ub-glass.css"
        rel="stylesheet"
    >

    <link
        href="assets/css/ub-glass-pages.css"
        rel="stylesheet"
    >

    <?php if (in_array($accion, ['catalogo', 'detalle_libro'], true)) { ?>
    <!-- Acabado premium del catálogo y del detalle de libro (acotado a esas dos vistas) -->
    <link
        href="assets/css/ub-catalogo-detalle.css"
        rel="stylesheet"
    >
    <?php } ?>

    <?php if ($accion === 'perfil') { ?>
    <!-- Diseño premium de Mi perfil (acotado a esta vista) -->
    <link
        href="assets/css/ub-perfil.css"
        rel="stylesheet"
    >
    <?php } ?>

    <?php if (strpos($accion, 'comunidad') === 0) { ?>
    <!-- Comunidad UniBook (acotado a las vistas accion=comunidad*) -->
    <link
        href="assets/css/ub-comunidad.css"
        rel="stylesheet"
    >
    <?php } ?>

    <script>
        try {
            if (localStorage.getItem("ub-theme") === "dark") {
                document.documentElement.setAttribute("data-theme", "dark");
            }
        } catch (e) {}
    </script>

</head>


<body class="ub-glass ub-accion-<?php echo preg_replace('/[^a-z0-9_]/i', '', $accion); ?>">

<?php

switch ($accion) {


    // ========================================================
    // LOGIN
    // ========================================================

    case 'login':

        include "views/login.php";

    break;

    case 'registro':

        include "views/registro.php";

    break;


    // ========================================================
    // DASHBOARD
    // ========================================================

    case 'dashboard':

        include "views/dashboard.php";

    break;


    // ========================================================
    // PUBLICAR LIBRO
    // ========================================================

    case 'publicar_libro':

        include "views/publicar_libro.php";

    break;


    // ========================================================
    // CATÁLOGO
    // ========================================================

    case 'catalogo':

        $datos =
            $libroController->listar();

        include "views/catalogo.php";

    break;


    // ========================================================
    // HISTORIA
    // ========================================================

    case 'historia':

        if (!isset($_SESSION['id_usuario'])) {

            header(
                "Location: index.php?accion=login"
            );

            exit();
        }

        $historial =
            $libroController->ultimosIngresados();

        include "views/historia.php";

    break;


    // ========================================================
    // DETALLE DEL LIBRO
    // ========================================================

    case 'detalle_libro':

        $id =
            $_GET['id'] ?? 0;

        $libro =
            $libroController->detalle($id);

        $vendedor = null;

        if ($libro) {

            $vendedor =
                $usuarioController->obtenerPorId(
                    $libro['usuario_id']
                );
        }

        include "views/detalle_libro.php";

    break;


    // ========================================================
    // PERFIL
    // ========================================================

    case 'perfil':

        if (!isset($_SESSION['id_usuario'])) {

            header(
                "Location: index.php?accion=login"
            );

            exit();
        }

        include "views/perfil.php";

    break;


    // ========================================================
    // CHECKOUT
    // ========================================================

    case 'checkout':

        $idCheckout =
            $_GET['id'] ?? 0;

        $checkoutInfo =
            $compraController->prepararCheckout(
                $idCheckout
            );

        include "views/checkout.php";

    break;


    // ========================================================
    // MIS COMPRAS
    // ========================================================

    case 'mis_compras':

        if (!isset($_SESSION['id_usuario'])) {

            header(
                "Location: index.php?accion=login"
            );

            exit();
        }

        $misCompras =
            $compraController->misCompras();

        include "views/mis_compras.php";

    break;


    // ========================================================
    // MIS VENTAS
    // ========================================================

    case 'mis_ventas':

        if (!isset($_SESSION['id_usuario'])) {

            header(
                "Location: index.php?accion=login"
            );

            exit();
        }

        /*
         * Tablero de ventas: periodo elegido por GET (7, 30, 90 o todo).
         * El controlador valida el valor y usa el vendedor de la sesión.
         */
        $panelVentas =
            $compraController->panelVentas(
                $_GET['periodo'] ?? '30'
            );

        include "views/mis_ventas.php";

    break;


    // ========================================================
    // DETALLE COMPRA
    // ========================================================

    case 'detalle_compra':

        if (!isset($_SESSION['id_usuario'])) {

            header(
                "Location: index.php?accion=login"
            );

            exit();
        }

        $idCompra =
            $_GET['id'] ?? 0;

        $detalleCompra =
            $compraController->confirmacion(
                $idCompra,
                $_SESSION['id_usuario']
            );

        include "views/detalle_compra.php";

    break;


    // ========================================================
    // COMPRA CONFIRMADA
    // ========================================================

    case 'compra_confirmada':

        if (!isset($_SESSION['id_usuario'])) {

            header(
                "Location: index.php?accion=login"
            );

            exit();
        }

        $idCompraConfirmada =
            $_GET['id'] ?? 0;

        $compraConfirmada =
            $compraController->confirmacion(
                $idCompraConfirmada,
                $_SESSION['id_usuario']
            );

        include "views/compra_confirmada.php";

    break;


    // ========================================================
    // SOLICITAR INTERCAMBIO
    // ========================================================

    case 'solicitar_intercambio':

        if (!isset($_SESSION['id_usuario'])) {

            header(
                "Location: index.php?accion=login"
            );

            exit();
        }

        $libroSolicitadoId =
            (int)($_GET['id'] ?? 0);

        $libroSolicitado =
            $libroController->detalle(
                $libroSolicitadoId
            );

        if (!$libroSolicitado) {

            header(
                "Location: index.php?accion=catalogo"
            );

            exit();
        }

        $tipoLibro =
            strtolower(
                trim(
                    $libroSolicitado['tipo_publicacion'] ?? ''
                )
            );

        $estadoLibro =
            strtolower(
                trim(
                    $libroSolicitado['estado_disponibilidad']
                    ?? 'disponible'
                )
            );

        if (
            $tipoLibro !== 'intercambio' ||
            $estadoLibro !== 'disponible' ||
            (int)$libroSolicitado['usuario_id']
                === (int)$_SESSION['id_usuario']
        ) {

            header(
                "Location: index.php?accion=detalle_libro&id="
                . $libroSolicitadoId
            );

            exit();
        }

        $misLibros =
            $intercambioController->obtenerMisLibros(
                $_SESSION['id_usuario']
            );

        include "views/solicitar_intercambio.php";

    break;


    // ========================================================
    // INTERCAMBIOS ENVIADOS
    // ========================================================

    case 'intercambios_enviados':

        if (!isset($_SESSION['id_usuario'])) {

            header(
                "Location: index.php?accion=login"
            );

            exit();
        }

        $intercambiosEnviados =
            $intercambioController->enviados();

        include "views/intercambios_enviados.php";

    break;


    // ========================================================
    // INTERCAMBIOS RECIBIDOS
    // ========================================================

    case 'intercambios_recibidos':

        if (!isset($_SESSION['id_usuario'])) {

            header(
                "Location: index.php?accion=login"
            );

            exit();
        }

        $intercambiosRecibidos =
            $intercambioController->recibidos();

        include "views/intercambios_recibidos.php";

    break;


    // ========================================================
    // NOTIFICACIONES
    // ========================================================

    case 'notificaciones':

        if (!isset($_SESSION['id_usuario'])) {

            header(
                "Location: index.php?accion=login"
            );

            exit();
        }

        $notificaciones =
            $notificacionController->listar();

        $notificacionesNoLeidas =
            $notificacionController->contarNoLeidas();

        include "views/notificaciones.php";

    break;


    // ========================================================
    // CHAT
    // ========================================================

    case 'chat':

        if (!isset($_SESSION['id_usuario'])) {

            header(
                "Location: index.php?accion=login"
            );

            exit();
        }

        $libroChatId =
            (int)($_GET['id'] ?? 0);

        /*
         * Usuario con quien se está conversando.
         * Cuando entramos desde Mensajes viene en la URL.
         */
        $otroUsuarioChatId =
            (int)($_GET['usuario'] ?? 0);

        $chat =
            $mensajeController->abrirChat(
                $libroChatId,
                $otroUsuarioChatId
            );

        if (!$chat) {

            header(
                "Location: index.php?accion=mensajes"
            );

            exit();
        }

        $libroChat =
            $chat['libro'];

        $vendedorChatId =
            $chat['vendedor_id'];

        /*
         * Este es el usuario REAL con quien estamos
         * hablando.
         */
        $otroUsuarioChatId =
            $chat['otro_usuario_id'];

        $mensajesChat =
            $chat['mensajes'];

        /*
         * IMPORTANTE:
         * Ya no buscamos solamente al vendedor.
         * Buscamos a la otra persona de la conversación.
         */
        $otroUsuarioChat =
            $usuarioController->obtenerPorId(
                $otroUsuarioChatId
            );

        /*
         * Se mantiene esta variable porque el chat
         * puede necesitar información del vendedor.
         */
        $vendedorChat =
            $usuarioController->obtenerPorId(
                $vendedorChatId
            );

        include "views/chat.php";

    break;


    // ========================================================
    // MENSAJES
    // ========================================================

    case 'mensajes':

        if (!isset($_SESSION['id_usuario'])) {

            header(
                "Location: index.php?accion=login"
            );

            exit();
        }

        $conversaciones =
            $mensajeController->conversaciones();

        $mensajesNoLeidos =
            $mensajeController->contarNoLeidos();

        include "views/mensajes.php";

    break;


    // ========================================================
    // COMUNIDAD (FASE 5)
    // Los datos ya los armó $comunidadController->preparar().
    // ========================================================

    case 'comunidad':

        include "views/comunidad.php";

    break;

    case 'comunidad_publicacion':

        include "views/comunidad_publicacion.php";

    break;

    case 'comunidad_editar':

        include "views/comunidad_editar.php";

    break;

    case 'comunidad_mensajes':

        include "views/comunidad_mensajes.php";

    break;

    case 'comunidad_chat':

        include "views/comunidad_chat.php";

    break;


    // ========================================================
    // INICIO
    // ========================================================

    default:

        include "views/registro.php";

    break;
}

?>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

<script
    src="assets/js/main.js"
></script>

<script
    src="assets/js/ub-glass.js"
></script>

<?php if (strpos($accion, 'comunidad') === 0) { ?>
<script
    src="assets/js/ub-comunidad.js"
></script>
<?php } ?>

</body>

</html>
