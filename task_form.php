<?php

declare(strict_types=1);

require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/config/database.php';

/* $demoOwnerId = 1; */
$currentUserId = requireLogin();

$statuses = [
    'pending' => 'Pendiente',
    'in_progress' => 'En progreso',
    'completed' => 'Completada',
    'cancelled' => 'Cancelada',
];

$priorities = [
    'low' => 'Baja',
    'medium' => 'Media',
    'high' => 'Alta',
    'urgent' => 'Urgente',
];

$errors = [];
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id !== null && $id !== false && $id < 1) {
    http_response_code(400);
    exit('Identificador de tarea no válido.');
}

$isEditing = $id !== null && $id !== false;

$task = [
    'title' => '',
    'description' => '',
    'project_id' => '',
    'assigned_to' => '',
    'status' => 'pending',
    'priority' => 'medium',
    'due_date' => '',
];

if ($isEditing) {
    $stmt = $pdo->prepare("
        SELECT t.*
        FROM tasks t
        INNER JOIN projects p ON p.id = t.project_id
        WHERE t.id = :id AND p.owner_id = :owner_id
    ");
    $stmt->execute([
        'id' => $id,
        'owner_id' => $currentUserId, /* $demoOwnerId, */
    ]);

    $existingTask = $stmt->fetch();

    if (!$existingTask) {
        http_response_code(404);
        exit('La tarea no existe o no tienes acceso a ella.');
    }

    $task = $existingTask;
}

$projectStmt = $pdo->prepare("
    SELECT id, name
    FROM projects
    WHERE owner_id = :owner_id
    ORDER BY name
");
$projectStmt->execute(['owner_id' => $currentUserId]); /* $demoOwnerId */
$projects = $projectStmt->fetchAll();

$userStmt = $pdo->query("
    SELECT id, name
    FROM users
    WHERE is_active = 1
    ORDER BY name
");
$users = $userStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    $task['title'] = trim((string) ($_POST['title'] ?? ''));
    $task['description'] = trim((string) ($_POST['description'] ?? ''));
    $task['project_id'] = trim((string) ($_POST['project_id'] ?? ''));
    $task['assigned_to'] = trim((string) ($_POST['assigned_to'] ?? ''));
    $task['status'] = (string) ($_POST['status'] ?? '');
    $task['priority'] = (string) ($_POST['priority'] ?? '');
    $task['due_date'] = trim((string) ($_POST['due_date'] ?? ''));

    $titleLength = function_exists('mb_strlen')
        ? mb_strlen($task['title'], 'UTF-8')
        : strlen($task['title']);

    if ($task['title'] === '' || $titleLength < 3 || $titleLength > 200) {
        $errors[] = 'El título debe tener entre 3 y 200 caracteres.';
    }

    if (strlen($task['description']) > 10000) {
        $errors[] = 'La descripción no puede superar los 10,000 caracteres.';
    }

    $projectId = filter_var($task['project_id'], FILTER_VALIDATE_INT);

    if (!$projectId || $projectId < 1) {
        $errors[] = 'Selecciona un proyecto válido.';
    } else {
        $checkProject = $pdo->prepare("
            SELECT id FROM projects
            WHERE id = :id AND owner_id = :owner_id
        ");
        $checkProject->execute([
            'id' => $projectId,
            'owner_id' => $currentUserId, /* $demoOwnerId, */
        ]);

        if (!$checkProject->fetch()) {
            $errors[] = 'El proyecto seleccionado no es válido.';
        }
    }

    $assignedTo = null;

    if ($task['assigned_to'] !== '') {
        $userId = filter_var($task['assigned_to'], FILTER_VALIDATE_INT);

        if (!$userId || $userId < 1) {
            $errors[] = 'Selecciona un responsable válido.';
        } else {
            $checkUser = $pdo->prepare("
                SELECT id FROM users
                WHERE id = :id AND is_active = 1
            ");
            $checkUser->execute(['id' => $userId]);

            if (!$checkUser->fetch()) {
                $errors[] = 'El responsable seleccionado no está disponible.';
            } else {
                $assignedTo = $userId;
            }
        }
    }

    if (!array_key_exists($task['status'], $statuses)) {
        $errors[] = 'Selecciona un estado válido.';
    }

    if (!array_key_exists($task['priority'], $priorities)) {
        $errors[] = 'Selecciona una prioridad válida.';
    }

    $dueDate = null;

    if ($task['due_date'] !== '') {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $task['due_date']);
        $dateErrors = DateTimeImmutable::getLastErrors();

        $validDate = $date !== false
            && ($dateErrors === false
                || ($dateErrors['warning_count'] === 0
                    && $dateErrors['error_count'] === 0))
            && $date->format('Y-m-d') === $task['due_date'];

        if (!$validDate) {
            $errors[] = 'La fecha límite no es válida.';
        } else {
            $dueDate = $task['due_date'];
        }
    }

    if (!$errors) {
        $params = [
            'project_id' => $projectId,
            'assigned_to' => $assignedTo,
            'title' => $task['title'],
            'description' => $task['description'] !== ''
                ? $task['description'] : null,
            'status' => $task['status'],
            'priority' => $task['priority'],
            'due_date' => $dueDate,
        ];

        if ($isEditing) {
            $params['id'] = $id;
            $params['owner_id'] = $currentUserId; /* $demoOwnerId; */

            $sql = "
                UPDATE tasks t
                INNER JOIN projects p ON p.id = t.project_id
                SET
                    t.project_id = :project_id,
                    t.assigned_to = :assigned_to,
                    t.title = :title,
                    t.description = :description,
                    t.status = :status,
                    t.priority = :priority,
                    t.due_date = :due_date
                WHERE t.id = :id AND p.owner_id = :owner_id
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $_SESSION['flash_message'] = 'Tarea actualizada correctamente.';
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO tasks (
                    project_id, assigned_to, title, description,
                    status, priority, due_date
                ) VALUES (
                    :project_id, :assigned_to, :title, :description,
                    :status, :priority, :due_date
                )
            ");
            $stmt->execute($params);

            $_SESSION['flash_message'] = 'Tarea creada correctamente.';
        }

        redirect('tasks.php');
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEditing ? 'Editar tarea' : 'Nueva tarea' ?> | GestorPro</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<main class="page-container">
    <header class="page-header">
        <div>
            <p class="eyebrow">GESTORPRO / TAREAS</p>
            <h1><?= $isEditing ? 'Editar tarea' : 'Crear tarea' ?></h1>
            <p>Completa la información de la actividad.</p>
        </div>
        <a class="button-secondary" href="tasks.php">Volver a tareas</a>
    </header>

    <?php if ($errors): ?>
        <div class="form-errors" role="alert">
            <strong>Revisa los siguientes campos:</strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (!$projects): ?>
        <section class="panel">
            <h2>Primero necesitas un proyecto</h2>
            <p>Registra un proyecto antes de crear una tarea.</p>
            <a class="button-primary" href="project_form.php">Crear proyecto</a>
        </section>
    <?php else: ?>
        <form class="panel form-panel" method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

            <div class="form-group">
                <label for="title">Título *</label>
                <input id="title" name="title" type="text"
                       minlength="3" maxlength="200" required
                       value="<?= e($task['title']) ?>">
            </div>

            <div class="form-group">
                <label for="description">Descripción</label>
                <textarea id="description" name="description"
                          rows="4" maxlength="10000"><?= e($task['description']) ?></textarea>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="project_id">Proyecto *</label>
                    <select id="project_id" name="project_id" required>
                        <option value="">Selecciona un proyecto</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?= (int) $project['id'] ?>"
                                <?= (string) $task['project_id'] === (string) $project['id']
                                    ? 'selected' : '' ?>>
                                <?= e($project['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="assigned_to">Responsable</label>
                    <select id="assigned_to" name="assigned_to">
                        <option value="">Sin asignar</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= (int) $user['id'] ?>"
                                <?= (string) ($task['assigned_to'] ?? '') === (string) $user['id']
                                    ? 'selected' : '' ?>>
                                <?= e($user['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="priority">Prioridad *</label>
                    <select id="priority" name="priority" required>
                        <?php foreach ($priorities as $value => $label): ?>
                            <option value="<?= e($value) ?>"
                                <?= $task['priority'] === $value ? 'selected' : '' ?>>
                                <?= e($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="status">Estado *</label>
                    <select id="status" name="status" required>
                        <?php foreach ($statuses as $value => $label): ?>
                            <option value="<?= e($value) ?>"
                                <?= $task['status'] === $value ? 'selected' : '' ?>>
                                <?= e($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="due_date">Fecha límite</label>
                    <input id="due_date" name="due_date" type="date"
                           value="<?= e($task['due_date'] ?? '') ?>">
                </div>
            </div>

            <div class="form-actions">
                <a class="button-secondary" href="tasks.php">Cancelar</a>
                <button class="button-primary" type="submit">
                    <?= $isEditing ? 'Guardar cambios' : 'Crear tarea' ?>
                </button>
            </div>
        </form>
    <?php endif; ?>
</main>

</body>
</html>
