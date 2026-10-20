<?php
declare(strict_types=1);

require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/config/database.php';

/* $demoOwnerId = 1; */
$currentUserId = requireLogin();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id === false || $id === null || $id < 1) {
    http_response_code(400);
    exit('Identificador de tarea no válido.');
}

$stmt = $pdo->prepare("
    SELECT t.id, t.title, p.name AS project_name
    FROM tasks t
    INNER JOIN projects p ON p.id = t.project_id
    WHERE t.id = :id AND p.owner_id = :owner_id
");
$stmt->execute([
    'id' => $id,
    'owner_id' => $currentUserId, /* $demoOwnerId, */
]);

$task = $stmt->fetch();

if (!$task) {
    http_response_code(404);
    exit('La tarea no existe o no tienes acceso a ella.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    $deleteStmt = $pdo->prepare("
        DELETE t
        FROM tasks t
        INNER JOIN projects p ON p.id = t.project_id
        WHERE t.id = :id AND p.owner_id = :owner_id
    ");

    $deleteStmt->execute([
        'id' => $id,
        'owner_id' => $currentUserId, /* $demoOwnerId, */
    ]);

    if ($deleteStmt->rowCount() !== 1) {
        http_response_code(409);
        exit('No se pudo eliminar la tarea. Actualiza la página e inténtalo de nuevo.');
    }

    $_SESSION['flash_message'] = 'Tarea eliminada correctamente.';
    redirect('tasks.php');
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eliminar tarea | GestorPro</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<main class="page-container">
    <section class="panel confirmation-panel">
        <p class="eyebrow">GESTORPRO / CONFIRMACIÓN</p>
        <h1>¿Eliminar esta tarea?</h1>

        <p>
            Vas a eliminar la tarea
            <strong><?= e($task['title']) ?></strong>
            del proyecto
            <strong><?= e($task['project_name']) ?></strong>.
        </p>

        <p>Esta acción no se puede deshacer.</p>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

            <div class="form-actions">
                <a class="button-secondary" href="tasks.php">Cancelar</a>
                <button class="button-danger" type="submit">
                    Sí, eliminar tarea
                </button>
            </div>
        </form>
    </section>
</main>

</body>
</html>
