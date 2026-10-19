<?php

declare(strict_types=1);

require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/config/database.php';

$demoOwnerId = 1;

$statuses = [
    'planning' => 'Planificación',
    'active' => 'Activo',
    'on_hold' => 'En pausa',
    'completed' => 'Completado',
    'archived' => 'Archivado',
];

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$isEditing = $id !== null && $id !== false && $id > 0;

$project = [
    'name' => '',
    'description' => '',
    'status' => 'planning',
    'start_date' => '',
    'due_date' => '',
];

$errors = [];

if ($isEditing) {
    $stmt = $pdo->prepare(
        'SELECT id, name, description, status, start_date, due_date
         FROM projects
         WHERE id = :id AND owner_id = :owner_id'
    );

    $stmt->execute([
        'id' => $id,
        'owner_id' => $demoOwnerId,
    ]);

    $existingProject = $stmt->fetch();

    if (!$existingProject) {
        http_response_code(404);
        exit('Proyecto no encontrado.');
    }

    $project = $existingProject;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    $name = trim((string) ($_POST['name'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $status = (string) ($_POST['status'] ?? '');
    $startDate = trim((string) ($_POST['start_date'] ?? ''));
    $dueDate = trim((string) ($_POST['due_date'] ?? ''));

    $project = [
        'name' => $name,
        'description' => $description,
        'status' => $status,
        'start_date' => $startDate,
        'due_date' => $dueDate,
    ];

    if (mb_strlen($name) < 3 || mb_strlen($name) > 150) {
        $errors[] = 'El nombre debe tener entre 3 y 150 caracteres.';
    }

    if (mb_strlen($description) > 10000) {
        $errors[] = 'La descripción no puede superar 10,000 caracteres.';
    }

    if (!array_key_exists($status, $statuses)) {
        $errors[] = 'Selecciona un estado válido.';
    }

    // Valida fechas reales con formato AAAA-MM-DD.
    $validateDate = static function (string $date): bool {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        return $parsed !== false
            && $parsed->format('Y-m-d') === $date;
    };

    if ($startDate !== '' && !$validateDate($startDate)) {
        $errors[] = 'La fecha de inicio no es válida.';
    }

    if ($dueDate !== '' && !$validateDate($dueDate)) {
        $errors[] = 'La fecha límite no es válida.';
    }

    if (
        $startDate !== ''
        && $dueDate !== ''
        && $validateDate($startDate)
        && $validateDate($dueDate)
        && $dueDate < $startDate
    ) {
        $errors[] = 'La fecha límite no puede ser anterior a la fecha de inicio.';
    }

    if (!$errors) {
        if ($isEditing) {
            $sql = 'UPDATE projects
                    SET name = :name,
                        description = :description,
                        status = :status,
                        start_date = :start_date,
                        due_date = :due_date
                    WHERE id = :id AND owner_id = :owner_id';

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                'name' => $name,
                'description' => $description !== '' ? $description : null,
                'status' => $status,
                'start_date' => $startDate !== '' ? $startDate : null,
                'due_date' => $dueDate !== '' ? $dueDate : null,
                'id' => $id,
                'owner_id' => $demoOwnerId,
            ]);

            $_SESSION['flash_message'] = 'Proyecto actualizado correctamente.';
        } else {
            $sql = 'INSERT INTO projects
                        (owner_id, name, description, status, start_date, due_date)
                    VALUES
                        (:owner_id, :name, :description, :status, :start_date, :due_date)';

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                'owner_id' => $demoOwnerId,
                'name' => $name,
                'description' => $description !== '' ? $description : null,
                'status' => $status,
                'start_date' => $startDate !== '' ? $startDate : null,
                'due_date' => $dueDate !== '' ? $dueDate : null,
            ]);

            $_SESSION['flash_message'] = 'Proyecto creado correctamente.';
        }

        redirect('projects.php');
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $isEditing ? 'Editar' : 'Nuevo' ?> proyecto | GestorPro</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="shortcut icon" href="assets/img/favicon.ico" type="image/x-icon">
</head>
<body>
<main class="page-content">
    <div class="page-heading">
        <div>
            <p class="eyebrow">GESTIÓN DE PROYECTOS</p>
            <h1><?= $isEditing ? 'Editar proyecto' : 'Nuevo proyecto' ?></h1>
            <p class="subtitle">
                Completa los datos del proyecto.
            </p>
        </div>
    </div>

    <section class="panel">
        <?php if ($errors): ?>
            <div class="form-errors" role="alert">
                <strong>Revisa los siguientes errores:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="">
            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrfToken()) ?>"
            >

            <div class="form-group">
                <label for="name">Nombre del proyecto *</label>
                <input
                    id="name"
                    name="name"
                    type="text"
                    minlength="3"
                    maxlength="150"
                    required
                    value="<?= e($project['name']) ?>"
                >
            </div>

            <div class="form-group">
                <label for="description">Descripción</label>
                <textarea
                    id="description"
                    name="description"
                    rows="5"
                    maxlength="10000"
                ><?= e($project['description']) ?></textarea>
            </div>

            <div class="form-group">
                <label for="status">Estado *</label>
                <select id="status" name="status" required>
                    <?php foreach ($statuses as $value => $label): ?>
                        <option
                            value="<?= e($value) ?>"
                            <?= $project['status'] === $value ? 'selected' : '' ?>
                        >
                            <?= e($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="start_date">Fecha de inicio</label>
                    <input
                        id="start_date"
                        name="start_date"
                        type="date"
                        value="<?= e($project['start_date']) ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="due_date">Fecha límite</label>
                    <input
                        id="due_date"
                        name="due_date"
                        type="date"
                        value="<?= e($project['due_date']) ?>"
                    >
                </div>
            </div>

            <div class="form-actions">
                <button class="button button-primary" type="submit">
                    <?= $isEditing ? 'Guardar cambios' : 'Crear proyecto' ?>
                </button>

                <a class="button button-secondary" href="projects.php">
                    Cancelar
                </a>
            </div>
        </form>
    </section>
</main>
</body>
</html>
