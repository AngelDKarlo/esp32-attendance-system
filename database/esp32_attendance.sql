-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 30-10-2026 a las 15:07:07
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `esp32_attendance`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asistencias`
--

CREATE TABLE `asistencias` (
  `id` int(11) NOT NULL,
  `usuario_id` varchar(50) NOT NULL,
  `clase_id` int(11) DEFAULT NULL,
  `fecha` date NOT NULL,
  `hora` time NOT NULL,
  `tipo` enum('RFID_VERIFICADO','MATRICULA_VERIFICADA','AUTORIZADO_DOCENTE') NOT NULL,
  `estado` enum('presente','retardo','falta','justificado') DEFAULT 'presente',
  `docente_autorizo_id` varchar(50) DEFAULT NULL,
  `dispositivo_id` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `asistencias`
--

INSERT INTO `asistencias` (`id`, `usuario_id`, `clase_id`, `fecha`, `hora`, `tipo`, `estado`, `docente_autorizo_id`, `dispositivo_id`) VALUES
(1, '2330018', NULL, '2026-07-09', '10:14:25', 'RFID_VERIFICADO', 'presente', NULL, NULL),
(2, '2330018', 2, '2026-09-08', '07:54:00', 'MATRICULA_VERIFICADA', 'falta', NULL, NULL),
(3, '2330319', 2, '2026-09-08', '07:54:00', 'MATRICULA_VERIFICADA', 'falta', NULL, NULL),
(4, '2330018', 5, '2026-10-16', '08:49:00', 'MATRICULA_VERIFICADA', 'falta', NULL, NULL),
(5, '2330319', 5, '2026-10-16', '08:49:00', 'MATRICULA_VERIFICADA', 'falta', NULL, NULL),
(6, '2430257', NULL, '2026-10-16', '11:31:48', 'MATRICULA_VERIFICADA', 'presente', NULL, 'ESP32-A214-01');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clases`
--

CREATE TABLE `clases` (
  `id` int(11) NOT NULL,
  `codigo_materia` varchar(10) NOT NULL DEFAULT '0000',
  `nombre_materia` varchar(100) NOT NULL,
  `docente_id` varchar(50) NOT NULL,
  `dia_semana` enum('lunes','martes','miercoles','jueves','viernes','sabado') NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `aula` varchar(50) DEFAULT NULL,
  `carrera` varchar(100) DEFAULT NULL,
  `grupo` varchar(20) DEFAULT NULL,
  `semestre` int(11) DEFAULT NULL,
  `total_sesiones` int(11) DEFAULT 0,
  `limite_faltas` int(11) DEFAULT 3
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `clases`
--

INSERT INTO `clases` (`id`, `codigo_materia`, `nombre_materia`, `docente_id`, `dia_semana`, `hora_inicio`, `hora_fin`, `aula`, `carrera`, `grupo`, `semestre`, `total_sesiones`, `limite_faltas`) VALUES
(1, '0005', 'Sistemas Inteligentes', '2330319', 'lunes', '07:00:00', '07:54:00', 'A214', 'Ingeniería en Tecnologías de la Información', '1', 9, 0, 3),
(2, '0001', 'Sistemas Inteligentes', '2330319', 'martes', '07:00:00', '07:54:00', 'A214', 'Ingeniería en Tecnologías de la Información', '1', 9, 0, 3),
(3, '0002', 'Sistemas Inteligentes', '2330319', 'jueves', '07:00:00', '07:54:00', 'A214', 'Ingeniería en Tecnologías de la Información', '1', 9, 0, 3),
(4, '0003', 'Sistemas Inteligentes', '2330319', 'miercoles', '07:00:00', '07:54:00', 'A214', 'Ingeniería en Tecnologías de la Información', '1', 9, 0, 3),
(5, '0004', 'Sistemas Inteligentes', '2330319', 'viernes', '07:00:00', '08:49:00', 'A214', 'Ingeniería en Tecnologías de la Información', '1', 9, 0, 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `codigos_verificacion`
--

CREATE TABLE `codigos_verificacion` (
  `id` int(11) NOT NULL,
  `usuario_id` varchar(50) NOT NULL,
  `codigo` varchar(10) NOT NULL,
  `tipo_flujo` enum('RFID','MATRICULA') NOT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `expira_en` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `intentos` int(11) DEFAULT 0,
  `estado` enum('pendiente','verificado','expirado') DEFAULT 'pendiente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `codigos_verificacion`
--

INSERT INTO `codigos_verificacion` (`id`, `usuario_id`, `codigo`, `tipo_flujo`, `creado_en`, `expira_en`, `intentos`, `estado`) VALUES
(1, '2330018', '7508', 'MATRICULA', '2026-07-09 13:54:02', '2026-07-09 13:59:02', 0, 'expirado'),
(2, '2330018', '7731', 'MATRICULA', '2026-07-09 13:57:43', '2026-07-09 14:02:43', 1, 'expirado'),
(3, '2330018', '1484', 'MATRICULA', '2026-07-09 15:31:00', '2026-07-09 15:36:00', 0, 'expirado'),
(4, '2330018', '2340', 'MATRICULA', '2026-07-09 15:52:20', '2026-07-09 15:57:20', 0, 'expirado'),
(5, '2330018', '4071', 'RFID', '2026-07-09 15:53:11', '2026-07-09 15:58:11', 0, 'expirado'),
(6, '2330018', '7594', 'RFID', '2026-07-09 15:56:57', '2026-07-09 16:01:57', 0, 'expirado'),
(7, '2330018', '5408', 'RFID', '2026-07-09 16:01:52', '2026-07-09 16:06:52', 0, 'expirado'),
(8, '2330018', '0543', 'RFID', '2026-07-09 16:02:45', '2026-07-09 16:07:45', 0, 'expirado'),
(9, '2330018', '5220', 'RFID', '2026-07-09 16:02:55', '2026-07-09 16:07:55', 0, 'expirado'),
(10, '2330018', '0798', 'RFID', '2026-07-09 16:04:01', '2026-07-09 16:09:01', 0, 'expirado'),
(11, '2330018', '6487', 'RFID', '2026-07-09 16:05:21', '2026-07-09 16:10:21', 0, 'expirado'),
(12, '2330018', '8402', 'RFID', '2026-07-09 16:06:41', '2026-07-09 16:11:41', 0, 'expirado'),
(13, '2330018', '0945', 'RFID', '2026-07-09 16:13:48', '2026-07-09 16:18:48', 0, 'verificado'),
(14, '2330018', '2193', 'RFID', '2026-07-09 16:16:55', '2026-07-09 16:21:55', 0, 'expirado'),
(15, '2330018', '9418', 'MATRICULA', '2026-10-16 16:41:29', '2026-10-16 16:46:29', 0, 'expirado'),
(16, '2430257', '5625', 'MATRICULA', '2026-10-16 17:00:51', '2026-10-16 17:05:51', 0, 'expirado'),
(17, '2430257', '5885', 'MATRICULA', '2026-10-16 17:03:24', '2026-10-16 17:08:24', 0, 'expirado'),
(18, '2430257', '9794', 'MATRICULA', '2026-10-16 17:04:19', '2026-10-16 17:09:19', 0, 'expirado'),
(19, '2430257', '7440', 'MATRICULA', '2026-10-16 17:10:17', '2026-10-16 17:15:17', 0, 'expirado'),
(20, '2330018', '5808', 'MATRICULA', '2026-10-16 17:11:52', '2026-10-16 17:16:52', 0, 'pendiente'),
(21, '2430257', '1964', 'MATRICULA', '2026-10-16 17:12:54', '2026-10-16 17:17:54', 0, 'expirado'),
(22, '2430257', '1199', 'MATRICULA', '2026-10-16 17:21:58', '2026-10-16 17:26:58', 0, 'expirado'),
(23, '2430257', '2609', 'MATRICULA', '2026-10-16 17:31:35', '2026-10-16 17:36:35', 0, 'verificado');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dispositivos`
--

CREATE TABLE `dispositivos` (
  `id` int(11) NOT NULL,
  `device_id` varchar(50) NOT NULL COMMENT 'Identificador único grabado en el firmware',
  `nombre` varchar(100) DEFAULT NULL,
  `aula` varchar(50) DEFAULT NULL,
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo',
  `ultima_conexion` timestamp NULL DEFAULT NULL,
  `ultima_ip` varchar(45) DEFAULT NULL,
  `total_lecturas` int(11) NOT NULL DEFAULT 0,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `dispositivos`
--

INSERT INTO `dispositivos` (`id`, `device_id`, `nombre`, `aula`, `estado`, `ultima_conexion`, `ultima_ip`, `total_lecturas`, `fecha_registro`) VALUES
(1, 'ESP32-A214-01', 'Caja principal', 'A214', 'activo', '2026-10-16 17:31:59', '192.168.1.90', 522, '2026-09-08 13:49:48');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `enrolamientos_rfid`
--

CREATE TABLE `enrolamientos_rfid` (
  `id` int(11) NOT NULL,
  `matricula` varchar(50) NOT NULL COMMENT 'Usuario al que se le asignará la tarjeta',
  `solicitado_por` varchar(50) DEFAULT NULL COMMENT 'Admin o docente que autorizó',
  `origen` enum('web','caja') NOT NULL DEFAULT 'web',
  `device_id` varchar(50) DEFAULT NULL COMMENT 'Caja que debe leer la tarjeta (NULL = cualquiera)',
  `uid` varchar(50) DEFAULT NULL COMMENT 'UID leído al completarse',
  `estado` enum('pendiente','completado','cancelado','expirado') NOT NULL DEFAULT 'pendiente',
  `mensaje` varchar(150) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `expira_en` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inscripciones`
--

CREATE TABLE `inscripciones` (
  `id` int(11) NOT NULL,
  `clase_id` int(11) NOT NULL,
  `alumno_id` varchar(50) NOT NULL,
  `fecha_inscripcion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `inscripciones`
--

INSERT INTO `inscripciones` (`id`, `clase_id`, `alumno_id`, `fecha_inscripcion`) VALUES
(1, 1, '2330018', '2026-09-08 13:49:48'),
(2, 2, '2330018', '2026-09-08 13:49:48'),
(3, 3, '2330018', '2026-09-08 13:49:48'),
(4, 4, '2330018', '2026-09-08 13:49:48'),
(5, 5, '2330018', '2026-09-08 13:49:48');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `matricula` varchar(50) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `rol` enum('alumno','docente','administrativo') NOT NULL DEFAULT 'alumno',
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo',
  `id_telegram` varchar(50) DEFAULT NULL,
  `tarjeta_rfid` varchar(50) DEFAULT NULL,
  `contrasena` varchar(255) NOT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `carrera` varchar(100) DEFAULT NULL,
  `grupo` varchar(20) DEFAULT NULL,
  `semestre` int(11) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`matricula`, `nombre`, `rol`, `estado`, `id_telegram`, `tarjeta_rfid`, `contrasena`, `correo`, `carrera`, `grupo`, `semestre`, `fecha_creacion`) VALUES
('2330018', 'Veyra Gutierrez', 'alumno', 'activo', '5658075717', 'B144B71D', '$2y$10$Dhs33ghW9XZqByn7WFpxlegVKDspjaCthFAiCfYJrROkFtA3aKwj6', '2330018@upv.edu.mx', 'Ingeniería en Tecnologías de la Información', '1', 9, '2026-07-09 13:45:13'),
('2330319', 'Ingridh Gracia', 'docente', 'activo', '8122182826', 'B144B71A', '$2y$10$CeQnY4Vd7beYIybmaftUHO5LUkeYFkoMhsq25DBbPZe84SJ6F1l6a', '2330319@upv.edu.mx', NULL, NULL, NULL, '2026-07-09 15:26:20'),
('2430257', 'Angel Enciso', 'alumno', 'activo', '7591294639', '2430257@UPV.EDU.MX', '$2y$10$nfuS3HjBXhvkhAIYC1Vrl.wy6laJClgCnIzIPo0opah70RQ76g92K', '2430257@upv.edu.mx', 'Ingenieria en Tecnologias de la Informacion E Innovacion Digital', '1', 6, '2026-10-16 17:00:08'),
('ADMIN01', 'Administrador General', 'administrativo', 'activo', NULL, NULL, '$2y$10$fLN/3wXESQ8VtiK6sSmRU.ZtcAfQdCHjkpFcqbBcL6kLMpl.mTlOS', 'admin@sistema.com', NULL, NULL, NULL, '2026-07-09 11:06:21');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `asistencias`
--
ALTER TABLE `asistencias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `clase_id` (`clase_id`),
  ADD KEY `docente_autorizo_id` (`docente_autorizo_id`),
  ADD KEY `dispositivo_id` (`dispositivo_id`);

--
-- Indices de la tabla `clases`
--
ALTER TABLE `clases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `docente_id` (`docente_id`);

--
-- Indices de la tabla `codigos_verificacion`
--
ALTER TABLE `codigos_verificacion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `dispositivos`
--
ALTER TABLE `dispositivos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `device_id` (`device_id`);

--
-- Indices de la tabla `enrolamientos_rfid`
--
ALTER TABLE `enrolamientos_rfid`
  ADD PRIMARY KEY (`id`),
  ADD KEY `matricula` (`matricula`),
  ADD KEY `estado` (`estado`);

--
-- Indices de la tabla `inscripciones`
--
ALTER TABLE `inscripciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `clase_alumno` (`clase_id`,`alumno_id`),
  ADD KEY `alumno_id` (`alumno_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`matricula`),
  ADD UNIQUE KEY `id_telegram` (`id_telegram`),
  ADD UNIQUE KEY `tarjeta_rfid` (`tarjeta_rfid`),
  ADD UNIQUE KEY `correo` (`correo`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `asistencias`
--
ALTER TABLE `asistencias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `clases`
--
ALTER TABLE `clases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `codigos_verificacion`
--
ALTER TABLE `codigos_verificacion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT de la tabla `dispositivos`
--
ALTER TABLE `dispositivos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `enrolamientos_rfid`
--
ALTER TABLE `enrolamientos_rfid`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `inscripciones`
--
ALTER TABLE `inscripciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `asistencias`
--
ALTER TABLE `asistencias`
  ADD CONSTRAINT `asistencias_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`matricula`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `asistencias_ibfk_2` FOREIGN KEY (`clase_id`) REFERENCES `clases` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `asistencias_ibfk_3` FOREIGN KEY (`docente_autorizo_id`) REFERENCES `usuarios` (`matricula`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `asistencias_ibfk_4` FOREIGN KEY (`dispositivo_id`) REFERENCES `dispositivos` (`device_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `clases`
--
ALTER TABLE `clases`
  ADD CONSTRAINT `clases_ibfk_1` FOREIGN KEY (`docente_id`) REFERENCES `usuarios` (`matricula`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `codigos_verificacion`
--
ALTER TABLE `codigos_verificacion`
  ADD CONSTRAINT `codigos_verificacion_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`matricula`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `enrolamientos_rfid`
--
ALTER TABLE `enrolamientos_rfid`
  ADD CONSTRAINT `enrolamientos_ibfk_1` FOREIGN KEY (`matricula`) REFERENCES `usuarios` (`matricula`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `inscripciones`
--
ALTER TABLE `inscripciones`
  ADD CONSTRAINT `inscripciones_ibfk_1` FOREIGN KEY (`clase_id`) REFERENCES `clases` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `inscripciones_ibfk_2` FOREIGN KEY (`alumno_id`) REFERENCES `usuarios` (`matricula`) ON DELETE CASCADE ON UPDATE CASCADE;
--
-- Justificantes de inasistencia y días inhábiles (agregados después del esquema base)
--

CREATE TABLE IF NOT EXISTS `justificantes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `alumno_id` varchar(50) NOT NULL,
  `clase_id` int(11) NOT NULL,
  `fecha` date NOT NULL COMMENT 'Día de la inasistencia que se justifica',
  `motivo` text NOT NULL,
  `archivo` varchar(255) DEFAULT NULL COMMENT 'Nombre del archivo en uploads/justificantes',
  `archivo_nombre` varchar(255) DEFAULT NULL COMMENT 'Nombre original del archivo',
  `estado` enum('pendiente','aceptado','rechazado') NOT NULL DEFAULT 'pendiente',
  `comentario_docente` varchar(500) DEFAULT NULL,
  `resuelto_por` varchar(50) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `resuelto_en` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `alumno_clase_fecha` (`alumno_id`,`clase_id`,`fecha`),
  KEY `clase_id` (`clase_id`),
  KEY `estado` (`estado`),
  CONSTRAINT `justificantes_ibfk_1` FOREIGN KEY (`alumno_id`) REFERENCES `usuarios` (`matricula`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `justificantes_ibfk_2` FOREIGN KEY (`clase_id`) REFERENCES `clases` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `dias_inhabiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `clase_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `motivo` varchar(150) DEFAULT NULL,
  `marcado_por` varchar(50) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `clase_fecha` (`clase_id`,`fecha`),
  CONSTRAINT `dias_inhabiles_ibfk_1` FOREIGN KEY (`clase_id`) REFERENCES `clases` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
