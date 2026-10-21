<?php
declare(strict_types=1);

require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/config/database.php';

$currentUserId = requireLogin();

$statusLabels = [
    'pending' => 'Pendiente',
    'in_progress' => 'En progreso',
    'completed' => 'Completada',
    'cancelled' => 'Cancelada',
];

$priorityLabels = [
    'low' => 'Baja',
    'medium' => 'Media',
    'high' => 'Alta',
    'urgent' => 'Urgente',
];

// Obtener proyectos que pertenecen al usuario autenticado.
$projectStmt = $pdo->prepare("
    SELECT id, name
    FROM projects
    WHERE owner_id = :owner_id
    ORDER BY name
");
$projectStmt->execute(['owner_id' => $currentUserId]);
$projects = $projectStmt->fetchAll();

// Leer y validar los filtros.
$search = trim((string) ($_GET['search'] ?? ''));
$search = function_exists('mb_substr')
    ? mb_substr($search, 0, 100, 'UTF-8')
    : substr($search, 0, 100);

$status = (string) ($_GET['status'] ?? '');
$priority = (string) ($_GET['priority'] ?? '');
$projectIdInput = (string) ($_GET['project_id'] ?? '');

if (!array_key_exists($status, $statusLabels)) {
    $status = '';
}

if (!array_key_exists($priority, $priorityLabels)) {
    $priority = '';
}

$projectId = null;

if ($projectIdInput !== '') {
    $validatedProjectId = filter_var(
        $projectIdInput,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($validatedProjectId !== false) {
        $projectId = (int) $validatedProjectId;
    }
}

// Si el proyecto seleccionado no pertenece al usuario, ignorar el filtro.
$ownedProjectIds = array_map(
    static fn(array $project): int => (int) $project['id'],
    $projects
);

if ($projectId !== null && !in_array($projectId, $ownedProjectIds, true)) {
    $projectId = null;
}

$page = filter_var(
    $_GET['page'] ?? 1,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

$page = $page === false ? 1 : $page;
$perPage = 10;

// Construir condiciones SQL de forma segura.
$where = ['p.owner_id = :owner_id'];

$params = [
    'owner_id' => $currentUserId,
];

if ($search !== '') {
    $where[] = 't.title LIKE :search';
    $params['search'] = '%' . $search . '%';
}

if ($status !== '') {
    $where[] = 't.status = :status';
    $params['status'] = $status;
}

if ($priority !== '') {
    $where[] = 't.priority = :priority';
    $params['priority'] = $priority;
}

if ($projectId !== null) {
    $where[] = 't.project_id = :project_id';
    $params['project_id'] = $projectId;
}

$whereSql = implode(' AND ', $where);

// Contar resultados para calcular la paginación.
$countSql = "
    SELECT COUNT(*)
    FROM tasks t
    INNER JOIN projects p ON p.id = t.project_id
    WHERE $whereSql
";

$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalTasks = (int) $countStmt->fetchColumn();

$totalPages = max(1, (int) ceil($totalTasks / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

// Obtener únicamente las tareas de la página actual.
$sql = "
    SELECT
        t.id,
        t.title,
        t.status,
        t.priority,
        t.due_date,
        p.name AS project_name,
        u.name AS assigned_name
    FROM tasks t
    INNER JOIN projects p ON p.id = t.project_id
    LEFT JOIN users u ON u.id = t.assigned_to
    WHERE $whereSql
    ORDER BY
        CASE t.status
            WHEN 'in_progress' THEN 1
            WHEN 'pending' THEN 2
            WHEN 'completed' THEN 3
            WHEN 'cancelled' THEN 4
            ELSE 5
        END,
        t.due_date IS NULL,
        t.due_date ASC,
        t.id DESC
    LIMIT $perPage OFFSET $offset
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tasks = $stmt->fetchAll();

$flashMessage = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);

// Conservar los filtros al cambiar de página.
$paginationParams = [
    'search' => $search,
    'status' => $status,
    'priority' => $priority,
    'project_id' => $projectId ?? '',
];

$paginationParams = array_filter(
    $paginationParams,
    static fn($value): bool => $value !== ''
);
?>


<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de tareas | GestorPro</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

    <main class="page-container">
        <header class="page-header">
            <div>
                <p class="eyebrow">GESTORPRO / ORGANIZACIÓN</p>
                <h1>Gestión de tareas</h1>
                <p>Organiza las actividades de tus proyectos.</p>
            </div>

            <a class="button-primary" href="task_form.php">
                + Nueva tarea
            </a>
        </header>

        <nav class="page-navigation">
            <a href="index.php">Dashboard</a>
            <a href="projects.php">Proyectos</a>
            <a href="tasks.php" aria-current="page">Tareas</a>
        </nav>

        <?php if ($flashMessage): ?>
        <div class="notice" role="status">
            <?= e((string) $flashMessage) ?>
        </div>
        <?php endif; ?>

        <!-- INICIO -->
        <section class="panel filters-panel">
            <div class="panel-heading">
                <div>
                    <h2>Buscar y filtrar</h2>
                    <p>Encuentra las tareas que necesitas.</p>
                </div>
            </div>

            <form method="GET" action="tasks.php" class="task-filters">
                <div class="form-group">
                    <label for="search">Buscar por título</label>
                    <input id="search" name="search" type="search" maxlength="100"
                        placeholder="Ej. Diseñar página de inicio" value="<?= e($search) ?>">
                </div>

                <div class="form-group">
                    <label for="status">Estado</label>
                    <select id="status" name="status">
                        <option value="">Todos los estados</option>
                        <?php foreach ($statusLabels as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?=$status===$value ? 'selected' : '' ?>>
                            <?= e($label) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="priority">Prioridad</label>
                    <select id="priority" name="priority">
                        <option value="">Todas las prioridades</option>
                        <?php foreach ($priorityLabels as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?=$priority===$value ? 'selected' : '' ?>>
                            <?= e($label) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="project_id">Proyecto</label>
                    <select id="project_id" name="project_id">
                        <option value="">Todos los proyectos</option>
                        <?php foreach ($projects as $project): ?>
                        <option value="<?= (int) $project['id'] ?>" <?=$projectId===(int) $project['id'] ? 'selected'
                            : '' ?>>
                            <?= e($project['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-actions">
                    <button class="button-primary" type="submit">
                        Aplicar filtros
                    </button>
                    <a class="button-secondary" href="tasks.php">
                        Limpiar
                    </a>
                </div>
            </form>
        </section>
        <!-- FIN -->

        <section class="panel">
            <div class="panel-heading">
                <div>
                    <h2>Todas las tareas</h2>
                    <p class="column-count: 2; column-gap: 20px; column-rule: 1px solid #ccc;">
                        <span>
                            <?= $totalTasks ?> resultado(s) encontrado(s),
                        </span>
                        <span>
                            <?= count($tasks) ?> tarea(s) registrada(s)
                        </span>
                    </p>
                </div>
            </div>

            <?php if (!$tasks): ?>
            <div class="empty-state">
                <h3>Aún no hay tareas</h3>
                <p>Crea tu primera tarea para comenzar a organizar el trabajo.</p>
                <a class="button-primary" href="task_form.php">
                    Crear tarea
                </a>

            </div>

            <?php else: ?>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Tarea</th>
                            <th>Proyecto</th>
                            <th>Responsable</th>
                            <th>Prioridad</th>
                            <th>Estado</th>
                            <th>Fecha límite</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tasks as $task): ?>
                        <tr>
                            <td>
                                <strong>
                                    <?= e($task['title']) ?>
                                </strong>
                            </td>
                            <td>
                                <?= e($task['project_name']) ?>
                            </td>
                            <td>
                                <?= e($task['assigned_name'] ?? 'Sin asignar') ?>
                            </td>
                            <td>
                                <span class="priority-badge priority-<?= e($task['priority']) ?>">
                                    <?= e($priorityLabels[$task['priority']] ?? $task['priority']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge">
                                    <?= e($statusLabels[$task['status']] ?? $task['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?= e($task['due_date'] ?? 'Sin fecha') ?>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a href="task_form.php?id=<?= (int) $task['id'] ?>">
                                        Editar
                                    </a>
                                    <a class="text-danger" href="task_delete.php?id=<?= (int) $task['id'] ?>">
                                        Eliminar
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>


            <?php if ($totalTasks > 0): ?>
            <nav class="pagination" aria-label="Paginación de tareas">
                <span class="pagination-info">
                    Total
                    <?= $page ?> de
                    <?= $totalPages ?> Página
                </span>

                <div class="pagination-actions">
                    <?php if ($page > 1): ?>
                    <a class="button-secondary" href="?<?= e(http_build_query(
                        $paginationParams + ['page' => $page - 1]
                    )) ?>">
                        Anterior
                    </a>
                    <?php endif; ?>

                    <?php if ($page < $totalPages): ?>
                    <a class="button-secondary" href="?<?= e(http_build_query(
                        $paginationParams + ['page' => $page + 1]
                    )) ?>">
                        Siguiente
                    </a>
                    <?php endif; ?>
                </div>
            </nav>
            <?php endif; ?>


        </section>
        <h3>
            <?= $totalTasks === 0 && ($search !== '' || $status !== '' || $priority !== '' || $projectId !== null)
            ? 'No encontramos tareas con esos filtros'
            : 'Aún no hay tareas' ?>
        </h3>
        <p>
    <?= $totalTasks === 0 && ($search !== '' || $status !== '' || $priority !== '' || $projectId !== null)
        ? 'Prueba con otros criterios o limpia los filtros.'
        : 'Crea tu primera tarea para comenzar a organizar el trabajo.' ?>
</p>
    </main>

</body>

</html>