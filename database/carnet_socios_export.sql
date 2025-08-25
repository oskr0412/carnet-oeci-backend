-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 25-08-2025 a las 04:21:12
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
-- Base de datos: `carnet_socios`
--
CREATE DATABASE IF NOT EXISTS `carnet_socios` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `carnet_socios`;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `administradores`
--

DROP TABLE IF EXISTS `administradores`;
CREATE TABLE `administradores` (
  `id` int(11) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `rol` enum('super_admin','admin','operador') DEFAULT 'admin',
  `activo` tinyint(1) DEFAULT 1,
  `ultimo_acceso` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `administradores`
--

INSERT INTO `administradores` (`id`, `usuario`, `email`, `password_hash`, `nombre`, `apellidos`, `telefono`, `rol`, `activo`, `ultimo_acceso`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@carnetsocios.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador', 'Principal', NULL, 'super_admin', 1, NULL, '2025-08-21 00:23:55', '2025-08-21 00:23:55');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion_sistema`
--

DROP TABLE IF EXISTS `configuracion_sistema`;
CREATE TABLE `configuracion_sistema` (
  `id` int(11) NOT NULL,
  `clave` varchar(100) NOT NULL,
  `valor` text DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `tipo` enum('string','number','boolean','json') DEFAULT 'string',
  `categoria` varchar(50) DEFAULT 'general',
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `configuracion_sistema`
--

INSERT INTO `configuracion_sistema` (`id`, `clave`, `valor`, `descripcion`, `tipo`, `categoria`, `updated_by`, `updated_at`) VALUES
(1, 'app_nombre', 'Carnet Digital', 'Nombre de la aplicación', 'string', 'general', NULL, '2025-08-21 00:23:55'),
(2, 'app_version', '1.0.0', 'Versión actual de la aplicación', 'string', 'general', NULL, '2025-08-21 00:23:55'),
(3, 'jwt_expiration_hours', '24', 'Horas de expiración del token JWT', 'number', 'seguridad', NULL, '2025-08-21 00:23:55'),
(4, 'organizacion_nombre', 'Mi Organización', 'Nombre de la organización', 'string', 'general', NULL, '2025-08-21 00:23:55');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `logs_actividad`
--

DROP TABLE IF EXISTS `logs_actividad`;
CREATE TABLE `logs_actividad` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `usuario_tipo` enum('socio','admin') NOT NULL,
  `accion` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `datos_adicionales` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos_adicionales`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sesiones_jwt`
--

DROP TABLE IF EXISTS `sesiones_jwt`;
CREATE TABLE `sesiones_jwt` (
  `id` int(11) NOT NULL,
  `token_jti` varchar(100) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `usuario_tipo` enum('socio','admin') NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `fecha_expiracion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `socios`
--

DROP TABLE IF EXISTS `socios`;
CREATE TABLE `socios` (
  `id` int(11) NOT NULL,
  `numero_socio` varchar(20) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `documento_identidad` varchar(30) DEFAULT NULL,
  `tipo_documento` enum('cedula','pasaporte','dni','otro') DEFAULT 'cedula',
  `fecha_nacimiento` date DEFAULT NULL,
  `genero` enum('masculino','femenino','otro','no_especifica') DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `ciudad` varchar(100) DEFAULT NULL,
  `pais` varchar(100) DEFAULT 'Colombia',
  `tipo_membresia_id` int(11) DEFAULT NULL,
  `fecha_ingreso` date NOT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `estado` enum('activo','suspendido','vencido','cancelado') DEFAULT 'activo',
  `foto_perfil` varchar(255) DEFAULT NULL,
  `qr_code` varchar(255) DEFAULT NULL,
  `ultimo_acceso` timestamp NULL DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `socios`
--

INSERT INTO `socios` (`id`, `numero_socio`, `usuario`, `email`, `password_hash`, `nombre`, `apellidos`, `documento_identidad`, `tipo_documento`, `fecha_nacimiento`, `genero`, `telefono`, `direccion`, `ciudad`, `pais`, `tipo_membresia_id`, `fecha_ingreso`, `fecha_vencimiento`, `estado`, `foto_perfil`, `qr_code`, `ultimo_acceso`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'SOC20240001', 'socio_demo', 'socio@demo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Juan Carlos', 'Pérez García', '12345678', 'cedula', '1990-05-15', 'masculino', '+57 300 123 4567', 'Calle 123 #45-67', 'Bogotá', 'Colombia', 1, '2025-08-20', '2026-08-20', 'activo', NULL, '054b3c6a94dc9e972b6840710b16d2e9', NULL, 1, '2025-08-21 00:23:55', '2025-08-21 00:23:55');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipos_membresia`
--

DROP TABLE IF EXISTS `tipos_membresia`;
CREATE TABLE `tipos_membresia` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio` decimal(10,2) DEFAULT 0.00,
  `duracion_meses` int(11) DEFAULT 12,
  `beneficios` text DEFAULT NULL,
  `color_hex` varchar(7) DEFAULT '#007BFF',
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tipos_membresia`
--

INSERT INTO `tipos_membresia` (`id`, `nombre`, `descripcion`, `precio`, `duracion_meses`, `beneficios`, `color_hex`, `activo`, `created_at`, `updated_at`) VALUES
(1, 'Básica', 'Membresía básica con acceso estándar', 50000.00, 12, 'Acceso a instalaciones básicas', '#28A745', 1, '2025-08-21 00:23:55', '2025-08-21 00:23:55'),
(2, 'Premium', 'Membresía premium con beneficios adicionales', 100000.00, 12, 'Acceso completo + descuentos especiales', '#007BFF', 1, '2025-08-21 00:23:55', '2025-08-21 00:23:55'),
(3, 'VIP', 'Membresía VIP con todos los beneficios', 200000.00, 12, 'Acceso completo + eventos exclusivos + parking', '#FFD700', 1, '2025-08-21 00:23:55', '2025-08-21 00:23:55'),
(4, 'Estudiante', 'Membresía especial para estudiantes', 30000.00, 12, 'Acceso básico con descuento estudiantil', '#17A2B8', 1, '2025-08-21 00:23:55', '2025-08-21 00:23:55');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `administradores`
--
ALTER TABLE `administradores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_admin_usuario` (`usuario`),
  ADD KEY `idx_admin_email` (`email`);

--
-- Indices de la tabla `configuracion_sistema`
--
ALTER TABLE `configuracion_sistema`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `clave` (`clave`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indices de la tabla `logs_actividad`
--
ALTER TABLE `logs_actividad`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_usuario_fecha` (`usuario_id`,`usuario_tipo`,`created_at`);

--
-- Indices de la tabla `sesiones_jwt`
--
ALTER TABLE `sesiones_jwt`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token_jti` (`token_jti`),
  ADD KEY `idx_token_activo` (`token_jti`,`activo`),
  ADD KEY `idx_usuario` (`usuario_id`,`usuario_tipo`);

--
-- Indices de la tabla `socios`
--
ALTER TABLE `socios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_socio` (`numero_socio`),
  ADD UNIQUE KEY `usuario` (`usuario`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `tipo_membresia_id` (`tipo_membresia_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_socios_numero` (`numero_socio`),
  ADD KEY `idx_socios_estado` (`estado`),
  ADD KEY `idx_socios_email` (`email`);

--
-- Indices de la tabla `tipos_membresia`
--
ALTER TABLE `tipos_membresia`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `administradores`
--
ALTER TABLE `administradores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `configuracion_sistema`
--
ALTER TABLE `configuracion_sistema`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `logs_actividad`
--
ALTER TABLE `logs_actividad`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `sesiones_jwt`
--
ALTER TABLE `sesiones_jwt`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `socios`
--
ALTER TABLE `socios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `tipos_membresia`
--
ALTER TABLE `tipos_membresia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `configuracion_sistema`
--
ALTER TABLE `configuracion_sistema`
  ADD CONSTRAINT `configuracion_sistema_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `administradores` (`id`);

--
-- Filtros para la tabla `socios`
--
ALTER TABLE `socios`
  ADD CONSTRAINT `socios_ibfk_1` FOREIGN KEY (`tipo_membresia_id`) REFERENCES `tipos_membresia` (`id`),
  ADD CONSTRAINT `socios_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `administradores` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
