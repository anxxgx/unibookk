-- UniBook: estructura completa + registros iniciales de demostración.
-- Compatible con MySQL 8+ y MariaDB 10.4+.
-- ADVERTENCIA: para cargarlo desde cero, elimina primero la base de datos
-- existente o importa este archivo en una base vacía llamada "unibook".

CREATE DATABASE IF NOT EXISTS unibook
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE unibook;

CREATE TABLE roles (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, nombre VARCHAR(50) NOT NULL, UNIQUE KEY uq_roles_nombre (nombre)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE usuarios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, nombre VARCHAR(100) NOT NULL, apellido VARCHAR(100) NOT NULL, correo VARCHAR(150) NOT NULL, password VARCHAR(255) NOT NULL,
  telefono VARCHAR(20) NULL, foto VARCHAR(255) NULL, foto_perfil VARCHAR(255) NULL, biografia VARCHAR(500) NULL, universidad VARCHAR(150) NULL, carrera VARCHAR(150) NULL, semestre VARCHAR(20) NULL,
  fecha_registro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo', rol_id INT UNSIGNED NOT NULL DEFAULT 2,
  UNIQUE KEY uq_usuarios_correo (correo), KEY idx_usuarios_rol (rol_id),
  CONSTRAINT fk_usuarios_rol FOREIGN KEY (rol_id) REFERENCES roles(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE libros (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, titulo VARCHAR(150) NOT NULL, autor VARCHAR(100) NOT NULL, editorial VARCHAR(100) NULL, universidad VARCHAR(100) NULL, carrera VARCHAR(100) NULL, semestre VARCHAR(50) NULL, materia VARCHAR(100) NULL,
  descripcion TEXT NULL, precio DECIMAL(10,2) NULL, estado ENUM('Nuevo','Usado') NOT NULL DEFAULT 'Usado', tipo_publicacion ENUM('Venta','Intercambio') NOT NULL DEFAULT 'Venta', imagen VARCHAR(255) NULL,
  fecha_publicacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, usuario_id INT UNSIGNED NOT NULL, estado_disponibilidad ENUM('disponible','reservado','vendido','intercambiado') NOT NULL DEFAULT 'disponible',
  KEY idx_libros_usuario (usuario_id), KEY idx_libros_catalogo (estado_disponibilidad,tipo_publicacion,fecha_publicacion),
  CONSTRAINT fk_libros_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE compras (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, libro_id INT UNSIGNED NOT NULL, comprador_id INT UNSIGNED NOT NULL, vendedor_id INT UNSIGNED NOT NULL, precio DECIMAL(10,2) NOT NULL,
  metodo_pago ENUM('pse','nequi','bancolombia','daviplata') NOT NULL, estado ENUM('pendiente','confirmada','rechazada','cancelada') NOT NULL DEFAULT 'pendiente', fecha_compra DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, fecha_actualizacion DATETIME NULL,
  KEY idx_compras_libro (libro_id), KEY idx_compras_comprador (comprador_id,fecha_compra), KEY idx_compras_vendedor (vendedor_id,fecha_compra),
  CONSTRAINT fk_compras_libro FOREIGN KEY (libro_id) REFERENCES libros(id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_compras_comprador FOREIGN KEY (comprador_id) REFERENCES usuarios(id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_compras_vendedor FOREIGN KEY (vendedor_id) REFERENCES usuarios(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE intercambios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, libro_solicitado_id INT UNSIGNED NOT NULL, libro_ofrecido_id INT UNSIGNED NOT NULL, solicitante_id INT UNSIGNED NOT NULL, propietario_id INT UNSIGNED NOT NULL,
  estado ENUM('pendiente','aceptado','rechazado') NOT NULL DEFAULT 'pendiente', fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, fecha_actualizacion TIMESTAMP NULL DEFAULT NULL,
  KEY idx_intercambios_solicitado (libro_solicitado_id), KEY idx_intercambios_ofrecido (libro_ofrecido_id), KEY idx_intercambios_solicitante (solicitante_id,estado), KEY idx_intercambios_propietario (propietario_id,estado),
  CONSTRAINT fk_intercambios_libro_solicitado FOREIGN KEY (libro_solicitado_id) REFERENCES libros(id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_intercambios_libro_ofrecido FOREIGN KEY (libro_ofrecido_id) REFERENCES libros(id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_intercambios_solicitante FOREIGN KEY (solicitante_id) REFERENCES usuarios(id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_intercambios_propietario FOREIGN KEY (propietario_id) REFERENCES usuarios(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE mensajes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, remitente_id INT UNSIGNED NOT NULL, destinatario_id INT UNSIGNED NOT NULL, libro_id INT UNSIGNED NOT NULL, mensaje TEXT NOT NULL, leido TINYINT(1) NOT NULL DEFAULT 0, fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_mensajes_conversacion (libro_id,remitente_id,destinatario_id,fecha_creacion), KEY idx_mensajes_destinatario (destinatario_id,leido),
  CONSTRAINT fk_mensajes_remitente FOREIGN KEY (remitente_id) REFERENCES usuarios(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_mensajes_destinatario FOREIGN KEY (destinatario_id) REFERENCES usuarios(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_mensajes_libro FOREIGN KEY (libro_id) REFERENCES libros(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE notificaciones (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, usuario_id INT UNSIGNED NOT NULL, tipo VARCHAR(50) NOT NULL, titulo VARCHAR(150) NOT NULL, mensaje TEXT NOT NULL, enlace VARCHAR(255) NULL, leida TINYINT(1) NOT NULL DEFAULT 0, fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_notificaciones_usuario_leida (usuario_id,leida,fecha_creacion),
  CONSTRAINT fk_notificaciones_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE comunidad_publicaciones (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, usuario_id INT UNSIGNED NOT NULL, categoria ENUM('busco_libro','recurso_academico','pregunta_universitaria','recomendacion','otro') NOT NULL DEFAULT 'otro', titulo VARCHAR(150) NOT NULL, contenido TEXT NOT NULL,
  asignatura VARCHAR(120) NULL, carrera VARCHAR(120) NULL, libro_titulo VARCHAR(150) NULL, libro_autor VARCHAR(100) NULL, modalidad ENUM('compra','intercambio','cualquiera') NULL, imagen VARCHAR(255) NULL,
  estado ENUM('activa','resuelta','cerrada') NOT NULL DEFAULT 'activa', fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, fecha_edicion DATETIME NULL,
  KEY idx_cpub_usuario (usuario_id), KEY idx_cpub_filtro (categoria,estado,fecha_creacion),
  CONSTRAINT fk_cpub_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE comunidad_comentarios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, publicacion_id INT UNSIGNED NOT NULL, usuario_id INT UNSIGNED NOT NULL, parent_id INT UNSIGNED NULL, contenido TEXT NOT NULL, eliminado TINYINT(1) NOT NULL DEFAULT 0, fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, fecha_edicion DATETIME NULL,
  KEY idx_ccom_publicacion (publicacion_id,parent_id,id), KEY idx_ccom_usuario (usuario_id),
  CONSTRAINT fk_ccom_publicacion FOREIGN KEY (publicacion_id) REFERENCES comunidad_publicaciones(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_ccom_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_ccom_parent FOREIGN KEY (parent_id) REFERENCES comunidad_comentarios(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE comunidad_conversaciones (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, publicacion_id INT UNSIGNED NULL, publicacion_titulo VARCHAR(150) NOT NULL, autor_id INT UNSIGNED NOT NULL, interesado_id INT UNSIGNED NOT NULL, fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_cconv_publicacion_interesado (publicacion_id,interesado_id), KEY idx_cconv_autor (autor_id), KEY idx_cconv_interesado (interesado_id),
  CONSTRAINT fk_cconv_publicacion FOREIGN KEY (publicacion_id) REFERENCES comunidad_publicaciones(id) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_cconv_autor FOREIGN KEY (autor_id) REFERENCES usuarios(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_cconv_interesado FOREIGN KEY (interesado_id) REFERENCES usuarios(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE comunidad_mensajes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, conversacion_id INT UNSIGNED NOT NULL, remitente_id INT UNSIGNED NOT NULL, mensaje TEXT NOT NULL, leido TINYINT(1) NOT NULL DEFAULT 0, fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_cmsg_conversacion (conversacion_id,id), KEY idx_cmsg_no_leidos (conversacion_id,leido,remitente_id),
  CONSTRAINT fk_cmsg_conversacion FOREIGN KEY (conversacion_id) REFERENCES comunidad_conversaciones(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_cmsg_remitente FOREIGN KEY (remitente_id) REFERENCES usuarios(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Contraseña de los usuarios de demostración: password
INSERT INTO roles (id, nombre) VALUES (1, 'Administrador'), (2, 'Estudiante');
INSERT INTO usuarios (id,nombre,apellido,correo,password,telefono,biografia,universidad,carrera,semestre,rol_id) VALUES
  (1,'Laura','Gómez','laura@unibook.test','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.','3001112233','Estudiante de Ingeniería de Sistemas.','Universidad de Antioquia','Ingeniería de Sistemas','6',1),
  (2,'Mateo','Ruiz','mateo@unibook.test','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.','3002223344','Me interesan los libros de cálculo y programación.','Universidad Nacional','Ingeniería Civil','4',2),
  (3,'Sofía','Martínez','sofia@unibook.test','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.','3003334455','Siempre busco intercambiar libros en buen estado.','UPB','Administración','5',2),
  (4,'Daniel','Rojas','daniel@unibook.test','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.','3004445566','Estudiante y lector de economía.','Politécnico Jaime Isaza Cadavid','Contaduría','3',2);
INSERT INTO libros (id,titulo,autor,editorial,universidad,carrera,semestre,materia,descripcion,precio,estado,tipo_publicacion,usuario_id,estado_disponibilidad) VALUES
  (1,'Cálculo de una variable','James Stewart','Cengage','Universidad Nacional','Ingeniería Civil','4','Cálculo', 'Séptima edición, con ejercicios resueltos.',65000,'Usado','Venta',2,'disponible'),
  (2,'Introducción a los algoritmos','Cormen, Leiserson y Rivest','MIT Press','Universidad de Antioquia','Ingeniería de Sistemas','6','Algoritmos','Libro completo, algunas notas a lápiz.',85000,'Usado','Venta',1,'disponible'),
  (3,'Fundamentos de economía','Paul Krugman','Reverté','UPB','Administración','5','Economía','Buen estado; ideal para intercambio.',0,'Usado','Intercambio',3,'reservado'),
  (4,'Álgebra lineal','David Lay','Pearson','Universidad de Antioquia','Ingeniería de Sistemas','3','Matemáticas','Como nuevo, sin subrayados.',55000,'Nuevo','Venta',1,'vendido'),
  (5,'Contabilidad financiera','Walter Harrison','Pearson','Politécnico Jaime Isaza Cadavid','Contaduría','3','Contabilidad','Incluye guía de ejercicios.',0,'Usado','Intercambio',4,'disponible'),
  (6,'Clean Code','Robert C. Martin','Prentice Hall','Universidad de Antioquia','Ingeniería de Sistemas','6','Programación','Edición en inglés, estado excelente.',70000,'Usado','Venta',1,'disponible');
INSERT INTO compras (id,libro_id,comprador_id,vendedor_id,precio,metodo_pago,estado,fecha_compra,fecha_actualizacion) VALUES
  (1,4,4,1,55000,'nequi','confirmada','2026-10-05 10:20:00','2026-10-05 12:00:00'),
  (2,2,3,1,85000,'pse','pendiente','2026-10-22 15:30:00',NULL);
INSERT INTO intercambios (id,libro_solicitado_id,libro_ofrecido_id,solicitante_id,propietario_id,estado,fecha_creacion,fecha_actualizacion) VALUES
  (1,3,5,4,3,'pendiente','2026-10-24 09:15:00',NULL);
INSERT INTO mensajes (id,remitente_id,destinatario_id,libro_id,mensaje,leido,fecha_creacion) VALUES
  (1,3,1,2,'Hola, ¿el libro sigue disponible?',1,'2026-10-22 15:31:00'),
  (2,1,3,2,'Sí, está disponible. Podemos acordar la entrega en el campus.',0,'2026-10-22 15:35:00');
INSERT INTO notificaciones (id,usuario_id,tipo,titulo,mensaje,enlace,leida,fecha_creacion) VALUES
  (1,1,'nueva_compra','Nueva compra','Sofía inició la compra de "Introducción a los algoritmos".','index.php?accion=detalle_compra&id=2',0,'2026-10-22 15:30:00'),
  (2,3,'intercambio','Nueva solicitud de intercambio','Daniel quiere intercambiar un libro por "Fundamentos de economía".','index.php?accion=intercambios_recibidos',0,'2026-10-24 09:15:00');
INSERT INTO comunidad_publicaciones (id,usuario_id,categoria,titulo,contenido,asignatura,carrera,libro_titulo,libro_autor,modalidad,estado,fecha_creacion) VALUES
  (1,2,'busco_libro','Busco libro de física','Necesito un libro de física universitaria para el próximo semestre.','Física','Ingeniería Civil','Física para ciencias e ingeniería','Serway','cualquiera','activa','2026-10-20 08:00:00'),
  (2,3,'recurso_academico','Guía de estudio para cálculo','Comparto recomendaciones para preparar el parcial de cálculo.','Cálculo','Administración',NULL,NULL,NULL,'activa','2026-10-21 14:00:00');
INSERT INTO comunidad_comentarios (id,publicacion_id,usuario_id,parent_id,contenido,fecha_creacion) VALUES
  (1,1,1,NULL,'Tengo uno disponible; te escribo por privado.','2026-10-20 09:10:00'),
  (2,1,2,1,'¡Gracias! Quedo pendiente.','2026-10-20 09:20:00');
INSERT INTO comunidad_conversaciones (id,publicacion_id,publicacion_titulo,autor_id,interesado_id,fecha_creacion) VALUES
  (1,1,'Busco libro de física',2,1,'2026-10-20 09:11:00');
INSERT INTO comunidad_mensajes (id,conversacion_id,remitente_id,mensaje,leido,fecha_creacion) VALUES
  (1,1,1,'Hola, tengo un libro de física en buen estado.',1,'2026-10-20 09:11:00'),
  (2,1,2,'Perfecto, ¿me puedes enviar una foto?',0,'2026-10-20 09:14:00');
