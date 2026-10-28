-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 28-10-2026 a las 20:26:43
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `unibook`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `compras`
--

CREATE TABLE `compras` (
  `id` int(11) NOT NULL,
  `libro_id` int(11) NOT NULL,
  `comprador_id` int(11) NOT NULL,
  `vendedor_id` int(11) NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `metodo_pago` enum('pse','nequi','bancolombia','daviplata') NOT NULL,
  `estado` enum('pendiente','confirmada','rechazada','cancelada') NOT NULL DEFAULT 'pendiente',
  `fecha_compra` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `compras`
--

INSERT INTO `compras` (`id`, `libro_id`, `comprador_id`, `vendedor_id`, `precio`, `metodo_pago`, `estado`, `fecha_compra`, `fecha_actualizacion`) VALUES
(1, 8, 16, 13, 120000.00, 'pse', 'pendiente', '2026-09-11 14:33:05', NULL),
(2, 7, 16, 12, 200000.00, 'bancolombia', 'cancelada', '2026-09-11 14:34:15', '2026-09-11 15:53:14'),
(3, 1, 16, 12, 20.00, 'nequi', 'confirmada', '2026-09-11 14:43:14', '2026-09-11 15:52:17'),
(4, 7, 16, 12, 200000.00, 'pse', 'confirmada', '2026-09-11 15:58:31', '2026-09-11 20:58:58'),
(5, 12, 18, 12, 127764.00, 'bancolombia', 'confirmada', '2026-09-22 18:56:01', '2026-09-22 19:29:19'),
(6, 14, 19, 18, 1200.00, 'daviplata', 'confirmada', '2026-09-23 14:27:57', '2026-09-23 14:28:40');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comunidad_comentarios`
--

CREATE TABLE `comunidad_comentarios` (
  `id` int(11) NOT NULL,
  `publicacion_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `contenido` text NOT NULL,
  `eliminado` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_edicion` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `comunidad_comentarios`
--

INSERT INTO `comunidad_comentarios` (`id`, `publicacion_id`, `usuario_id`, `parent_id`, `contenido`, `eliminado`, `fecha_creacion`, `fecha_edicion`) VALUES
(1, 1, 18, NULL, 'hi', 0, '2026-10-25 10:33:00', NULL),
(2, 1, 12, 1, 'jajjaja', 0, '2026-10-25 10:36:12', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comunidad_conversaciones`
--

CREATE TABLE `comunidad_conversaciones` (
  `id` int(11) NOT NULL,
  `publicacion_id` int(11) DEFAULT NULL,
  `publicacion_titulo` varchar(150) NOT NULL,
  `autor_id` int(11) NOT NULL,
  `interesado_id` int(11) NOT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `comunidad_conversaciones`
--

INSERT INTO `comunidad_conversaciones` (`id`, `publicacion_id`, `publicacion_titulo`, `autor_id`, `interesado_id`, `fecha_creacion`) VALUES
(1, 1, 'Nini Pupu', 18, 12, '2026-10-25 10:35:28');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comunidad_mensajes`
--

CREATE TABLE `comunidad_mensajes` (
  `id` int(11) NOT NULL,
  `conversacion_id` int(11) NOT NULL,
  `remitente_id` int(11) NOT NULL,
  `mensaje` text NOT NULL,
  `leido` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `comunidad_mensajes`
--

INSERT INTO `comunidad_mensajes` (`id`, `conversacion_id`, `remitente_id`, `mensaje`, `leido`, `fecha_creacion`) VALUES
(1, 1, 12, 'hola', 1, '2026-10-25 10:35:28'),
(2, 1, 18, 'hola, que mas?', 0, '2026-10-27 19:18:12');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comunidad_publicaciones`
--

CREATE TABLE `comunidad_publicaciones` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `categoria` enum('busco_libro','recurso_academico','pregunta_universitaria','recomendacion','otro') NOT NULL DEFAULT 'otro',
  `titulo` varchar(150) NOT NULL,
  `contenido` text NOT NULL,
  `asignatura` varchar(120) DEFAULT NULL,
  `carrera` varchar(120) DEFAULT NULL,
  `libro_titulo` varchar(150) DEFAULT NULL,
  `libro_autor` varchar(100) DEFAULT NULL,
  `modalidad` enum('compra','intercambio','cualquiera') DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `estado` enum('activa','resuelta','cerrada') NOT NULL DEFAULT 'activa',
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_edicion` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `comunidad_publicaciones`
--

INSERT INTO `comunidad_publicaciones` (`id`, `usuario_id`, `categoria`, `titulo`, `contenido`, `asignatura`, `carrera`, `libro_titulo`, `libro_autor`, `modalidad`, `imagen`, `estado`, `fecha_creacion`, `fecha_edicion`) VALUES
(1, 18, 'busco_libro', 'Nini Pupu', 'no se eeeeeee', NULL, NULL, NULL, NULL, 'cualquiera', NULL, 'activa', '2026-10-25 10:32:39', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `intercambios`
--

CREATE TABLE `intercambios` (
  `id` int(11) NOT NULL,
  `libro_solicitado_id` int(11) NOT NULL,
  `libro_ofrecido_id` int(11) NOT NULL,
  `solicitante_id` int(11) NOT NULL,
  `propietario_id` int(11) NOT NULL,
  `estado` enum('pendiente','aceptado','rechazado') DEFAULT 'pendiente',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `intercambios`
--

INSERT INTO `intercambios` (`id`, `libro_solicitado_id`, `libro_ofrecido_id`, `solicitante_id`, `propietario_id`, `estado`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, 6, 9, 16, 12, 'aceptado', '2026-09-18 23:04:48', NULL),
(2, 6, 9, 16, 12, 'rechazado', '2026-09-18 23:06:17', NULL),
(3, 10, 6, 12, 16, 'pendiente', '2026-09-18 23:33:50', NULL),
(4, 10, 3, 12, 16, 'pendiente', '2026-09-19 00:12:48', NULL),
(5, 15, 13, 18, 12, 'aceptado', '2026-09-25 14:30:17', NULL),
(6, 19, 20, 13, 18, 'pendiente', '2026-10-02 21:22:35', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `libros`
--

CREATE TABLE `libros` (
  `id` int(11) NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `autor` varchar(100) NOT NULL,
  `editorial` varchar(100) DEFAULT NULL,
  `universidad` varchar(100) DEFAULT NULL,
  `carrera` varchar(100) DEFAULT NULL,
  `semestre` varchar(50) DEFAULT NULL,
  `materia` varchar(100) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `precio` decimal(10,2) DEFAULT NULL,
  `estado` enum('Nuevo','Usado') DEFAULT 'Usado',
  `tipo_publicacion` enum('Venta','Intercambio') DEFAULT 'Venta',
  `imagen` varchar(255) DEFAULT NULL,
  `fecha_publicacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `usuario_id` int(11) DEFAULT NULL,
  `estado_disponibilidad` enum('disponible','reservado','vendido','intercambiado') NOT NULL DEFAULT 'disponible'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `libros`
--

INSERT INTO `libros` (`id`, `titulo`, `autor`, `editorial`, `universidad`, `carrera`, `semestre`, `materia`, `descripcion`, `precio`, `estado`, `tipo_publicacion`, `imagen`, `fecha_publicacion`, `usuario_id`, `estado_disponibilidad`) VALUES
(1, 'Harry culo', 'melany', 'd1', 'upb', 'contabilidad', '2', 'Ingles', 'usado-nuevo', 20.00, 'Nuevo', 'Venta', NULL, '2026-08-12 19:51:07', 12, 'vendido'),
(2, 'Nini pupu', 'Camila', 'el ara', 'sena', 'programacion', '5', 'logica', 'usado', 20.00, 'Usado', 'Venta', NULL, '2026-08-12 20:45:00', 12, 'disponible'),
(3, 'Angi dueña de valery', 'Angi', 'Privado', 'upb', 'programacion de sofware', '8', 'logica', 'casi nuevo', 20.00, 'Usado', 'Venta', NULL, '2026-08-12 20:50:48', 12, 'disponible'),
(4, 'Jimeenis', 'Valesca', 'Privado', 'upb', 'contabilidad', '5', 'Ingles', 'Libro lindo', 17.00, 'Nuevo', 'Intercambio', 'assets/uploads/1786568461_procsio.PNG', '2026-08-12 21:01:01', 12, 'disponible'),
(5, 'Jimeenis', 'Valesca', 'Privado', 'upb', 'contabilidad', '5', 'Ingles', 'Libro lindo', 17.00, 'Nuevo', 'Intercambio', 'assets/uploads/1786568582_procsio.PNG', '2026-08-12 21:03:02', 12, 'disponible'),
(6, 'Jimeenis', 'Valesca', 'Privado', 'upb', 'contabilidad', '12', 'Ingles', 'lindito', 3.00, 'Nuevo', 'Intercambio', 'assets/uploads/1786568629_procsio.PNG', '2026-08-12 21:03:49', 12, 'disponible'),
(7, 'Maryi', 'Jimena', 'Privado', 'Pascual Bravo', 'Gastronomia', '8', 'comida', 'Rico magi de maryi', 200000.00, 'Nuevo', 'Venta', 'assets/uploads/1786736514_Captura-de-pantalla-2025-05-28-121939.png', '2026-08-14 19:41:54', 12, 'vendido'),
(8, 'Aplicaciones de las funciones Algebraicas', 'Carlos Javier Rojas Alvarez', 'no se ', 'upb', 'matematicas', '12', 'logica', 'es lindo', 120000.00, 'Nuevo', 'Venta', 'assets/uploads/1786740769_libroMATE.jpg', '2026-08-14 20:52:49', 13, 'reservado'),
(9, 'Calculo', 'Juanes ', 'no se ', 'udea', 'matematicas', '5', 'calculo', '0', 12000.00, 'Nuevo', 'Intercambio', 'assets/uploads/1789772649_Captura.PNG', '2026-09-18 23:04:09', 16, 'disponible'),
(10, 'aa', 'ssss', 'ssssssss', 'aaaaaaaaa', '55', '5', 'dhjfh', 'bnp', 0.00, 'Nuevo', 'Intercambio', '', '2026-09-18 23:32:28', 16, 'disponible'),
(11, 'sssssss', 'rrrrrrr', 'wwwwwwwwwww', 'wwwwwwwww', '4', '4', 'mnqjkdnj', '0', 0.00, 'Nuevo', 'Intercambio', '', '2026-09-22 23:28:11', 18, 'disponible'),
(12, 'abbbjbzbhj', 'bjsajabahj', 'gsggs', 'hbsbhjshj', 'sbhjsjs', '8', 'djhihwi', '0', 127764.00, 'Nuevo', 'Venta', '', '2026-09-22 23:54:38', 12, 'vendido'),
(13, 'dhjskk', 'jas', 'djjd', 'upb', 'fjdk', '3', 'oqbe', 'es feo', 122000.00, 'Nuevo', 'Intercambio', '', '2026-09-23 19:24:49', 18, 'intercambiado'),
(14, 'magi', 'maryi', 'camila', 'nini', 'luciana', '4', 'ana y any', 'es feito', 1200.00, 'Nuevo', 'Venta', '', '2026-09-23 19:26:16', 18, 'vendido'),
(15, 'holaaaaaaa', 'yooooo', 'no seeeeeeeeeeeee', 'upb', 'noshe', '4', 'ingles', '0', 0.00, 'Nuevo', 'Intercambio', '', '2026-09-25 14:29:29', 12, 'intercambiado'),
(16, 'ULTIMA PRUEBA CREO', 'SHJSouns', 'SAJHOL', 'SJKGWOWDW', '', '', '', '0', 0.00, NULL, 'Venta', '', '2026-10-02 21:11:16', 18, 'disponible'),
(17, 'ULTIMA PRUEBA CREO', 'SHJSouns', 'SAJHOL', 'SJKGWOWDW', '', '', '', '0', 0.00, NULL, 'Venta', '', '2026-10-02 21:14:56', 18, 'disponible'),
(18, 'ULTIMA PRUEBA CREO', 'maryi', 'camila', 'kjwkj', 'ingenieria', '5', 'SIJAO', '0', 126281.00, 'Nuevo', 'Venta', '', '2026-10-02 21:15:47', 18, 'disponible'),
(19, 'INTERCAMBIO ULTIMO', 'hbxbs', 'jjkj', 'nini', 'asoijkjs', '6', 'juiugiu', '0', 0.00, 'Usado', 'Intercambio', '', '2026-10-02 21:16:47', 18, 'reservado'),
(20, 'jlNQYLA', 'SQJJSLk', 'slkjLK', 'xBJK', 'XSKJLS', 'ALNl', 'LKi', '0', 0.00, 'Nuevo', 'Intercambio', '', '2026-10-02 21:22:22', 13, 'reservado'),
(21, 'Diseño', 'YO', 'Yo', 'upb', 'matematicas', '8', 'ingles', 'esta melo', 120000.00, 'Nuevo', 'Venta', 'assets/uploads/9d1780a0a6f0cb3a797a5c2137e11a4e.jpg', '2026-10-20 22:42:03', 18, 'disponible');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mensajes`
--

CREATE TABLE `mensajes` (
  `id` int(11) NOT NULL,
  `remitente_id` int(11) NOT NULL,
  `destinatario_id` int(11) NOT NULL,
  `libro_id` int(11) NOT NULL,
  `mensaje` text NOT NULL,
  `leido` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `mensajes`
--

INSERT INTO `mensajes` (`id`, `remitente_id`, `destinatario_id`, `libro_id`, `mensaje`, `leido`, `fecha_creacion`) VALUES
(1, 12, 18, 13, 'hola', 1, '2026-09-23 14:52:17'),
(2, 18, 12, 13, 'hola', 1, '2026-09-23 16:09:18'),
(3, 18, 12, 13, 'holja', 1, '2026-09-23 16:09:52'),
(4, 12, 18, 13, 'hola', 1, '2026-09-23 16:10:57'),
(5, 12, 18, 13, 'holaaaaa', 1, '2026-09-23 16:20:04'),
(6, 18, 12, 13, 'hollaaa', 1, '2026-09-23 16:20:14'),
(7, 18, 12, 13, 'hola', 1, '2026-10-22 15:44:51'),
(8, 18, 12, 13, 'hiiii', 1, '2026-10-22 16:11:06');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

CREATE TABLE `notificaciones` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `mensaje` text NOT NULL,
  `enlace` varchar(255) DEFAULT NULL,
  `leida` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `notificaciones`
--

INSERT INTO `notificaciones` (`id`, `usuario_id`, `tipo`, `titulo`, `mensaje`, `enlace`, `leida`, `fecha_creacion`) VALUES
(1, 16, 'intercambio', 'Nueva solicitud de intercambio', 'Alguien quiere intercambiar su libro por \"aa\". Revisa tus solicitudes de intercambio.', 'index.php?accion=intercambios_recibidos', 0, '2026-09-18 18:33:50'),
(2, 16, 'intercambio', 'Nueva solicitud de intercambio', 'Alguien quiere intercambiar su libro por \"aa\". Revisa tus solicitudes de intercambio.', 'index.php?accion=intercambios_recibidos', 0, '2026-09-18 19:12:48'),
(3, 12, 'nueva_compra', 'Nueva compra', 'Vale Perez ha realizado una compra de \"abbbjbzbhj\". Revisa la operación y confirma la venta.', 'index.php?accion=detalle_compra&id=5', 0, '2026-09-22 18:56:01'),
(4, 18, 'compra_confirmada', '¡Compra confirmada!', 'El vendedor ha confirmado tu compra de \"abbbjbzbhj\". La operación fue aceptada.', 'index.php?accion=detalle_compra&id=5', 0, '2026-09-22 19:29:19'),
(5, 18, 'nueva_compra', 'Nueva compra', 'Melany Montes ha realizado una compra de \"magi\". Revisa la operación y confirma la venta.', 'index.php?accion=detalle_compra&id=6', 1, '2026-09-23 14:27:57'),
(6, 19, 'compra_confirmada', '¡Compra confirmada!', 'El vendedor ha confirmado tu compra de \"magi\". La operación fue aceptada.', 'index.php?accion=detalle_compra&id=6', 0, '2026-09-23 14:28:40'),
(7, 12, 'intercambio', 'Nueva solicitud de intercambio', 'Alguien quiere intercambiar su libro por \"holaaaaaaa\". Revisa tus solicitudes de intercambio.', 'index.php?accion=intercambios_recibidos', 0, '2026-09-25 09:30:17'),
(8, 18, 'intercambio', '¡Intercambio aceptado!', 'Tu solicitud de intercambio fue aceptada. Los dos libros ahora forman parte del intercambio.', 'index.php?accion=intercambios_enviados', 0, '2026-09-25 09:31:53'),
(9, 18, 'intercambio', 'Nueva solicitud de intercambio', 'Alguien quiere intercambiar su libro por \"INTERCAMBIO ULTIMO\". Revisa tus solicitudes de intercambio.', 'index.php?accion=intercambios_recibidos', 0, '2026-10-02 16:22:35'),
(10, 18, 'comunidad_mensaje', 'Nuevo mensaje privado', 'Angimar te escribió sobre \"Nini Pupu\".', 'index.php?accion=comunidad_chat&id=1', 0, '2026-10-25 10:35:28'),
(11, 18, 'comunidad_comentario', 'Nuevo comentario en tu publicación', 'Angimar comentó en \"Nini Pupu\".', 'index.php?accion=comunidad_publicacion&id=1#comentarios', 0, '2026-10-25 10:36:12');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `nombre`) VALUES
(1, 'Administrador'),
(2, 'Estudiante');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `correo` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `rol_id` int(11) NOT NULL DEFAULT 2,
  `foto_perfil` varchar(255) DEFAULT NULL,
  `biografia` varchar(500) DEFAULT NULL,
  `universidad` varchar(150) DEFAULT NULL,
  `carrera` varchar(150) DEFAULT NULL,
  `semestre` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `apellido`, `correo`, `password`, `telefono`, `foto`, `fecha_registro`, `estado`, `rol_id`, `foto_perfil`, `biografia`, `universidad`, `carrera`, `semestre`) VALUES
(12, 'Angimar', 'Gonzalez', 'angimargonzalez154@gmail.com', '$2y$10$6agz.X/H6qF7r8USzgYP6uWjsEFKBd3p.10ju0eHnN6T14sRQP3EG', NULL, NULL, '2026-08-12 19:33:07', 'activo', 2, NULL, NULL, NULL, NULL, NULL),
(13, 'Angimar', 'Carolina', 'angimarcarolina06@gmail.com', '$2y$10$JZT/vfA12sl6lJTy0yrp1OsWzgm9guzyrdIYZCbj2XRoxtRbSRS/m', NULL, NULL, '2026-08-14 20:47:20', 'activo', 2, NULL, NULL, NULL, NULL, NULL),
(14, 'angi', 'gonzalez', 'angi1909@gmail.com', '$2y$10$Yse543MFlnEvgyjJZVatNOIPT5GmCmx2m4EBzsX67VcbuvJPoxCVq', NULL, NULL, '2026-08-19 19:44:29', 'activo', 2, NULL, NULL, NULL, NULL, NULL),
(16, 'cristian', 'morales', 'cris@gmail.com', '$2y$10$dYGynFCi5rGmT3xI6oO1YOGD8eYJyV21WkDVSkSVHlFA0KoV0qD3W', NULL, NULL, '2026-09-02 20:10:21', 'activo', 2, NULL, NULL, NULL, NULL, NULL),
(17, 'Caro', 'Lina', 'caro@gmail.com', '$2y$10$lcw64GiSvOIRpfAYYZiwo.nwjitS1Kxr9EQ3oiOiEbQyzcu0ec76e', NULL, NULL, '2026-09-19 00:20:02', 'activo', 2, NULL, NULL, NULL, NULL, NULL),
(18, 'Vale', 'Perez', 'vale@gmail.com', '$2y$10$fIOVTednXxKnEnbk5xx0uuLHhTZaI0tFvF62o50xYt/Mrb6Kq84S2', NULL, NULL, '2026-09-22 23:27:32', 'activo', 2, 'assets/uploads/perfiles/u18_9896428a336d6c26381970b6.jpg', 'hola', 'upb', 'matematicas', '6'),
(19, 'Melany', 'Montes', 'mel0506montes@gmail.com', '$2y$10$sG2wy/XZK29QzjjT2k5kHu9cwf9Xifs9Xsx6mK3afCESoHbtnKc8W', NULL, NULL, '2026-09-23 19:26:59', 'activo', 2, NULL, NULL, NULL, NULL, NULL),
(20, 'Melany', 'Mones', 'melahiana@gmail.com', '$2y$10$0Th2hyDsyM71vUUB672bHO8q/12PsOy6Njx/LF3zG0FEKMQGWxJlS', NULL, NULL, '2026-10-14 19:46:53', 'activo', 2, NULL, NULL, NULL, NULL, NULL);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `compras`
--
ALTER TABLE `compras`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_compras_libro` (`libro_id`),
  ADD KEY `idx_compras_comprador` (`comprador_id`),
  ADD KEY `idx_compras_vendedor` (`vendedor_id`);

--
-- Indices de la tabla `comunidad_comentarios`
--
ALTER TABLE `comunidad_comentarios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ccom_publicacion` (`publicacion_id`,`parent_id`,`id`),
  ADD KEY `idx_ccom_usuario` (`usuario_id`),
  ADD KEY `idx_ccom_parent` (`parent_id`);

--
-- Indices de la tabla `comunidad_conversaciones`
--
ALTER TABLE `comunidad_conversaciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_cconv_publicacion_interesado` (`publicacion_id`,`interesado_id`),
  ADD KEY `idx_cconv_autor` (`autor_id`),
  ADD KEY `idx_cconv_interesado` (`interesado_id`);

--
-- Indices de la tabla `comunidad_mensajes`
--
ALTER TABLE `comunidad_mensajes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cmsg_conversacion` (`conversacion_id`,`id`),
  ADD KEY `idx_cmsg_no_leidos` (`conversacion_id`,`leido`,`remitente_id`),
  ADD KEY `idx_cmsg_remitente` (`remitente_id`);

--
-- Indices de la tabla `comunidad_publicaciones`
--
ALTER TABLE `comunidad_publicaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cpub_usuario` (`usuario_id`),
  ADD KEY `idx_cpub_fecha` (`fecha_creacion`),
  ADD KEY `idx_cpub_categoria` (`categoria`,`fecha_creacion`),
  ADD KEY `idx_cpub_estado` (`estado`,`fecha_creacion`);

--
-- Indices de la tabla `intercambios`
--
ALTER TABLE `intercambios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `libro_solicitado_id` (`libro_solicitado_id`),
  ADD KEY `libro_ofrecido_id` (`libro_ofrecido_id`);

--
-- Indices de la tabla `libros`
--
ALTER TABLE `libros`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `mensajes`
--
ALTER TABLE `mensajes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_mensajes_remitente` (`remitente_id`),
  ADD KEY `fk_mensajes_destinatario` (`destinatario_id`),
  ADD KEY `fk_mensajes_libro` (`libro_id`);

--
-- Indices de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notificaciones_usuario` (`usuario_id`),
  ADD KEY `idx_notificaciones_leida` (`usuario_id`,`leida`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `correo` (`correo`),
  ADD KEY `rol_id` (`rol_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `compras`
--
ALTER TABLE `compras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `comunidad_comentarios`
--
ALTER TABLE `comunidad_comentarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `comunidad_conversaciones`
--
ALTER TABLE `comunidad_conversaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `comunidad_mensajes`
--
ALTER TABLE `comunidad_mensajes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `comunidad_publicaciones`
--
ALTER TABLE `comunidad_publicaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `intercambios`
--
ALTER TABLE `intercambios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `libros`
--
ALTER TABLE `libros`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de la tabla `mensajes`
--
ALTER TABLE `mensajes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `comunidad_comentarios`
--
ALTER TABLE `comunidad_comentarios`
  ADD CONSTRAINT `fk_ccom_parent` FOREIGN KEY (`parent_id`) REFERENCES `comunidad_comentarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ccom_publicacion` FOREIGN KEY (`publicacion_id`) REFERENCES `comunidad_publicaciones` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ccom_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `comunidad_conversaciones`
--
ALTER TABLE `comunidad_conversaciones`
  ADD CONSTRAINT `fk_cconv_autor` FOREIGN KEY (`autor_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cconv_interesado` FOREIGN KEY (`interesado_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cconv_publicacion` FOREIGN KEY (`publicacion_id`) REFERENCES `comunidad_publicaciones` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `comunidad_mensajes`
--
ALTER TABLE `comunidad_mensajes`
  ADD CONSTRAINT `fk_cmsg_conversacion` FOREIGN KEY (`conversacion_id`) REFERENCES `comunidad_conversaciones` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cmsg_remitente` FOREIGN KEY (`remitente_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `comunidad_publicaciones`
--
ALTER TABLE `comunidad_publicaciones`
  ADD CONSTRAINT `fk_cpub_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `intercambios`
--
ALTER TABLE `intercambios`
  ADD CONSTRAINT `intercambios_ibfk_1` FOREIGN KEY (`libro_solicitado_id`) REFERENCES `libros` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `intercambios_ibfk_2` FOREIGN KEY (`libro_ofrecido_id`) REFERENCES `libros` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `libros`
--
ALTER TABLE `libros`
  ADD CONSTRAINT `libros_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `mensajes`
--
ALTER TABLE `mensajes`
  ADD CONSTRAINT `fk_mensajes_destinatario` FOREIGN KEY (`destinatario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mensajes_libro` FOREIGN KEY (`libro_id`) REFERENCES `libros` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mensajes_remitente` FOREIGN KEY (`remitente_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD CONSTRAINT `fk_notificaciones_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
