-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 20-10-2026 a las 00:36:30
-- Versión del servidor: 8.4.3
-- Versión de PHP: 8.3.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `gestorpro_db`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `projects`
--

CREATE TABLE `projects` (
  `id` int UNSIGNED NOT NULL,
  `owner_id` int UNSIGNED NOT NULL,
  `name` varchar(150) COLLATE utf8mb3_spanish_ci NOT NULL,
  `description` text COLLATE utf8mb3_spanish_ci,
  `status` enum('planning','active','on_hold','completed','archived') COLLATE utf8mb3_spanish_ci NOT NULL DEFAULT 'planning',
  `start_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ;

--
-- Volcado de datos para la tabla `projects`
--

INSERT INTO `projects` (`id`, `owner_id`, `name`, `description`, `status`, `start_date`, `due_date`, `created_at`, `updated_at`) VALUES
(1, 1, 'Sitio corporativo', 'Diseño y desarrollo de una página institucional.', 'active', '2026-10-01', '2026-11-30', '2026-10-19 16:29:50', '2026-10-19 16:29:50'),
(2, 1, 'Sistema de inventario', 'Control de productos y existencias.', 'planning', '2026-10-15', '2026-12-15', '2026-10-19 16:29:50', '2026-10-19 16:29:50'),
(3, 2, 'Reportes internos', 'Centralización de informes de trabajo.', 'on_hold', '2026-10-05', '2026-12-01', '2026-10-19 16:29:50', '2026-10-19 16:29:50'),
(5, 4, 'Sitio corporativo 2', 'Esto es una prueba de que se puede crear los proyectos', 'active', '2026-10-19', '2026-10-23', '2026-10-20 00:24:20', '2026-10-20 00:24:20');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tasks`
--

CREATE TABLE `tasks` (
  `id` int UNSIGNED NOT NULL,
  `project_id` int UNSIGNED NOT NULL,
  `assigned_to` int UNSIGNED DEFAULT NULL,
  `title` varchar(180) COLLATE utf8mb3_spanish_ci NOT NULL,
  `description` text COLLATE utf8mb3_spanish_ci,
  `status` enum('pending','in_progress','completed','cancelled') COLLATE utf8mb3_spanish_ci NOT NULL DEFAULT 'pending',
  `priority` enum('low','medium','high','urgent') COLLATE utf8mb3_spanish_ci NOT NULL DEFAULT 'medium',
  `due_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ;

--
-- Volcado de datos para la tabla `tasks`
--

INSERT INTO `tasks` (`id`, `project_id`, `assigned_to`, `title`, `description`, `status`, `priority`, `due_date`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 'Diseñar pantalla de acceso', 'Crear el diseño inicial del formulario de inicio de sesión.', 'in_progress', 'high', '2026-10-25', '2026-10-19 16:29:50', '2026-10-19 16:29:50'),
(2, 1, 3, 'Crear página principal', 'Construir la estructura HTML de la página de inicio.', 'pending', 'medium', '2026-10-28', '2026-10-19 16:29:50', '2026-10-19 16:29:50'),
(3, 2, 2, 'Diseñar modelo de productos', 'Definir los campos y relaciones de productos.', 'pending', 'urgent', '2026-11-05', '2026-10-19 16:29:50', '2026-10-19 16:29:50'),
(4, 3, NULL, 'Documentar reportes', 'Preparar la documentación funcional.', 'completed', 'low', '2026-10-20', '2026-10-19 16:29:50', '2026-10-19 16:29:50'),
(5, 2, 4, 'Cambio de colores', 'Se ara el cambio de las venta dashboard', 'in_progress', 'high', '2026-10-23', '2026-10-19 23:54:02', '2026-10-19 23:54:02'),
(6, 5, 4, 'Diseñar pantalla de acceso', 'El departamento de diseño nos dará el diseño para implementarlo.', 'in_progress', 'high', '2026-10-21', '2026-10-20 00:25:42', '2026-10-20 00:25:42');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(100) COLLATE utf8mb3_spanish_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb3_spanish_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb3_spanish_ci NOT NULL,
  `role` enum('admin','member') COLLATE utf8mb3_spanish_ci NOT NULL DEFAULT 'member',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Ana Martínez', 'ana@gestorpro.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1, '2026-10-19 16:29:50', '2026-10-19 16:29:50'),
(2, 'Carlos López', 'carlos@gestorpro.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'member', 1, '2026-10-19 16:29:50', '2026-10-19 16:29:50'),
(3, 'Laura Sánchez', 'laura@gestorpro.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'member', 1, '2026-10-19 16:29:50', '2026-10-19 16:29:50'),
(4, 'Alberto Bernal cThoa#nou406', 'smb34abr@gmail.com', '$2y$10$cjdu2x/.UiNAsXDQWBQ92OvEkmXPFvoeLt8R/XT7eGKpYP4yyO80G', 'admin', 1, '2026-10-19 23:50:25', '2026-10-20 00:21:41');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_projects_owner` (`owner_id`),
  ADD KEY `idx_projects_status` (`status`),
  ADD KEY `idx_projects_due_date` (`due_date`);

--
-- Indices de la tabla `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tasks_project_status` (`project_id`,`status`),
  ADD KEY `idx_tasks_assigned_to` (`assigned_to`),
  ADD KEY `idx_tasks_status` (`status`),
  ADD KEY `idx_tasks_priority` (`priority`),
  ADD KEY `idx_tasks_due_date` (`due_date`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_email` (`email`),
  ADD KEY `idx_users_role` (`role`),
  ADD KEY `idx_users_active` (`is_active`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `fk_projects_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Filtros para la tabla `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `fk_tasks_assignee` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tasks_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- https://chatgpt.com/c/6ad5b953-8590-83e8-a56d-777903c1c9f9