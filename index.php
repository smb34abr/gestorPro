<?php
declare(strict_types=1);

require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/config/database.php';

$currentUserId = requireLogin();

// 1. Resumen de proyectos del usuario autenticado.
$projectStatsStmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total_projects,
        COALESCE(SUM(status = 'active'), 0) AS active_projects,
        COALESCE(SUM(status = 'completed'), 0) AS completed_projects
    FROM projects
    WHERE owner_id = :owner_id
");

$projectStatsStmt->execute([
  'owner_id' => $currentUserId,
]);

$projectStats = $projectStatsStmt->fetch();

// 2. Resumen de tareas pertenecientes a sus proyectos.
$taskStatsStmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total_tasks,
        COALESCE(SUM(t.status = 'pending'), 0) AS pending_tasks,
        COALESCE(SUM(t.status = 'in_progress'), 0) AS in_progress_tasks,
        COALESCE(SUM(t.status = 'completed'), 0) AS completed_tasks,
        COALESCE(SUM(
            t.due_date IS NOT NULL
            AND t.due_date >= CURDATE()
            AND t.due_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
            AND t.status IN ('pending', 'in_progress')
        ), 0) AS upcoming_tasks
    FROM tasks t
    INNER JOIN projects p ON p.id = t.project_id
    WHERE p.owner_id = :owner_id
");

$taskStatsStmt->execute([
  'owner_id' => $currentUserId,
]);

$taskStats = $taskStatsStmt->fetch();

// 3. Proyectos recientes con el número de tareas de cada uno.
$recentProjectsStmt = $pdo->prepare("
    SELECT
        p.id,
        p.name,
        p.status,
        p.due_date,
        (
            SELECT COUNT(*)
            FROM tasks t
            WHERE t.project_id = p.id
        ) AS total_tasks,
        (
            SELECT COUNT(*)
            FROM tasks t
            WHERE t.project_id = p.id
              AND t.status = 'completed'
        ) AS completed_tasks
    FROM projects p
    WHERE p.owner_id = :owner_id
    ORDER BY p.updated_at DESC, p.id DESC
    LIMIT 5
");

$recentProjectsStmt->execute([
  'owner_id' => $currentUserId,
]);

$recentProjects = $recentProjectsStmt->fetchAll();

// 4. Etiquetas legibles para mostrar los estados.
$projectStatusLabels = [
  'planning' => 'En planificación',
  'active' => 'Activo',
  'on_hold' => 'En pausa',
  'completed' => 'Completado',
  'archived' => 'Archivado',
];

$flashMessage = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);
?>




<?php
/*
  declare(strict_types=1);
  require_once __DIR__ . '/config/functions.php';
  $currentUserId = requireLogin(); 
 */
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <link rel="shortcut icon" href="assets/img/favicon.ico" type="image/x-icon">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="GestorPro: sistema de gestión de proyectos y tareas.">
  <title>GestorPro | Panel de control</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <script src="assets/js/app.js" defer></script>
</head>

<body>
  <div class="app">

    <aside class="sidebar" id="sidebar">
      <a class="brand" href="#inicio">
        <span class="brand-icon">G</span>
        <span>Gestor<span class="brand-accent">Pro</span></span>
      </a>

      <p class="nav-label">MENÚ PRINCIPAL</p>

      <nav class="navigation" aria-label="Navegación principal">
        <a class="nav-link active" href="#inicio">
          <span>▦</span> Dashboard
        </a>
        <a class="nav-link" href="projects.php"> <!-- #proyectos -->
          <span>▣</span> Proyectos
        </a>
        <a class="nav-link" href="tasks.php">
          <span>✓</span> Tareas
        </a>
        <a class="nav-link" href="#equipo">
          <span>♙</span> Equipo
        </a>
        <a class="nav-link" href="#reportes">
          <span>▥</span> Reportes
        </a>
      </nav>

      <div class="sidebar-bottom">
        <div class="user-avatar">AD</div>
        <div class="user-info">
          <strong>Administrador</strong>
          <span>Cuenta de demostración</span>
        </div>
      </div>
    </aside>

    <main class="main-content" id="inicio">
      <header class="topbar">
        <button class="menu-toggle" id="menuToggle" aria-label="Abrir o cerrar menú" aria-expanded="false">
          ☰
        </button>
        <div class="breadcrumb">Workspace / <strong>Dashboard</strong></div>
        <!-- <span class="status"><span></span> Vista de demostración</span> -->
        <!-- Usuario -->
        <p>
          Bienvenido,
          <?= e($_SESSION['user_name'] ?? 'Usuario') ?>
        </p>

        <form action="logout.php" method="POST">
          <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
          <button type="submit" class="button-secondary">
            Cerrar sesión
          </button>
        </form>
        <!-- fin -->

      </header>

      <section class="page-content">
        <div class="page-heading">
          <div>
            <p class="eyebrow">RESUMEN GENERAL</p>
            <h1>Panel de control</h1>
            <p class="subtitle">
              Organiza tus proyectos y alcanza tus objetivos.
            </p>
          </div>
          <a class="button button-primary" href="project_form.php"> <!-- #proyectos -->
            + Nuevo proyecto
          </a>
        </div>

        <section class="stats-grid" aria-label="Indicadores">
          <article class="stat-card">
            <div class="stat-top">
              <span>Total de proyectos</span>
              <span class="stat-icon purple">▣</span>
            </div>
            <p class="stat-number"><?= (int) $projectStats['total_projects'] ?></p>
            <span class="stat-note">Proyectos registrados</span>
          </article>

          <article class="stat-card">
            <div class="stat-top">
              <span>Proyectos activos</span>
              <span class="stat-icon orange">◷</span>
            </div>
            <p class="stat-number"><?= (int) $projectStats['active_projects'] ?></p>
            <span class="stat-note">En ejecución</span>
          </article>

          <article class="stat-card">
            <div class="stat-top">
              <span>Tareas pendientes</span>
              <span class="stat-icon blue">↗</span>
            </div>
            <p class="stat-number"><?= (int) $taskStats['pending_tasks'] ?></p>
            <span class="stat-note">Por comenzar</span>
          </article>

          <article class="stat-card">
            <div class="stat-top">
              <span>Tareas completadas</span>              
              <span class="stat-icon green">✓</span>
            </div>
            <p class="stat-number"><?= (int) $taskStats['completed_tasks'] ?></p>
            <span class="stat-note">Finalizadas</span>
            <div class="progress-track">
              <div class="progress-fill" style="width:72%"></div>
            </div>
          </article>
        </section>

        <section class="panel projects-panel" id="proyectos">
          <div class="panel-heading">
            <div>
              <h2>Proyectos recientes</h2>
              <p>Seguimiento de tus proyectos activos.</p>
            </div>
            <button class="button button-secondary" id="viewProjects">Ver proyectos</button>
          </div>

          <div class="project-grid">
            <article class="project-card">
              <div class="project-symbol purple">W</div>
              <span class="project-tag">Diseño web</span>
              <h3>Sitio corporativo</h3>
              <p>Rediseño de la página institucional.</p>
              <div class="progress-label">
                <span>Progreso</span><strong>80%</strong>
              </div>
              <div class="progress-track">
                <div class="progress-fill" style="width:80%"></div>
              </div>
            </article>

            <article class="project-card">
              <div class="project-symbol blue">S</div>
              <span class="project-tag">Desarrollo</span>
              <h3>Sistema de inventario</h3>
              <p>Control de productos y existencias.</p>
              <div class="progress-label">
                <span>Progreso</span><strong>55%</strong>
              </div>
              <div class="progress-track">
                <div class="progress-fill" style="width:55%"></div>
              </div>
            </article>

            <article class="project-card">
              <div class="project-symbol green">R</div>
              <span class="project-tag">Operaciones</span>
              <h3>Reportes internos</h3>
              <p>Centralización de informes de trabajo.</p>
              <div class="progress-label">
                <span>Progreso</span><strong>35%</strong>
              </div>
              <div class="progress-track">
                <div class="progress-fill" style="width:35%"></div>
              </div>
            </article>
          </div>
        </section>

        <section class="panel tasks-panel" id="tareas">
          <div class="panel-heading">
            <div>
              <h2>Mis tareas</h2>
              <p>Consulta las actividades programadas.</p>
            </div>
            <label class="search-box">
              <span aria-hidden="true">⌕</span>
              <input id="taskSearch" type="search" placeholder="Buscar tarea..." aria-label="Buscar tarea">
            </label>
          </div>

          <div class="table-wrapper">
            <table>
              <thead>
                <tr>
                  <th>Tarea</th>
                  <th>Proyecto</th>
                  <th>Prioridad</th>
                  <th>Estado</th>
                </tr>
              </thead>
              <tbody id="taskTable">
                <tr>
                  <td>Diseñar pantalla de acceso</td>
                  <td>Sitio corporativo</td>
                  <td><span class="priority high">Alta</span></td>
                  <td><span class="task-status progress">En progreso</span></td>
                </tr>
                <tr>
                  <td>Crear modelo de productos</td>
                  <td>Sistema de inventario</td>
                  <td><span class="priority medium">Media</span></td>
                  <td><span class="task-status pending">Pendiente</span></td>
                </tr>
                <tr>
                  <td>Validar formulario de reportes</td>
                  <td>Reportes internos</td>
                  <td><span class="priority low">Baja</span></td>
                  <td><span class="task-status done">Completada</span></td>
                </tr>
                <tr>
                  <td>Preparar documentación técnica</td>
                  <td>Sitio corporativo</td>
                  <td><span class="priority medium">Media</span></td>
                  <td><span class="task-status pending">Pendiente</span></td>
                </tr>
              </tbody>
            </table>
            <p id="emptyMessage" class="empty-message" hidden>
              No se encontraron tareas.
            </p>
          </div>
          <p class="demo-note">
            Los registros mostrados son datos de demostración.
          </p>
        </section>

        <footer class="footer">
          <span>GestorPro · Proyecto de portafolio</span>
          <span>Versión 0.1.0</span>
        </footer>
      </section>
    </main>
  </div>
</body>

</html>