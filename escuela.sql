-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 30-10-2025 a las 01:33:50
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
-- Base de datos: `escuela`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alumnos`
--

CREATE TABLE `alumnos` (
  `matricula` varchar(20) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido_paterno` varchar(100) NOT NULL,
  `apellido_materno` varchar(100) DEFAULT NULL,
  `correo` varchar(150) DEFAULT NULL,
  `carrera` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `alumnos`
--

INSERT INTO `alumnos` (`matricula`, `nombre`, `apellido_paterno`, `apellido_materno`, `correo`, `carrera`) VALUES
('321001797', 'Javier', 'Montes de Oca', 'Chavez', 'correo@gmail.com', 'Informatica'),
('321173012', 'Arturo', 'Rubio', 'Garcia', 'correo@gmail.com', 'Informatica');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alumno_grupo`
--

CREATE TABLE `alumno_grupo` (
  `id` int(11) NOT NULL,
  `matricula` varchar(20) DEFAULT NULL,
  `id_grupo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asistencias`
--

CREATE TABLE `asistencias` (
  `id_asistencia` int(11) NOT NULL,
  `matricula` varchar(20) DEFAULT NULL,
  `id_grupo` int(11) DEFAULT NULL,
  `fecha` date DEFAULT NULL,
  `estado` enum('Asistió','Faltó') NOT NULL DEFAULT 'Faltó'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `grupos`
--

CREATE TABLE `grupos` (
  `id_grupo` int(11) NOT NULL,
  `clave_grupo` varchar(50) NOT NULL,
  `periodo` varchar(50) DEFAULT NULL,
  `materia` varchar(150) DEFAULT NULL,
  `semestre` varchar(50) DEFAULT NULL,
  `carrera` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `grupos`
--

INSERT INTO `grupos` (`id_grupo`, `clave_grupo`, `periodo`, `materia`, `semestre`, `carrera`) VALUES
(1, '1151', '2025-2', 'Finanzas', '5', 'Informatica');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `alumnos`
--
ALTER TABLE `alumnos`
  ADD PRIMARY KEY (`matricula`);

--
-- Indices de la tabla `alumno_grupo`
--
ALTER TABLE `alumno_grupo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `matricula` (`matricula`),
  ADD KEY `id_grupo` (`id_grupo`);

--
-- Indices de la tabla `asistencias`
--
ALTER TABLE `asistencias`
  ADD PRIMARY KEY (`id_asistencia`),
  ADD KEY `matricula` (`matricula`),
  ADD KEY `id_grupo` (`id_grupo`);

--
-- Indices de la tabla `grupos`
--
ALTER TABLE `grupos`
  ADD PRIMARY KEY (`id_grupo`),
  ADD UNIQUE KEY `clave_grupo` (`clave_grupo`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `alumno_grupo`
--
ALTER TABLE `alumno_grupo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `asistencias`
--
ALTER TABLE `asistencias`
  MODIFY `id_asistencia` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `grupos`
--
ALTER TABLE `grupos`
  MODIFY `id_grupo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `alumno_grupo`
--
ALTER TABLE `alumno_grupo`
  ADD CONSTRAINT `alumno_grupo_ibfk_1` FOREIGN KEY (`matricula`) REFERENCES `alumnos` (`matricula`) ON DELETE CASCADE,
  ADD CONSTRAINT `alumno_grupo_ibfk_2` FOREIGN KEY (`id_grupo`) REFERENCES `grupos` (`id_grupo`) ON DELETE CASCADE;

--
-- Filtros para la tabla `asistencias`
--
ALTER TABLE `asistencias`
  ADD CONSTRAINT `asistencias_ibfk_1` FOREIGN KEY (`matricula`) REFERENCES `alumnos` (`matricula`) ON DELETE CASCADE,
  ADD CONSTRAINT `asistencias_ibfk_2` FOREIGN KEY (`id_grupo`) REFERENCES `grupos` (`id_grupo`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
