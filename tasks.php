<?php
declare(strict_types=1);

require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/config/database.php';

// Identificador temporal para las pruebas.
// Se sustituirá por el usuario autenticado cuando implementemos el login.
/* $demoOwnerId = 1; */

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
    WHERE p.owner_id = :owner_id
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
";

$stmt = $pdo->prepare($sql);
$stmt->execute(['owner_id' => $currentUserId]); /* $demoOwnerId */
$tasks = $stmt->fetchAll();

$flashMessage = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);
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

    <section class="panel">
        <div class="panel-heading">
            <div>
                <h2>Todas las tareas</h2>
                <p><?= count($tasks) ?> tarea(s) registrada(s)</p>
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
                                <strong><?= e($task['title']) ?></strong>
                            </td>
                            <td><?= e($task['project_name']) ?></td>
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
                                    <a class="text-danger"
                                       href="task_delete.php?id=<?= (int) $task['id'] ?>">
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
    </section>
</main>

</body>
</html>
