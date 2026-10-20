<?php

declare(strict_types=1);

require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/config/database.php';

// Usuario de demostración.
// Sustituiremos este valor por el ID de la sesión al implementar login.

/* $demoOwnerId = 1; */
$currentUserId = requireLogin();

$stmt = $pdo->prepare(
    'SELECT
        p.id,
        p.name,
        p.description,
        p.status,
        p.start_date,
        p.due_date,
        p.created_at,
        COUNT(t.id) AS total_tasks
     FROM projects AS p
     LEFT JOIN tasks AS t ON t.project_id = p.id
     WHERE p.owner_id = :owner_id
     GROUP BY
        p.id, p.name, p.description, p.status,
        p.start_date, p.due_date, p.created_at
     ORDER BY p.created_at DESC'
);

$stmt->execute(['owner_id' => $currentUserId]); /* $demoOwnerId */
$projects = $stmt->fetchAll();

$statusLabels = [
    'planning' => 'Planificación',
    'active' => 'Activo',
    'on_hold' => 'En pausa',
    'completed' => 'Completado',
    'archived' => 'Archivado',
];

$message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Proyectos | GestorPro</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="shortcut icon" href="assets/img/favicon.ico" type="image/x-icon">
</head>
<body>
<main class="page-content">
    <div class="page-heading">
        <div>
            <p class="eyebrow">GESTIÓN</p>
            <h1>Mis proyectos</h1>
            <p class="subtitle">
                Administra y consulta tus proyectos registrados.
            </p>
        </div>

        <a class="button button-primary" href="project_form.php">
            + Nuevo proyecto
        </a>
    </div>

    <?php if ($message): ?>
        <p class="notice"><?= e($message) ?></p>
    <?php endif; ?>

    <section class="panel">
        <?php if (!$projects): ?>
            <p>Aún no tienes proyectos registrados.</p>
            <a class="button button-primary" href="project_form.php">
                Crear mi primer proyecto
            </a>
        <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                    <tr>
                        <th>Proyecto</th>
                        <th>Estado</th>
                        <th>Tareas</th>
                        <th>Fecha límite</th>
                        <th>Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($projects as $project): ?>
                        <tr>
                            <td>
                                <strong><?= e($project['name']) ?></strong>
                                <br>
                                <span class="subtitle">
                                    <?= e($project['description']) ?>
                                </span>
                            </td>
                            <td>
                                <?= e(
                                    $statusLabels[$project['status']]
                                    ?? $project['status']
                                ) ?>
                            </td>
                            <td><?= (int) $project['total_tasks'] ?></td>
                            <td>
                                <?= e($project['due_date'] ?? 'Sin fecha') ?>
                            </td>
                            <td>
                                <a href="project_form.php?id=<?= (int) $project['id'] ?>">
                                    Editar
                                </a>
                                &nbsp;|&nbsp;
                                <a href="project_delete.php?id=<?= (int) $project['id'] ?>">
                                    Eliminar
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <p style="margin-top: 24px">
            <a href="index.php">Volver al dashboard de demostración</a>
        </p>
    </section>
</main>
</body>
</html>
