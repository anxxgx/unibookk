-- ============================================================
-- UniBook — FASE 5: Comunidad UniBook
-- ------------------------------------------------------------
-- Ejecutar UNA sola vez sobre la base de datos "unibook":
--   1. Haz una copia de seguridad (phpMyAdmin > Exportar).
--   2. Entra a phpMyAdmin y selecciona la base "unibook".
--   3. Pestaña "SQL": pega todo este archivo y pulsa "Continuar".
--
-- Qué hace:
--   Crea 4 tablas NUEVAS para la Comunidad:
--     comunidad_publicaciones   necesidades, preguntas y recomendaciones
--     comunidad_comentarios     comentarios y respuestas (1 nivel)
--     comunidad_conversaciones  conversación privada (publicación + 2 personas)
--     comunidad_mensajes        mensajes de cada conversación
--
-- Es una migración NO destructiva:
--   * Solo usa CREATE TABLE IF NOT EXISTS: se puede ejecutar de nuevo
--     sin errores y sin tocar datos.
--   * No modifica ni borra ninguna tabla existente (usuarios, libros,
--     compras, intercambios, mensajes, notificaciones, roles).
--   * Solo referencia usuarios(id) mediante claves foráneas.
--
-- Decisiones de diseño:
--   * La mensajería de Comunidad es independiente de la tabla "mensajes"
--     porque esa tabla exige un libro (libro_id NOT NULL) en cada mensaje.
--   * Al borrar una publicación, sus comentarios se borran, pero las
--     conversaciones privadas se conservan (publicacion_id pasa a NULL y
--     queda guardado el título en publicacion_titulo).
--   * Los comentarios eliminados se marcan (eliminado = 1) para no
--     romper las respuestas que cuelgan de ellos.
-- ============================================================

CREATE TABLE IF NOT EXISTS `comunidad_publicaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `fecha_edicion` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cpub_usuario` (`usuario_id`),
  KEY `idx_cpub_fecha` (`fecha_creacion`),
  KEY `idx_cpub_categoria` (`categoria`,`fecha_creacion`),
  KEY `idx_cpub_estado` (`estado`,`fecha_creacion`),
  CONSTRAINT `fk_cpub_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `comunidad_comentarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `publicacion_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `contenido` text NOT NULL,
  `eliminado` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_edicion` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ccom_publicacion` (`publicacion_id`,`parent_id`,`id`),
  KEY `idx_ccom_usuario` (`usuario_id`),
  KEY `idx_ccom_parent` (`parent_id`),
  CONSTRAINT `fk_ccom_publicacion` FOREIGN KEY (`publicacion_id`) REFERENCES `comunidad_publicaciones` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ccom_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ccom_parent` FOREIGN KEY (`parent_id`) REFERENCES `comunidad_comentarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `comunidad_conversaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `publicacion_id` int(11) DEFAULT NULL,
  `publicacion_titulo` varchar(150) NOT NULL,
  `autor_id` int(11) NOT NULL,
  `interesado_id` int(11) NOT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cconv_publicacion_interesado` (`publicacion_id`,`interesado_id`),
  KEY `idx_cconv_autor` (`autor_id`),
  KEY `idx_cconv_interesado` (`interesado_id`),
  CONSTRAINT `fk_cconv_publicacion` FOREIGN KEY (`publicacion_id`) REFERENCES `comunidad_publicaciones` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_cconv_autor` FOREIGN KEY (`autor_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_cconv_interesado` FOREIGN KEY (`interesado_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `comunidad_mensajes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `conversacion_id` int(11) NOT NULL,
  `remitente_id` int(11) NOT NULL,
  `mensaje` text NOT NULL,
  `leido` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_cmsg_conversacion` (`conversacion_id`,`id`),
  KEY `idx_cmsg_no_leidos` (`conversacion_id`,`leido`,`remitente_id`),
  KEY `idx_cmsg_remitente` (`remitente_id`),
  CONSTRAINT `fk_cmsg_conversacion` FOREIGN KEY (`conversacion_id`) REFERENCES `comunidad_conversaciones` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_cmsg_remitente` FOREIGN KEY (`remitente_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
