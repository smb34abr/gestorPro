
USE gestorpro_db;

-- Usuarios de demostración
INSERT INTO users (name, email, password, role) VALUES
(
    'Ana Martínez',
    'ana@gestorpro.test',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin'
),
(
    'Carlos López',
    'carlos@gestorpro.test',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'member'
),
(
    'Laura Sánchez',
    'laura@gestorpro.test',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'member'
);

-- Proyectos de demostración
INSERT INTO projects (
    owner_id, name, description, status, start_date, due_date
) VALUES
(
    1,
    'Sitio corporativo',
    'Diseño y desarrollo de una página institucional.',
    'active',
    '2026-10-01',
    '2026-11-30'
),
(
    1,
    'Sistema de inventario',
    'Control de productos y existencias.',
    'planning',
    '2026-10-15',
    '2026-12-15'
),
(
    2,
    'Reportes internos',
    'Centralización de informes de trabajo.',
    'on_hold',
    '2026-10-05',
    '2026-12-01'
);

-- Tareas de demostración
INSERT INTO tasks (
    project_id, assigned_to, title,
    description, status, priority, due_date
) VALUES
(
    1, 2,
    'Diseñar pantalla de acceso',
    'Crear el diseño inicial del formulario de inicio de sesión.',
    'in_progress', 'high', '2026-10-25'
),
(
    1, 3,
    'Crear página principal',
    'Construir la estructura HTML de la página de inicio.',
    'pending', 'medium', '2026-10-28'
),
(
    2, 2,
    'Diseñar modelo de productos',
    'Definir los campos y relaciones de productos.',
    'pending', 'urgent', '2026-11-05'
),
(
    3, NULL,
    'Documentar reportes',
    'Preparar la documentación funcional.',
    'completed', 'low', '2026-10-20'
);
